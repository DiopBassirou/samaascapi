<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ConvocationController;
use App\Http\Controllers\PlayerNoteController;
use App\Http\Controllers\PouleController;
use App\Http\Controllers\AscController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\PaymentController;
// Routes Publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/ascs', [AscController::class, 'index']); // Liste des ASC validées (pour l'inscription)

// Routes Protégées (Token Sanctum requis)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // --- Pôle ASC (SaaS) ---
    Route::post('/asc/create', [AscController::class, 'store']);
    Route::post('/asc/join', [AscController::class, 'join']);
    
    // --- Super Admin ---
    Route::get('/superadmin/ascs/pending', [SuperAdminController::class, 'getPendingAscs']);
    Route::post('/superadmin/ascs/{code_unique}/approve', [SuperAdminController::class, 'approveAsc']);
    Route::post('/superadmin/ascs/{code_unique}/reject', [SuperAdminController::class, 'rejectAsc']);

    // --- Pôle Sportif ---
    // Joueurs (Effectif)
    Route::get('/players', [PlayerController::class, 'index']);
    Route::post('/players', [PlayerController::class, 'store']);
    Route::delete('/players/{id}', [PlayerController::class, 'destroy']);
    
    // Convocations (Coach)
    Route::post('/matches/{matchId}/convocations', [ConvocationController::class, 'store']);
    
    // Matchs (Calendrier, Direct & Score)
    Route::get('/matches', [MatchController::class, 'index']);
    Route::post('/matches', [MatchController::class, 'store']);
    Route::put('/matches/{id}/score', [MatchController::class, 'updateScore']);
    Route::put('/matches/{id}/status', [MatchController::class, 'updateStatus']);
    Route::post('/matches/{id}/events', [MatchController::class, 'addEvent']);

    // --- Pôle Supporter (Votes) ---
    Route::post('/matches/{matchId}/players/{playerId}/rate', [PlayerNoteController::class, 'store']);
    Route::get('/matches/{matchId}/players/{playerId}/rating', [PlayerNoteController::class, 'getAverage']);

    // --- Pôle Compétition (Poules) ---
    Route::get('/poules', [PouleController::class, 'index']);
    Route::post('/poules', [PouleController::class, 'store']);
    Route::post('/poules/other-match', [PouleController::class, 'addOtherMatchResult']);

    // --- Pôle Financier (Bilan Trésorier) ---
    Route::get('/finances', [TransactionController::class, 'index']);
    Route::post('/finances', [TransactionController::class, 'store']);
    Route::get('/finances/export', [TransactionController::class, 'exportPdf']);

    // --- Pôle Président (Bureau) ---
    Route::get('/bureau', [\App\Http\Controllers\BureauController::class, 'index']);
    Route::post('/bureau/assign', [\App\Http\Controllers\BureauController::class, 'assign']);

    // --- Paramètres Généraux ---
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index']);
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update']);
    Route::post('/settings/logo', [\App\Http\Controllers\SettingsController::class, 'uploadLogo']);

    // --- Classement ---
    Route::get('/classement', [\App\Http\Controllers\ClassementController::class, 'index']);

    // --- Annonces (News) ---
    Route::get('/news', [\App\Http\Controllers\AnnouncementController::class, 'index']);
    Route::post('/news', [\App\Http\Controllers\AnnouncementController::class, 'store']);

    // --- Cotisation (Paiement) ---
    Route::post('/payments/wave', [PaymentController::class, 'payerParWave']);
    Route::post('/payments/om', [PaymentController::class, 'payerParOM']);
});

// Webhooks de paiement (hors de l'authentification)
Route::post('/webhooks/wave', [PaymentController::class, 'waveWebhook']);
Route::post('/webhooks/om', [PaymentController::class, 'omWebhook']);
