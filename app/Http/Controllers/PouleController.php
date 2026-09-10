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
        $poules = Poule::with('teams')
            ->where('asc_code', $request->user()->asc_code)
            ->get();
            
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
        ]);

        $poule = DB::transaction(function () use ($request) {
            $poule = Poule::create([
                'asc_code'  => $request->user()->asc_code,
                'nom'       => $request->nom,
                'categorie' => $request->categorie ?? 'SENIOR',
            ]);

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
}
