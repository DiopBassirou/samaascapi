<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    protected $playerService;

    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
    }

    public function index(Request $request)
    {
        $players = $this->playerService->getPlayersByAsc($request->user()->asc_code);
        return response()->json($players);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'poste' => 'required|string|max:100',
        ]);

        $player = $this->playerService->createPlayer($data, $request->user()->asc_code);
        return response()->json($player, 201);
    }

    public function destroy(Request $request, $id)
    {
        $player = Player::where('asc_code', $request->user()->asc_code)->findOrFail($id);
        $this->playerService->deactivatePlayer($player);

        return response()->json(['message' => 'Joueur retiré de l\'effectif actif.']);
    }
}
