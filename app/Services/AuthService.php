<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Enregistre un nouvel utilisateur.
     */
    public function registerUser(array $data)
    {
        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'password' => Hash::make($data['password']),
            // Par défaut, le rôle est 'Supporter' (1) et l'ASC est null (à rejoindre)
            'role_id' => 1
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Connecte un utilisateur.
     */
    public function loginUser(string $telephone, string $password)
    {
        $user = User::where('telephone', $telephone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'telephone' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return ['user' => $user->load('asc', 'role'), 'token' => $token];
    }

    /**
     * Déconnecte un utilisateur.
     */
    public function logoutUser(User $user)
    {
        $user->currentAccessToken()->delete();
    }
}
