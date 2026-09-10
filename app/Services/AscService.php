<?php

namespace App\Services;

use App\Models\Asc;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AscService
{
    public function createAsc(array $data, User $user, ?UploadedFile $recepisse)
    {
        return DB::transaction(function () use ($data, $user, $recepisse) {
            $nomPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['nom']), 0, 3));
            $villePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['ville']), 0, 2));
            
            $codeUnique = $nomPrefix . '-' . $villePrefix . '-' . rand(1000, 9999);
            
            while (Asc::where('code_unique', $codeUnique)->exists()) {
                $codeUnique = $nomPrefix . '-' . $villePrefix . '-' . rand(1000, 9999);
            }

            $recepissePath = null;
            if ($recepisse) {
                $recepissePath = $recepisse->store('recepisses', 'public');
            }

            $asc = Asc::create([
                'code_unique' => $codeUnique,
                'nom' => $data['nom'],
                'ville' => $data['ville'],
                'zone' => $data['zone'],
                'statut' => 'EN_ATTENTE',
                'is_active' => false,
                'president_id' => $user->id,
                'recepisse_path' => $recepissePath,
            ]);

            // L'utilisateur rejoint l'ASC mais reste Supporter tant que ce n'est pas validé
            $user->update([
                'asc_code' => $asc->code_unique,
                'role_id' => 1,
            ]);

            return $asc;
        });
    }

    public function joinAsc(string $codeUnique, User $user)
    {
        $asc = Asc::where('code_unique', $codeUnique)
                  ->orWhere('nom', $codeUnique)
                  ->first();
        if (!$asc) {
            throw ValidationException::withMessages(['code_unique' => ['Cette équipe (ou code ASC) n\'existe pas.']]);
        }
        if ($asc->statut !== 'VALIDEE') {
            throw ValidationException::withMessages(['code_unique' => ['Cette ASC n\'a pas encore été validée.']]);
        }
        $user->update(['asc_code' => $asc->code_unique, 'role_id' => 1]);
        return $asc;
    }
}
