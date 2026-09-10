<?php

namespace App\Http\Controllers;

use App\Models\Asc;
use App\Models\MatchGame;
use App\Models\Poule;
use Illuminate\Http\Request;

class ClassementController extends Controller
{
    /**
     * Retourne le classement calculé dynamiquement depuis les poules et matchs.
     * La poule d'une équipe est définie dans la table `poules` (avec categorie SENIOR/CADET).
     *
     * Structure de sortie: Zone -> Categorie -> Poule -> [équipes triées]
     */
    public function index(Request $request)
    {
        $userAscCode = $request->user() ? $request->user()->asc_code : null;

        $baseStats = [
            'j' => 0, 'v' => 0, 'n' => 0, 'd' => 0,
            'bp' => 0, 'bc' => 0, 'db' => 0, 'db_formatted' => '0', 'pts' => 0
        ];

        $classements = []; // [zone][cat][poule][ascCodeOrKey] => stats

        // Étape 1 : Initialiser les poules et équipes définies en BDD
        $poules = Poule::with('teams')->get();
        foreach ($poules as $pouleObj) {
            $zone      = $pouleObj->zone ?? 'Zone 2A';
            $cat       = $pouleObj->categorie ?? 'SENIOR';
            $pouleName = $pouleObj->nom;

            foreach ($pouleObj->teams as $team) {
                $code = $team->asc_code ?: $team->nom_equipe;
                $classements[$zone][$cat][$pouleName][$code] = array_merge([
                    'code_unique' => $code,
                    'name'        => $team->nom_equipe,
                    'highlight'   => ($userAscCode && ($userAscCode === $team->asc_code)),
                ], $baseStats);
            }
        }

        // Étape 2 : Identifier et ajouter les équipes des matchs n'existant pas encore dans les poules
        $allMatches = MatchGame::with('asc')->get();

        foreach ($allMatches as $match) {
            if (!$match->asc) continue;

            $zone  = $match->asc->zone ?? 'Zone Non Définie';
            $cat   = $match->categorie ?? 'SENIOR';
            $poule = $match->phase ?? 'Phase de Groupes';

            $homeCode = $match->asc_code;
            $homeName = $match->asc->nom;
            $awayCode = $match->adversaire_code ?: $match->adversaire_nom;
            $awayName = $match->adversaire_nom;

            if ($homeCode && !isset($classements[$zone][$cat][$poule][$homeCode])) {
                $classements[$zone][$cat][$poule][$homeCode] = array_merge([
                    'code_unique' => $homeCode,
                    'name'        => $homeName,
                    'highlight'   => ($userAscCode === $homeCode),
                ], $baseStats);
            }

            if ($awayCode && !isset($classements[$zone][$cat][$poule][$awayCode])) {
                $awayAsc = Asc::find($awayCode);
                $classements[$zone][$cat][$poule][$awayCode] = array_merge([
                    'code_unique' => $awayCode,
                    'name'        => $awayAsc ? $awayAsc->nom : $awayName,
                    'highlight'   => ($userAscCode === $awayCode),
                ], $baseStats);
            }
        }

        // Étape 2 : Calculer les stats pour les matchs TERMINÉS uniquement
        $terminatedMatches = $allMatches->where('statut', 'TERMINE');

        foreach ($terminatedMatches as $match) {
            if (!$match->asc) continue;

            $zone  = $match->asc->zone ?? 'Zone Non Définie';
            $cat   = $match->categorie ?? 'SENIOR';
            $poule = $match->phase ?? 'Phase de Groupes';

            $homeCode = $match->asc_code;
            $awayCode = $match->adversaire_code;
            $scoreH   = $match->score_asc ?? 0;
            $scoreA   = $match->score_adv ?? 0;

            // Mise à jour équipe domicile
            if (isset($classements[$zone][$cat][$poule][$homeCode])) {
                $t = &$classements[$zone][$cat][$poule][$homeCode];
                $t['j']++;
                $t['bp'] += $scoreH;
                $t['bc'] += $scoreA;
                if ($scoreH > $scoreA)      { $t['v']++; $t['pts'] += 3; }
                elseif ($scoreH === $scoreA) { $t['n']++; $t['pts'] += 1; }
                else                        { $t['d']++; }
                $t['db']          = $t['bp'] - $t['bc'];
                $t['db_formatted'] = ($t['db'] > 0 ? '+' : '') . $t['db'];
                unset($t);
            }

            // Mise à jour équipe adverse
            if ($awayCode && isset($classements[$zone][$cat][$poule][$awayCode])) {
                $t = &$classements[$zone][$cat][$poule][$awayCode];
                $t['j']++;
                $t['bp'] += $scoreA;
                $t['bc'] += $scoreH;
                if ($scoreA > $scoreH)      { $t['v']++; $t['pts'] += 3; }
                elseif ($scoreA === $scoreH) { $t['n']++; $t['pts'] += 1; }
                else                        { $t['d']++; }
                $t['db']          = $t['bp'] - $t['bc'];
                $t['db_formatted'] = ($t['db'] > 0 ? '+' : '') . $t['db'];
                unset($t);
            }
        }

        // Étape 3 : Trier et formater la réponse
        $finalClassements = [];

        foreach ($classements as $zone => $categories) {
            foreach ($categories as $cat => $poules) {
                foreach ($poules as $pouleName => $teamsDict) {
                    $teamsList = array_values($teamsDict);

                    // Tri : Points DESC, Diff Buts DESC, Buts Pour DESC
                    usort($teamsList, function ($a, $b) {
                        if ($b['pts'] !== $a['pts'])  return $b['pts'] - $a['pts'];
                        if ($b['db']  !== $a['db'])   return intval($b['db']) - intval($a['db']);
                        return intval($b['bp']) - intval($a['bp']);
                    });

                    foreach ($teamsList as $i => &$t) {
                        $t['rank'] = $i + 1;
                    }
                    unset($t);

                    $finalClassements[$zone][$cat][$pouleName] = $teamsList;
                }
            }
        }

        return response()->json($finalClassements);
    }
}




