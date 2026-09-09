<?php

namespace App\Http\Controllers;

use App\Services\AscService;
use Illuminate\Http\Request;

class AscController extends Controller
{
    protected $ascService;

    public function __construct(AscService $ascService)
    {
        $this->ascService = $ascService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
            'recepisse' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $asc = $this->ascService->createAsc($data, $request->user(), $request->file('recepisse'));

        return response()->json([
            'message' => 'Demande de création envoyée. En attente de validation.',
            'asc' => $asc
        ], 201);
    }

    public function join(Request $request)
    {
        $request->validate(['code_unique' => 'required|string']);
        $asc = $this->ascService->joinAsc($request->code_unique, $request->user());
        return response()->json(['message' => 'Vous avez rejoint l\'ASC avec succès !', 'asc' => $asc]);
    }
}
