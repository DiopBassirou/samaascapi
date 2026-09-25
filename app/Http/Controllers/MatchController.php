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
        $ascCode = $request->user()?->asc_code ?? $request->query('asc_code');

        if (!$ascCode) {
            return response()->json([]);
        }

        $pouleTeamIds = \App\Models\PouleTeam::where('asc_code', $ascCode)->pluck('id')->toArray();

        // On charge l'ASC de l'adversaire si c'est une équipe de poule ou via adversaire_code
        $matches = MatchGame::with(['asc', 'opponent.asc', 'events.player'])
            ->where(function($query) use ($ascCode, $pouleTeamIds) {
                $query->where('asc_code', $ascCode)
                      ->orWhere('adversaire_code', $ascCode)
                      ->orWhereIn('poule_team_id', $pouleTeamIds);
            })
            ->orderBy('date_match', 'asc')
            ->get();

        $formattedMatches = $matches->map(function ($match) use ($ascCode) {
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
                
                // Si l'adversaire a une poule, on l'utilise pour la phase
                if ($match->opponent->poule) {
                    $match->phase = $match->opponent->poule->nom;
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

            // Si l'ASC de l'utilisateur est l'adversaire (trouvé via poule_team_id),
            // inverser les noms pour que l'utilisateur voie son équipe en Team A
            if ($match->asc_code !== $ascCode && $match->opponent && $match->opponent->asc_code === $ascCode) {
                // Swap
                [$teamAName, $teamBName] = [$teamBName, $teamAName];
                [$teamALogo, $teamBLogo] = [$teamBLogo, $teamALogo];
                // Swap scores aussi
                $match->setAttribute('score_asc_display', $match->score_adv);
                $match->setAttribute('score_adv_display', $match->score_asc);
            } else {
                $match->setAttribute('score_asc_display', $match->score_asc);
                $match->setAttribute('score_adv_display', $match->score_adv);
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
     * Le Chargé de Com met à jour un match existant
     */
    public function update(Request $request, $id)
    {
        $match = MatchGame::where('asc_code', $request->user()->asc_code)->findOrFail($id);

        $request->validate([
            'date_match' => 'nullable|date',
            'lieu' => 'nullable|string|max:255',
            'phase' => 'nullable|string|max:255',
            'poule_team_id' => 'nullable|integer',
            'poule_team_b_id' => 'nullable|exists:poule_teams,id',
            'statut' => 'nullable|string|max:50',
        ]);

        if ($request->has('date_match')) $match->date_match = $request->date_match;
        if ($request->has('lieu')) $match->lieu = $request->lieu;
        if ($request->has('phase')) $match->phase = $request->phase;
        
        // Handling team B update
        if ($request->has('poule_team_b_id') && $request->poule_team_b_id) {
            $match->poule_team_id = $request->poule_team_b_id;
        } elseif ($request->has('poule_team_id')) {
            $match->poule_team_id = $request->poule_team_id;
        }
        
        if ($request->has('statut')) $match->statut = $request->statut;

        $match->save();

        return response()->json($match->load(['opponent', 'events']));
    }

    /**
     * Changer le statut du match (EN_COURS, MI_TEMPS, TERMINE)
     */
    public function updateStatus(Request $request, $id, StandingsCalculationService $standingsService, \App\Services\PushNotificationService $pushService)
    {
        $userAscCode = $request->user()->asc_code;
        $match = MatchGame::with(['asc', 'opponent'])
            ->where(function ($query) use ($userAscCode) {
                $query->where('asc_code', $userAscCode)
                      ->orWhereHas('opponent', function ($q) use ($userAscCode) {
                          $q->where('asc_code', $userAscCode);
                      });
            })
            ->findOrFail($id);

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

        if ($request->statut === 'EN_COURS' && isset($dataToUpdate['started_at'])) {
            $pushService->sendToAsc($match->asc_code, "⚽ Coup d'envoi !", "Le match de " . ($match->asc->nom ?? 'l\'ASC') . " vient de commencer.");
        } elseif ($request->statut === 'TERMINE') {
            $pushService->sendToAsc($match->asc_code, "🏁 Fin du match", "Score final : " . ($match->asc->nom ?? 'ASC') . " {$match->score_asc} - {$match->score_adv} Adversaire");
        }

        return response()->json($match->load(['opponent', 'events']));
    }

    /**
     * Enregistrer un événement (But avec joueur et minute, mi-temps, etc.)
     */
    public function addEvent(Request $request, $id, \App\Services\PushNotificationService $pushService)
    {
        $userAscCode = $request->user()->asc_code;
        $match = MatchGame::with(['asc', 'opponent'])
            ->where(function ($query) use ($userAscCode) {
                $query->where('asc_code', $userAscCode)
                      ->orWhereHas('opponent', function ($q) use ($userAscCode) {
                          $q->where('asc_code', $userAscCode);
                      });
            })
            ->findOrFail($id);

        $request->validate([
            'type' => 'required|string|in:BUT_ASC,BUT_ADV,MI_TEMPS,CARTON',
            'player_id' => 'nullable|exists:players,id',
            'player_name' => 'nullable|string|max:255',
            'minute' => 'nullable|integer|min:1|max:120',
            'description' => 'nullable|string',
        ]);

        $isOpponent = ($match->asc_code !== $userAscCode);
        $type = $request->type;
        if ($isOpponent) {
            if ($type === 'BUT_ASC') $type = 'BUT_ADV';
            elseif ($type === 'BUT_ADV') $type = 'BUT_ASC';
        }

        $event = \App\Models\MatchEvent::create([
            'match_game_id' => $match->id,
            'player_id' => $request->player_id,
            'player_name' => $request->player_name,
            'type' => $type,
            'minute' => $request->minute ?? 0,
            'description' => $request->description,
        ]);

        if ($type === 'BUT_ASC') {
            $match->score_asc = ($match->score_asc ?? 0) + 1;
            $match->save();
            $pushService->sendToAsc(
                $match->asc_code, 
                "⚽ BUT pour " . ($match->asc->nom ?? 'l\'équipe domicile') . " !", 
                "Nouveau score : {$match->score_asc} - {$match->score_adv}"
            );
        } elseif ($type === 'BUT_ADV') {
            $match->score_adv = ($match->score_adv ?? 0) + 1;
            $match->save();
            $pushService->sendToAsc(
                $match->asc_code, 
                "⚠️ L'adversaire a marqué", 
                "Nouveau score : {$match->score_asc} - {$match->score_adv}"
            );
        }

        return response()->json($match->refresh()->load(['opponent', 'events']), 201);
    }

    /**
     * Le Chargé de Com met à jour le score final
     */
    public function updateScore(Request $request, $id, StandingsCalculationService $standingsService)
    {
        $userAscCode = $request->user()->asc_code;
        $match = MatchGame::with(['asc', 'opponent'])
            ->where(function ($query) use ($userAscCode) {
                $query->where('asc_code', $userAscCode)
                      ->orWhereHas('opponent', function ($q) use ($userAscCode) {
                          $q->where('asc_code', $userAscCode);
                      });
            })
            ->findOrFail($id);

        $request->validate([
            'score_asc' => 'required|integer|min:0',
            'score_adv' => 'required|integer|min:0',
        ]);

        $isOpponent = ($match->asc_code !== $userAscCode);
        $scoreAsc = $isOpponent ? $request->score_adv : $request->score_asc;
        $scoreAdv = $isOpponent ? $request->score_asc : $request->score_adv;

        $match->update([
            'score_asc' => $scoreAsc,
            'score_adv' => $scoreAdv,
            'statut' => 'TERMINE',
        ]);

        $standingsService->updatePouleTeamStats($match);

        return response()->json([
            'message' => 'Score mis à jour et classement recalculé !',
            'match' => $match->load(['opponent', 'events'])
        ]);
    }
}
