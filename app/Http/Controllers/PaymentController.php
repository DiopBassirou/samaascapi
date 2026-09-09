<?php

namespace App\Http\Controllers;

use App\Services\OrangeMoneyService;
use App\Services\WaveService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Initier un paiement de cotisation par Wave
     */
    public function payerParWave(Request $request, WaveService $waveService)
    {
        $request->validate([
            'montant' => 'required|numeric|min:100',
        ]);

        $montant = $request->input('montant');
        $transactionId = 'COT_W_' . uniqid(); // Génère un ID unique pour la cotisation

        // L'URL de redirection après paiement (succès et erreur) pour la PWA
        $successUrl = env('FRONTEND_URL', 'https://topjeunesse.sunugalsolutiongroup.com') . '/payment-success';
        $errorUrl = env('FRONTEND_URL', 'https://topjeunesse.sunugalsolutiongroup.com') . '/payment-error';

        try {
            $session = $waveService->createCheckoutSession(
                $montant, 
                $transactionId, 
                $successUrl, 
                $errorUrl
            );

            // Vous pouvez enregistrer la transaction en BD ici avec un statut "PENDING"

            return response()->json([
                'success' => true,
                'provider' => 'wave',
                'transaction_id' => $transactionId,
                'session' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement Wave.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Initier un paiement de cotisation par Orange Money
     */
    public function payerParOM(Request $request, OrangeMoneyService $omService)
    {
        $request->validate([
            'montant' => 'required|numeric|min:100',
        ]);

        $montant = $request->input('montant');
        $transactionId = 'COT_OM_' . uniqid();
        $libelle = "Cotisation ASC " . $transactionId;

        try {
            $qrCode = $omService->genererQRCode($montant, $transactionId, $libelle);
            
            // Vous pouvez enregistrer la transaction en BD ici avec un statut "PENDING"

            return response()->json([
                'success' => true,
                'provider' => 'orange_money',
                'transaction_id' => $transactionId,
                'payment_info' => $qrCode // Contient l'image du QR Code et le Deep Link Maxit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement Orange Money.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhooks pour valider les paiements (appels reçus de Wave/OM)
     */
    public function waveWebhook(Request $request)
    {
        // Logique de validation du webhook Wave...
        // Mettre à jour la transaction en BD (ex: statut "SUCCESS")
        return response()->json(['status' => 'received']);
    }

    public function omWebhook(Request $request)
    {
        // Logique de validation du webhook Orange Money...
        // Mettre à jour la transaction en BD
        return response()->json(['status' => 'received']);
    }
}
