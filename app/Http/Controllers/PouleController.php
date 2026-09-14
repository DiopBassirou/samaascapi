<?php

namespace App\Http\Controllers;

use App\Models\Poule;
use App\Models\PouleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PouleController extends Controller
{
    /**
     * Liste les poules de l'ASC.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role_id === 5) {
            // Super admin voit toutes les poules
            $poules = Poule::with('teams')->get();
        } else {
            // ASC voit les poules de sa zone ou créées par elle-même
            $asc = $user->asc;
            $zone = $asc ? $asc->zone : null;
            $poules = Poule::with('teams')
                ->where('asc_code', $user->asc_code)
                ->orWhere('zone', $zone)
                ->get();
        }
            
        return response()->json($poules);
    }

    /**
     * Crée une nouvelle poule (ex: Poule A) et y ajoute des équipes.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom'      => 'required|string|max:255',
            'equipes'  => 'required|array',
            'equipes.*'=> 'required|string|max:255',
            'categorie'=> 'nullable|in:CADET,SENIOR',
            'zone'     => 'required|string|max:255',
        ]);

        $poule = DB::transaction(function () use ($request) {
            $poule = Poule::create([
                'asc_code'  => $request->user()->role_id === 5 ? null : $request->user()->asc_code,
                'nom'       => $request->nom,
                'categorie' => $request->categorie ?? 'SENIOR',
                'zone'      => $request->zone,
            ]);

            // Ajouter automatiquement l'ASC qui crée la poule (seulement si ce n'est pas le super admin)
            if ($request->user()->role_id !== 5) {
                $asc = $request->user()->asc;
                if ($asc) {
                    PouleTeam::create([
                        'poule_id'   => $poule->id,
                        'nom_equipe' => 'NOTRE ASC (' . $asc->nom . ')',
                        'points'     => $asc->points ?? 0,
                        'joues'      => $asc->matchs_joues ?? 0,
                        'victoires'  => $asc->victoires ?? 0,
                        'nuls'       => $asc->nuls ?? 0,
                        'defaites'   => $asc->defaites ?? 0,
                        'buts_pour'  => $asc->buts_pour ?? 0,
                        'buts_contre'=> $asc->buts_contre ?? 0,
                    ]);
                }
            }

            foreach ($request->equipes as $nomEquipe) {
                PouleTeam::create([
                    'poule_id'   => $poule->id,
                    'nom_equipe' => $nomEquipe,
                ]);
            }

            return $poule->load('teams');
        });

        return response()->json([
            'message' => 'Poule et équipes créées avec succès !',
            'poule' => $poule
        ], 201);
    }

    /**
     * Saisir le résultat d'un match entre deux autres équipes de la poule.
     */
    public function addOtherMatchResult(Request $request)
    {
        $request->validate([
            'team1_id' => 'required|exists:poule_teams,id',
            'team2_id' => 'required|exists:poule_teams,id|different:team1_id',
            'score1' => 'required|integer|min:0',
            'score2' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $team1 = PouleTeam::findOrFail($request->team1_id);
            $team2 = PouleTeam::findOrFail($request->team2_id);

            // Calcul des points pour team1
            $points1 = 0; $vic1 = 0; $nul1 = 0; $def1 = 0;
            // Calcul des points pour team2
            $points2 = 0; $vic2 = 0; $nul2 = 0; $def2 = 0;

            if ($request->score1 > $request->score2) {
                $points1 = 3; $vic1 = 1;
                $def2 = 1;
            } elseif ($request->score1 === $request->score2) {
                $points1 = 1; $nul1 = 1;
                $points2 = 1; $nul2 = 1;
            } else {
                $points2 = 3; $vic2 = 1;
                $def1 = 1;
            }

            // Maj Team 1
            $team1->increment('joues');
            $team1->increment('victoires', $vic1);
            $team1->increment('nuls', $nul1);
            $team1->increment('defaites', $def1);
            $team1->increment('buts_pour', $request->score1);
            $team1->increment('buts_contre', $request->score2);
            $team1->increment('points', $points1);

            // Maj Team 2
            $team2->increment('joues');
            $team2->increment('victoires', $vic2);
            $team2->increment('nuls', $nul2);
            $team2->increment('defaites', $def2);
            $team2->increment('buts_pour', $request->score2);
            $team2->increment('buts_contre', $request->score1);
            $team2->increment('points', $points2);

            return response()->json(['message' => 'Résultat enregistré et classement mis à jour avec succès.']);
        });
    }

    /**
     * Retourne les prédictions des quarts de finale
     */
    public function getQuarterFinalsPrediction(\App\Services\PredictionService $predictionService)
    {
        return response()->json($predictionService->getQuarterFinalsPrediction());
    }

    /**
     * Simule les matchs restants et retourne le classement et les prédictions
     * sans affecter la base de données.
     */
    public function simulate(Request $request, \App\Services\PredictionService $predictionService)
    {
        $customMatches = $request->input('matches', []);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($customMatches as $custom) {
                $match = \App\Models\MatchGame::find($custom['id']);
                if ($match && in_array($match->statut, ['A_VENIR', 'EN_COURS', 'MI_TEMPS', 'DEUXIEME_MI_TEMPS'])) {
                    // Trouver les PouleTeam
                    $teamA = \App\Models\PouleTeam::where('asc_code', $match->asc_code)->where('categorie', $match->categorie)->first();
                    if (!$teamA) {
                        $teamA = \App\Models\PouleTeam::where('asc_code', $match->asc_code)->first();
                    }
                    $teamB = \App\Models\PouleTeam::find($match->poule_team_id);

                    if ($teamA && $teamB) {
                        $scoreA = (int) $custom['score_asc'];
                        $scoreB = (int) $custom['score_adv'];

                        $pointsA = 0; $vicA = 0; $nulA = 0; $defA = 0;
                        $pointsB = 0; $vicB = 0; $nulB = 0; $defB = 0;

                        if ($scoreA > $scoreB) {
                            $pointsA = 3; $vicA = 1; $defB = 1;
                        } elseif ($scoreA === $scoreB) {
                            $pointsA = 1; $nulA = 1; $pointsB = 1; $nulB = 1;
                        } else {
                            $pointsB = 3; $vicB = 1; $defA = 1;
                        }

                        $teamA->increment('joues');
                        $teamA->increment('victoires', $vicA);
                        $teamA->increment('nuls', $nulA);
                        $teamA->increment('defaites', $defA);
                        $teamA->increment('buts_pour', $scoreA);
                        $teamA->increment('buts_contre', $scoreB);
                        $teamA->increment('points', $pointsA);

                        $teamB->increment('joues');
                        $teamB->increment('victoires', $vicB);
                        $teamB->increment('nuls', $nulB);
                        $teamB->increment('defaites', $defB);
                        $teamB->increment('buts_pour', $scoreB);
                        $teamB->increment('buts_contre', $scoreA);
                        $teamB->increment('points', $pointsB);
                    }
                }
            }
            
            // Get predictions
            $predictions = $predictionService->getQuarterFinalsPrediction();
            
            // Get updated poules standings
            $poules = \App\Models\Poule::with('teams')->get();
            $poules->transform(function($poule) {
                // Tri strict comme dans PredictionService
                $sortedTeams = $poule->teams->map(function ($t) {
                    $t->goal_difference = $t->buts_pour - $t->buts_contre;
                    return $t;
                })->sort(function ($a, $b) {
                    if ($a->points !== $b->points) return $b->points <=> $a->points;
                    if ($a->goal_difference !== $b->goal_difference) return $b->goal_difference <=> $a->goal_difference;
                    return $b->buts_pour <=> $a->buts_pour;
                })->values();
                $poule->setRelation('teams', $sortedTeams);
                return $poule;
            });

            \Illuminate\Support\Facades\DB::rollBack();

            return response()->json([
                'predictions' => $predictions,
                'poules' => $poules
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
