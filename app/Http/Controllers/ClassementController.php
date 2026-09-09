<?php

namespace App\Http\Controllers;

use App\Models\Poule;
use App\Models\MatchGame;
use Illuminate\Http\Request;

class ClassementController extends Controller
{
    public function index(Request $request)
    {
        $ascCode = $request->user()->asc_code;
        
        // Trouver la poule de notre ASC
        $poule = Poule::with('teams')->where('asc_code', $ascCode)->first();
        
        if (!$poule) {
            return response()->json([]);
        }

        // Calculer les stats de Notre ASC à partir des matchs terminés et en cours
        $matchesToCount = MatchGame::where('asc_code', $ascCode)
            ->whereIn('statut', ['TERMINE', 'EN_COURS', 'MI_TEMPS'])
            ->get();

        $ascJoues = $matchesToCount->count();
        $ascVictoires = $matchesToCount->filter(fn($m) => $m->score_asc > $m->score_adv)->count();
        $ascNuls = $matchesToCount->filter(fn($m) => $m->score_asc == $m->score_adv)->count();
        $ascDefaites = $matchesToCount->filter(fn($m) => $m->score_asc < $m->score_adv)->count();
        $ascButsPour = $matchesToCount->sum('score_asc');
        $ascButsContre = $matchesToCount->sum('score_adv');
        $ascDiffButs = $ascButsPour - $ascButsContre;
        $ascPoints = ($ascVictoires * 3) + $ascNuls;

        // Préparer les stats dynamiques (live) des adversaires
        $liveOpponents = [];
        foreach ($matchesToCount as $m) {
            if (in_array($m->statut, ['EN_COURS', 'MI_TEMPS']) && $m->poule_team_id) {
                if (!isset($liveOpponents[$m->poule_team_id])) {
                    $liveOpponents[$m->poule_team_id] = [
                        'j' => 0, 'v' => 0, 'n' => 0, 'd' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0
                    ];
                }
                $o = &$liveOpponents[$m->poule_team_id];
                $o['j'] += 1;
                $o['bp'] += $m->score_adv;
                $o['bc'] += $m->score_asc;
                if ($m->score_adv > $m->score_asc) {
                    $o['v'] += 1;
                    $o['pts'] += 3;
                } elseif ($m->score_adv == $m->score_asc) {
                    $o['n'] += 1;
                    $o['pts'] += 1;
                } else {
                    $o['d'] += 1;
                }
            }
        }

        // Construire le classement avec Notre ASC + les adversaires
        $teams = [];
        
        // Notre ASC
        $teams[] = [
            'name' => 'Notre ASC',
            'j' => $ascJoues,
            'v' => $ascVictoires,
            'n' => $ascNuls,
            'd' => $ascDefaites,
            'bp' => $ascButsPour,
            'bc' => $ascButsContre,
            'db' => ($ascDiffButs >= 0 ? '+' : '') . $ascDiffButs,
            'pts' => $ascPoints,
            'highlight' => true,
        ];

        // Les adversaires de la poule
        foreach ($poule->teams as $team) {
            $live = $liveOpponents[$team->id] ?? null;

            $teamJoues = ($team->joues ?? 0) + ($live ? $live['j'] : 0);
            $teamVictoires = ($team->victoires ?? 0) + ($live ? $live['v'] : 0);
            $teamNuls = ($team->nuls ?? 0) + ($live ? $live['n'] : 0);
            $teamDefaites = ($team->defaites ?? 0) + ($live ? $live['d'] : 0);
            $teamBP = ($team->buts_pour ?? 0) + ($live ? $live['bp'] : 0);
            $teamBC = ($team->buts_contre ?? 0) + ($live ? $live['bc'] : 0);
            $teamPoints = ($team->points ?? 0) + ($live ? $live['pts'] : 0);
            
            $teamDiffButs = $teamBP - $teamBC;

            $teams[] = [
                'id' => $team->id,
                'name' => $team->nom_equipe,
                'j' => $teamJoues,
                'v' => $teamVictoires,
                'n' => $teamNuls,
                'd' => $teamDefaites,
                'bp' => $teamBP,
                'bc' => $teamBC,
                'db' => ($teamDiffButs >= 0 ? '+' : '') . $teamDiffButs,
                'pts' => $teamPoints,
                'highlight' => false,
            ];
        }

        // Trier par points décroissants, puis par diff de buts
        usort($teams, function($a, $b) {
            if ($b['pts'] !== $a['pts']) return $b['pts'] - $a['pts'];
            return intval($b['db']) - intval($a['db']);
        });

        // Ajouter le rang
        foreach ($teams as $i => &$t) {
            $t['rank'] = $i + 1;
        }

        return response()->json($teams);
    }
}
