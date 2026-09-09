<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $result = $this->authService->registerUser($data);
        return response()->json($result, 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->authService->loginUser($request->telephone, $request->password);
        return response()->json($result);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('asc', 'role'));
    }

    public function logout(Request $request)
    {
        $this->authService->logoutUser($request->user());
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }
}
