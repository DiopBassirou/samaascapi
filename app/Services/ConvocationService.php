<?php

namespace App\Services;

use App\Models\Convocation;

class ConvocationService
{
    /**
     * Convoquer une liste de joueurs pour un match.
     */
    public function submitConvocations(int $matchId, array $playersData)
    {
        $convocations = [];
        
        foreach ($playersData as $data) {
            $convocations[] = Convocation::updateOrCreate(
                ['match_game_id' => $matchId, 'player_id' => $data['player_id']],
                ['statut' => $data['statut']] // TITULAIRE, REMPLACANT, REPOS
            );
        }

        return $convocations;
    }
}
