<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\RoleHistory;
use Carbon\Carbon;

class BureauController extends Controller
{
    public function index(Request $request)
    {
        $ascCode = $request->user()->asc_code;
        
        $bureauMembers = User::with('role')
            ->where('asc_code', $ascCode)
            ->whereHas('role', function ($query) {
                $query->where('nom', '!=', 'SUPPORTER');
            })
            ->get();
            
        return response()->json($bureauMembers);
    }
    
    public function assign(Request $request)
    {
        $request->validate([
            'search' => 'required|string', // nom ou telephone
            'role_id' => 'required|exists:roles,id'
        ]);

        $ascCode = $request->user()->asc_code;
        
        $targetUser = User::where('asc_code', $ascCode)
            ->where(function ($q) use ($request) {
                $q->where('telephone', $request->search)
                  ->orWhere('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('prenom', 'like', '%' . $request->search . '%');
            })->first();
            
        if (!$targetUser) {
            return response()->json(['message' => 'Utilisateur non trouvé dans votre ASC.'], 404);
        }

        $newRole = Role::find($request->role_id);
        
        if ($targetUser->role_id === $newRole->id) {
            return response()->json(['message' => 'Cet utilisateur a déjà ce rôle.'], 400);
        }
        
        // Cas spécifique du Président
        if ($newRole->nom === 'PRESIDENT') {
            $supporterRole = Role::where('nom', 'SUPPORTER')->first();
            if ($supporterRole && $request->user()->role->nom === 'PRESIDENT') {
                RoleHistory::where('user_id', $request->user()->id)
                    ->where('role_id', $request->user()->role_id)
                    ->whereNull('date_fin')
                    ->update(['date_fin' => Carbon::now()]);
                    
                $request->user()->update(['role_id' => $supporterRole->id]);
            }
        }

        // Fermer l'ancien historique du target user
        RoleHistory::where('user_id', $targetUser->id)
            ->where('role_id', $targetUser->role_id)
            ->whereNull('date_fin')
            ->update(['date_fin' => Carbon::now()]);
            
        // Assigner le nouveau rôle
        $targetUser->update(['role_id' => $newRole->id]);
        
        // Créer un nouvel historique
        RoleHistory::create([
            'user_id' => $targetUser->id,
            'asc_code' => $ascCode,
            'role_id' => $newRole->id,
            'date_debut' => Carbon::now(),
            'saison' => date('Y') . '-' . (date('Y') + 1)
        ]);

        return response()->json([
            'message' => 'Rôle assigné avec succès.',
            'user' => $targetUser->load('role')
        ]);
    }
}
