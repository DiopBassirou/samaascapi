<?php

namespace App\Services;

use App\Models\Player;

class PlayerService
{
    /**
     * Récupère l'effectif d'une ASC avec le statut de convocation du dernier match.
     */
    public function getPlayersByAsc(string $ascCode)
    {
        // Trouver le dernier match (EN_COURS ou le plus récent TERMINE)
        $latestMatch = \App\Models\MatchGame::where('asc_code', $ascCode)
            ->whereIn('statut', ['EN_COURS', 'MI_TEMPS', 'TERMINE', 'A_VENIR'])
            ->orderByRaw("FIELD(statut, 'EN_COURS', 'MI_TEMPS', 'A_VENIR', 'TERMINE')")
            ->orderBy('date_match', 'desc')
            ->first();

        $players = Player::where('asc_code', $ascCode)
            ->where('is_active', true)
            ->orderBy('nom', 'asc')
            ->get();

        if ($latestMatch) {
            $convocations = \App\Models\Convocation::where('match_game_id', $latestMatch->id)
                ->pluck('statut', 'player_id');

            $players = $players->map(function ($player) use ($convocations) {
                $player->statut_convocation = $convocations[$player->id] ?? null;
                return $player;
            });
        }

        return $players;
    }

    /**
     * Recrute un nouveau joueur.
     */
    public function createPlayer(array $data, string $ascCode)
    {
        return Player::create([
            'asc_code' => $ascCode,
            'nom' => $data['nom'],
            'poste' => $data['poste'],
            'is_active' => true,
        ]);
    }

    /**
     * Désactive un joueur (Démission / Départ).
     * On ne supprime pas (Soft Delete maison) pour garder les stats.
     */
    public function deactivatePlayer(Player $player)
    {
        $player->update(['is_active' => false]);
    }
}
