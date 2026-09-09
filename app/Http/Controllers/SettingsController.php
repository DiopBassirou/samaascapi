<?php

namespace App\Http\Controllers;

use App\Models\Asc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Récupérer les paramètres de l'ASC
     */
    public function index(Request $request)
    {
        $ascCode = $request->user()->asc_code;
        $asc = Asc::where('code_unique', $ascCode)->first();

        if (!$asc) {
            return response()->json(['message' => 'ASC introuvable'], 404);
        }

        return response()->json([
            'nom_asc' => $asc->nom,
            'ville' => $asc->ville,
            'zone' => $asc->zone,
            'cotisation_objectif' => $asc->cotisation_objectif,
            'logo_url' => $asc->logo_path ? Storage::url($asc->logo_path) : null,
        ]);
    }

    /**
     * Mettre à jour les paramètres généraux
     */
    public function update(Request $request)
    {
        $request->validate([
            'nom_asc' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
            'cotisation_objectif' => 'required|numeric|min:0',
        ]);

        $asc = Asc::where('code_unique', $request->user()->asc_code)->firstOrFail();

        $asc->update([
            'nom' => $request->nom_asc,
            'ville' => $request->ville,
            'zone' => $request->zone,
            'cotisation_objectif' => $request->cotisation_objectif,
        ]);

        return response()->json([
            'message' => 'Paramètres mis à jour avec succès.',
            'settings' => [
                'nom_asc' => $asc->nom,
                'ville' => $asc->ville,
                'zone' => $asc->zone,
                'cotisation_objectif' => $asc->cotisation_objectif,
                'logo_url' => $asc->logo_path ? Storage::url($asc->logo_path) : null,
            ]
        ]);
    }

    /**
     * Uploader un nouveau logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $asc = Asc::where('code_unique', $request->user()->asc_code)->firstOrFail();

        // Supprimer l'ancien logo si existant
        if ($asc->logo_path && Storage::disk('public')->exists($asc->logo_path)) {
            Storage::disk('public')->delete($asc->logo_path);
        }

        // Sauvegarder le nouveau
        $path = $request->file('logo')->store('logos', 'public');
        $asc->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo mis à jour.',
            'logo_url' => Storage::url($path),
        ]);
    }
}
