<?php

namespace App\Http\Controllers;

use App\Models\Asc;
use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function getPendingAscs()
    {
        $ascs = Asc::with('president')->where('statut', 'EN_ATTENTE')->get();
        return response()->json($ascs);
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
