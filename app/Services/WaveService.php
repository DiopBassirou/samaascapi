<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaveService
{
    protected ?string $apiKey;

    protected $baseUrl = 'https://api.wave.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.wave.key');
    }

    /**
     * Créer une session de paiement Wave
     *
     * @param  float  $amount  Montant en XOF
     * @param  string  $transactionId  Votre ID de transaction interne
     * @param  string  $successUrl  URL de retour en cas de succès
     * @param  string  $errorUrl  URL de retour en cas d'erreur
     * @return array|null
     */
    public function createCheckoutSession($amount, $transactionId, $successUrl, $errorUrl)
    {
        // Wave exige du HTTPS pour les URLs de retour
        $successUrl = str_replace('http://', 'https://', $successUrl);
        $errorUrl = str_replace('http://', 'https://', $errorUrl);

        try {
            $response = Http::withToken($this->apiKey)
                ->post($this->baseUrl.'/checkout/sessions', [
                    'amount' => (int) $amount,
                    'currency' => 'XOF',
                    'error_url' => $errorUrl,
                    'success_url' => $successUrl,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Wave Payment Error: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Wave Exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Vérifier le statut d'une session de paiement Wave
     *
     * @param  string  $sessionId  L'ID de la session Wave
     * @return array|null
     */
    public function verifyCheckoutSession($sessionId)
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->get($this->baseUrl.'/checkout/sessions/'.$sessionId);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Wave Verification Error: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Wave Verification Exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Vérifier la signature du Webhook Wave
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $webhookSecret (Optionnel, sinon prend config)
     * @return bool
     */
    public function verifyWebhookSignature($request, $webhookSecret = null): bool
    {
        $secret = $webhookSecret ?? config('payments.wave.webhook_secret');
        if (empty($secret)) {
            Log::warning('Wave Webhook Secret manquant.');
            return false;
        }

        $signatureHeader = $request->header('Wave-Signature');
        if (! $signatureHeader) {
            return false;
        }

        // Le format typique du header: t=1612345678,v1=xxxxxxxxxxxxx
        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                if ($kv[0] === 't') {
                    $timestamp = $kv[1];
                } elseif ($kv[0] === 'v1') {
                    $signatures[] = $kv[1];
                }
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        // Tolérance de 5 minutes pour éviter les attaques par rejeu (replay attacks)
        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        $body = $request->getContent();
        $payloadToSign = $timestamp . '.' . $body;
        $expectedSignature = hash_hmac('sha256', $payloadToSign, $secret);

        return in_array($expectedSignature, $signatures, true);
    }
}
