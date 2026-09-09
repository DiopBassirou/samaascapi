<?php

namespace App\Services;

use App\Models\PlayerNote;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RatingService
{
    /**
     * Enregistre un vote anonyme d'un supporter.
     */
    public function submitVote(int $matchId, int $playerId, int $note, string $userIdIdentifier)
    {
        // 1. Création d'un hash unique pour garantir l'anonymat tout en empêchant le double vote
        // Le hash combine l'ID de l'utilisateur, l'ID du match et une clé secrète
        $voteHash = hash('sha256', $userIdIdentifier . '_' . $matchId . '_' . config('app.key'));

        // 2. Vérifier si le supporter a déjà voté pour ce joueur dans ce match
        $existingVote = PlayerNote::where('match_game_id', $matchId)
            ->where('player_id', $playerId)
            ->where('vote_hash', $voteHash)
            ->first();

        if ($existingVote) {
            throw ValidationException::withMessages([
                'vote' => ['Vous avez déjà noté ce joueur pour ce match.'],
            ]);
        }

        // 3. Enregistrer la note
        return PlayerNote::create([
            'match_game_id' => $matchId,
            'player_id' => $playerId,
            'vote_hash' => $voteHash,
            'note' => $note,
        ]);
    }

    /**
     * Calcule la moyenne des notes d'un joueur pour un match spécifique.
     */
    public function getPlayerAverageNoteForMatch(int $matchId, int $playerId)
    {
        $average = PlayerNote::where('match_game_id', $matchId)
            ->where('player_id', $playerId)
            ->avg('note');

        return round($average, 1);
    }
}
