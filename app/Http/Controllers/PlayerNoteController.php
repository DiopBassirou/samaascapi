<?php

namespace App\Http\Controllers;

use App\Services\RatingService;
use Illuminate\Http\Request;

class PlayerNoteController extends Controller
{
    protected $ratingService;

    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * Un supporter vote pour un joueur.
     */
    public function store(Request $request, $matchId, $playerId)
    {
        $request->validate([
            'note' => 'required|integer|min:1|max:5',
        ]);

        // On utilise l'ID de l'utilisateur pour générer le hash anonyme
        $userId = $request->user()->id;

        $vote = $this->ratingService->submitVote(
            $matchId,
            $playerId,
            $request->note,
            $userId
        );

        return response()->json([
            'message' => 'Votre note anonyme a bien été prise en compte !',
            'vote' => $vote
        ], 201);
    }

    /**
     * Consulter la moyenne d'un joueur pour un match.
     */
    public function getAverage(Request $request, $matchId, $playerId)
    {
        $average = $this->ratingService->getPlayerAverageNoteForMatch($matchId, $playerId);
        
        return response()->json([
            'player_id' => $playerId,
            'match_id' => $matchId,
            'average_note' => $average
        ]);
    }
}
