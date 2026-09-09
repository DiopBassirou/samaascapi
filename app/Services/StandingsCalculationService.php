<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\PouleTeam;
use Illuminate\Support\Facades\DB;

class StandingsCalculationService
{
    /**
     * Calcule et met à jour les statistiques (points, buts) d'une équipe adverse
     * après la saisie d'un score de match.
     */
    public function updatePouleTeamStats(MatchGame $match)
    {
        // Si le match n'est pas terminé ou n'a pas d'adversaire, on ignore
        if ($match->statut !== 'TERMINE' || !$match->poule_team_id) {
            return false;
        }

        return DB::transaction(function () use ($match) {
            $team = PouleTeam::find($match->poule_team_id);
            if (!$team) return false;

            // Logique d'attribution des points
            // L'ASC (nous) vs PouleTeam (l'adversaire)
            $pointsAdversaire = 0;
            $victoireAdv = 0;
            $nulAdv = 0;
            $defaiteAdv = 0;

            if ($match->score_adv > $match->score_asc) {
                // L'adversaire a gagné
                $pointsAdversaire = 3;
                $victoireAdv = 1;
            } elseif ($match->score_adv === $match->score_asc) {
                // Match Nul
                $pointsAdversaire = 1;
                $nulAdv = 1;
            } else {
                // L'adversaire a perdu
                $defaiteAdv = 1;
            }

            // Mise à jour incrémentielle des statistiques de l'adversaire
            $team->increment('joues');
            $team->increment('victoires', $victoireAdv);
            $team->increment('nuls', $nulAdv);
            $team->increment('defaites', $defaiteAdv);
            $team->increment('buts_pour', $match->score_adv);
            $team->increment('buts_contre', $match->score_asc);
            $team->increment('points', $pointsAdversaire);

            return true;
        });
    }
}
