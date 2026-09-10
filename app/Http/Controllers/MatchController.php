<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Services\StandingsCalculationService;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    /**
     * Liste les matchs de l'ASC de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $ascCode = $request->user()->asc_code;

        $pouleTeamIds = \App\Models\PouleTeam::where('asc_code', $ascCode)->pluck('id')->toArray();

        // On charge l'ASC de l'adversaire si c'est une équipe de poule ou via adversaire_code
        $matches = MatchGame::with(['asc', 'opponent.asc', 'events'])
            ->where(function($query) use ($ascCode, $pouleTeamIds) {
                $query->where('asc_code', $ascCode)
                      ->orWhere('adversaire_code', $ascCode)
                      ->orWhereIn('poule_team_id', $pouleTeamIds);
            })
            ->orderBy('date_match', 'asc')
            ->get();

        $formattedMatches = $matches->map(function ($match) {
            // Team A = asc_code
            // Team B = poule_team -> asc_code (ou adversaire_nom)
            
            $teamAName = $match->asc ? $match->asc->nom : 'Équipe A';
            $teamALogo = $match->asc ? $match->asc->logo_url : null;
            
            $teamBName = 'Adversaire';
            $teamBLogo = null;

            if ($match->opponent) {
                if ($match->opponent->asc) {
                    $teamBName = $match->opponent->asc->nom;
                    $teamBLogo = $match->opponent->asc->logo_url;
                } else {
                    $teamBName = $match->opponent->nom_equipe;
                }
            } elseif ($match->adversaire_code) {
                $advAsc = \App\Models\Asc::where('code_unique', $match->adversaire_code)->first();
                if ($advAsc) {
                    $teamBName = $advAsc->nom;
                    $teamBLogo = $advAsc->logo_url;
                }
            } elseif ($match->adversaire_nom) {
                $teamBName = $match->adversaire_nom;
            }

            $match->team_a_name = $teamAName;
            $match->team_a_logo = $teamALogo;
            $match->team_b_name = $teamBName;
            $match->team_b_logo = $teamBLogo;

            return $match;
        });

        return response()->json($formattedMatches);
    }

    /**
     * Le Chargé de Com enregistre un nouveau match
     */
    public function store(Request $request)
    {
        $request->validate([
            'poule_team_id' => 'required|exists:poule_teams,id',
            'date_match'    => 'required|date',
            'categorie'     => 'nullable|in:CADET,SENIOR',
            'lieu'          => 'nullable|string|max:255',
            'phase'         => 'nullable|string|max:100',
        ]);

        $match = MatchGame::create([
            'asc_code'      => $request->user()->asc_code,
            'poule_team_id' => $request->poule_team_id,
            'date_match'    => $request->date_match,
            'statut'        => 'A_VENIR',
            'score_asc'     => 0,
            'score_adv'     => 0,
            'categorie'     => $request->categorie ?? 'SENIOR',
            'lieu'          => $request->lieu,
            'phase'         => $request->phase ?? 'Phase de Groupes',
        ]);

        return response()->json($match->load(['opponent', 'events']), 201);
    }

    /**
     * Changer le statut du match (EN_COURS, MI_TEMPS, TERMINE)
     */
    public function updateStatus(Request $request, $id, StandingsCalculationService $standingsService)
    {
        $match = MatchGame::where('asc_code', $request->user()->asc_code)->findOrFail($id);

        $request->validate([
            'statut' => 'required|string|in:A_VENIR,EN_COURS,MI_TEMPS,DEUXIEME_MI_TEMPS,TERMINE',
        ]);

        $dataToUpdate = ['statut' => $request->statut];

        if ($request->statut === 'EN_COURS' && is_null($match->started_at)) {
            $dataToUpdate['started_at'] = now();
        } elseif ($request->statut === 'DEUXIEME_MI_TEMPS' && is_null($match->second_half_started_at)) {
            $dataToUpdate['second_half_started_at'] = now();
        }

        $match->update($dataToUpdate);

        // Si le match est terminé, recalculer le classement
        if ($request->statut === 'TERMINE') {
            $standingsService->updatePouleTeamStats($match);
        }

        return response()->json($match->load(['opponent', 'events']));
    }

    /**
     * Enregistrer un événement (But avec joueur et minute, mi-temps, etc.)
     */
    public function addEvent(Request $request, $id)
    {
        $match = MatchGame::where('asc_code', $request->user()->asc_code)->findOrFail($id);

        $request->validate([
            'type' => 'required|string|in:BUT_ASC,BUT_ADV,MI_TEMPS,CARTON',
            'player_id' => 'nullable|exists:players,id',
            'minute' => 'nullable|integer|min:1|max:120',
            'description' => 'nullable|string',
        ]);

        $event = \App\Models\MatchEvent::create([
            'match_game_id' => $match->id,
            'player_id' => $request->player_id,
            'type' => $request->type,
            'minute' => $request->minute ?? 0,
            'description' => $request->description,
        ]);

        // Incrémenter les scores selon le type (sans utiliser increment() pour s'assurer que le modèle est synchronisé)
        if ($request->type === 'BUT_ASC') {
            $match->score_asc = ($match->score_asc ?? 0) + 1;
            $match->save();
        } elseif ($request->type === 'BUT_ADV') {
            $match->score_adv = ($match->score_adv ?? 0) + 1;
            $match->save();
        }

        return response()->json($match->refresh()->load(['opponent', 'events']), 201);
    }

    /**
     * Le Chargé de Com met à jour le score final
     */
    public function updateScore(Request $request, $id, StandingsCalculationService $standingsService)
    {
        $match = MatchGame::where('asc_code', $request->user()->asc_code)->findOrFail($id);

        $request->validate([
            'score_asc' => 'required|integer|min:0',
            'score_adv' => 'required|integer|min:0',
        ]);

        $match->update([
            'score_asc' => $request->score_asc,
            'score_adv' => $request->score_adv,
            'statut' => 'TERMINE',
        ]);

        $standingsService->updatePouleTeamStats($match);

        return response()->json([
            'message' => 'Score mis à jour et classement recalculé !',
            'match' => $match->load(['opponent', 'events'])
        ]);
    }
}
