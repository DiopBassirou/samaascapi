<?php

namespace App\Services;

use App\Models\Poule;
use Illuminate\Support\Collection;

class PredictionService
{
    /**
     * Calcule le classement exact des équipes d'une poule selon les règles :
     * 1. Points
     * 2. Goal Average (buts_pour - buts_contre)
     * 3. Buts Marqués (buts_pour)
     */
    private function getRankedTeams(Poule $poule): Collection
    {
        return $poule->teams->map(function ($team) {
            $team->goal_difference = $team->buts_pour - $team->buts_contre;
            return $team;
        })->sort(function ($a, $b) {
            if ($a->points !== $b->points) {
                return $b->points <=> $a->points;
            }
            if ($a->goal_difference !== $b->goal_difference) {
                return $b->goal_difference <=> $a->goal_difference;
            }
            return $b->buts_pour <=> $a->buts_pour;
        })->values();
    }

    /**
     * Génère la prédiction des 1/4 de finale pour la catégorie SENIOR.
     */
    public function getQuarterFinalsPrediction()
    {
        $poules = Poule::with('teams')->where('categorie', 'SENIOR')->get();
        
        // Séparer la poule de 5 et les poules de 4
        $poule5 = null;
        $poules4 = [];

        foreach ($poules as $poule) {
            if ($poule->teams->count() === 5) {
                $poule5 = $poule;
            } else if ($poule->teams->count() === 4) {
                $poules4[] = $poule;
            }
        }

        if (!$poule5 || count($poules4) !== 2) {
            return [
                'error' => 'La structure des poules n\'est pas valide pour cette prédiction (nécessite 1 poule de 5 et 2 poules de 4).'
            ];
        }

        // Classement Poule 5
        $rankedPoule5 = $this->getRankedTeams($poule5);
        $poule5_1st = $rankedPoule5->get(0);
        $poule5_2nd = $rankedPoule5->get(1);
        $poule5_3rd = $rankedPoule5->get(2);

        // Classement Poules 4
        $rankedPoule4_1 = $this->getRankedTeams($poules4[0]);
        $rankedPoule4_2 = $this->getRankedTeams($poules4[1]);

        $poule4_1_1st = $rankedPoule4_1->get(0);
        $poule4_1_2nd = $rankedPoule4_1->get(1);
        $poule4_1_3rd = $rankedPoule4_1->get(2);

        $poule4_2_1st = $rankedPoule4_2->get(0);
        $poule4_2_2nd = $rankedPoule4_2->get(1);
        $poule4_2_3rd = $rankedPoule4_2->get(2);

        // Déterminer le meilleur 1er des poules de 4
        $firsts = collect([$poule4_1_1st, $poule4_2_1st])->sort(function ($a, $b) {
            if ($a->points !== $b->points) return $b->points <=> $a->points;
            if ($a->goal_difference !== $b->goal_difference) return $b->goal_difference <=> $a->goal_difference;
            return $b->buts_pour <=> $a->buts_pour;
        })->values();

        $best_1st_p4 = $firsts->get(0);
        $second_best_1st_p4 = $firsts->get(1);

        // Déterminer le meilleur 3ème des poules de 4
        $thirds = collect([$poule4_1_3rd, $poule4_2_3rd])->sort(function ($a, $b) {
            if ($a->points !== $b->points) return $b->points <=> $a->points;
            if ($a->goal_difference !== $b->goal_difference) return $b->goal_difference <=> $a->goal_difference;
            return $b->buts_pour <=> $a->buts_pour;
        })->values();

        $best_3rd_p4 = $thirds->get(0);

        // MATCHS SELON LES REGLES
        // Match 1 : 1er du poule 5 vs meilleur 3ème du poule de 4
        // Match 2 : Meilleur 1er du poule de 4 vs 3ème du poule de 5
        // Match 3 : 2ème meilleur 1er des poules de 4 vs 2ème du poule de 5
        // Match 4 : 2ème poule de 4 vs 2ème poule de 4

        $match1 = [
            'team_a' => $poule5_1st,
            'team_a_qualification' => '1er ' . $poule5->nom,
            'team_b' => $best_3rd_p4,
            'team_b_qualification' => 'Meilleur 3ème (Poules 4)',
            'label' => 'Quart de Finale 1'
        ];

        $match2 = [
            'team_a' => $best_1st_p4,
            'team_a_qualification' => 'Meilleur 1er (Poules 4)',
            'team_b' => $poule5_3rd,
            'team_b_qualification' => '3ème ' . $poule5->nom,
            'label' => 'Quart de Finale 2'
        ];

        $match3 = [
            'team_a' => $second_best_1st_p4,
            'team_a_qualification' => '2ème meilleur 1er (Poules 4)',
            'team_b' => $poule5_2nd,
            'team_b_qualification' => '2ème ' . $poule5->nom,
            'label' => 'Quart de Finale 3'
        ];

        $match4 = [
            'team_a' => $poule4_1_2nd,
            'team_a_qualification' => '2ème ' . $poules4[0]->nom,
            'team_b' => $poule4_2_2nd,
            'team_b_qualification' => '2ème ' . $poules4[1]->nom,
            'label' => 'Quart de Finale 4'
        ];

        return [
            'matchs' => [
                $match1,
                $match2,
                $match3,
                $match4
            ]
        ];
    }
}
