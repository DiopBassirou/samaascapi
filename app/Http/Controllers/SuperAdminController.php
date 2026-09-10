<?php

namespace App\Http\Controllers;

use App\Models\Asc;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function getPendingAscs()
    {
        $ascs = Asc::with('president')->where('statut', 'EN_ATTENTE')->get();
        return response()->json($ascs);
    }

    public function getAllAscs()
    {
        // Retourne toutes les ASCs (utile pour voir les codes générés)
        $ascs = Asc::with('president')->orderBy('zone')->orderBy('nom')->get();
        return response()->json($ascs);
    }

    public function createAsc(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
        ]);

        $codeUnique = strtoupper(Str::random(8));
        
        $asc = Asc::create([
            'code_unique' => $codeUnique,
            'nom' => $request->nom,
            'zone' => $request->zone,
            'statut' => 'VALIDEE', // Déjà validée car créée par le super admin
            'is_active' => true,
        ]);

        return response()->json(['message' => 'ASC créée avec succès.', 'asc' => $asc], 201);
    }

    public function approveAsc(Request $request, string $codeUnique)
    {
        $asc = Asc::findOrFail($codeUnique);
        $asc->update([
            'statut' => 'VALIDEE',
            'is_active' => true,
        ]);

        // Promouvoir le créateur au rang de Président
        if ($asc->president_id) {
            User::where('id', $asc->president_id)->update(['role_id' => 2]);
        }

        return response()->json(['message' => 'ASC validée avec succès.']);
    }

    public function rejectAsc(Request $request, string $codeUnique)
    {
        $asc = Asc::findOrFail($codeUnique);
        $asc->update([
            'statut' => 'REJETEE',
            'is_active' => false,
        ]);

        // Le créateur n'est plus rattaché à cette ASC
        if ($asc->president_id) {
            User::where('id', $asc->president_id)->update(['asc_code' => null]);
        }

        return response()->json(['message' => 'ASC rejetée.']);
    }
}
