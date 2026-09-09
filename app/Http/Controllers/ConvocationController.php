<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Services\ConvocationService;
use Illuminate\Http\Request;

class ConvocationController extends Controller
{
    protected $convocationService;

    public function __construct(ConvocationService $convocationService)
    {
        $this->convocationService = $convocationService;
    }

    public function store(Request $request, $matchId)
    {
        // Vérifier que le match appartient bien à l'ASC du Coach
        $match = MatchGame::where('asc_code', $request->user()->asc_code)->findOrFail($matchId);

        $request->validate([
            'players' => 'required|array',
            'players.*.player_id' => 'required|exists:players,id',
            'players.*.statut' => 'required|in:TITULAIRE,REMPLACANT,REPOS',
        ]);

        $convocations = $this->convocationService->submitConvocations($match->id, $request->players);

        return response()->json([
            'message' => 'Effectif du match validé avec succès !',
            'convocations' => $convocations
        ]);
    }
}
