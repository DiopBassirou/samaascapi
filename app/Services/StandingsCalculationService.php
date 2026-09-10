<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\Asc;
use Illuminate\Support\Facades\DB;

class StandingsCalculationService
{
    /**
     * Calcule et met à jour les statistiques (points, buts) des deux ASCs
     * après la saisie d'un score de match.
     */
    public function updatePouleTeamStats(MatchGame $match)
    {
        // Si le match n'est pas terminé, on ignore
        if ($match->statut !== 'TERMINE') {
            return false;
        }

        return DB::transaction(function () use ($match) {
            // L'ASC qui a créé le match (Home)
            $homeTeam = Asc::find($match->asc_code);
            
            // L'adversaire (Away) s'il est enregistré comme ASC
            $awayTeam = null;
            if ($match->adversaire_code) {
                $awayTeam = Asc::find($match->adversaire_code);
            }

            // Logique d'attribution des points
            $pointsHome = 0; $victoireHome = 0; $nulHome = 0; $defaiteHome = 0;
            $pointsAway = 0; $victoireAway = 0; $nulAway = 0; $defaiteAway = 0;

            if ($match->score_asc > $match->score_adv) {
                // Home gagne
                $pointsHome = 3; $victoireHome = 1;
                $defaiteAway = 1;
            } elseif ($match->score_asc === $match->score_adv) {
                // Nul
                $pointsHome = 1; $nulHome = 1;
                $pointsAway = 1; $nulAway = 1;
            } else {
                // Away gagne
                $defaiteHome = 1;
                $pointsAway = 3; $victoireAway = 1;
            }

            if ($homeTeam) {
                $homeTeam->increment('matchs_joues');
                $homeTeam->increment('victoires', $victoireHome);
                $homeTeam->increment('nuls', $nulHome);
                $homeTeam->increment('defaites', $defaiteHome);
                $homeTeam->increment('buts_pour', $match->score_asc);
                $homeTeam->increment('buts_contre', $match->score_adv);
                $homeTeam->increment('points', $pointsHome);
            }

            if ($awayTeam) {
                $awayTeam->increment('matchs_joues');
                $awayTeam->increment('victoires', $victoireAway);
                $awayTeam->increment('nuls', $nulAway);
                $awayTeam->increment('defaites', $defaiteAway);
                $awayTeam->increment('buts_pour', $match->score_adv);
                $awayTeam->increment('buts_contre', $match->score_asc);
                $awayTeam->increment('points', $pointsAway);
            }

            return true;
        });
    }
}
