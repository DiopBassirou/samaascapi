<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'intégration de l'API Orange Money Sonatel eWallet.
 *
 * Documentation : https://api.orange-sonatel.com
 *
 * Flux de paiement QR Code (membre → marchand) :
 *   1. obtenirJetonAcces()          → Token OAuth2
 *   2. genererQRCode()              → Génère un QR Code + deep link Maxit pour le membre
 *   3. [Membre scanne QR avec app Maxit et valide]
 *   4. webhookPaiementRecu()        → Orange appelle notre webhook pour confirmer
 *
 * Flux Cash-In (marchand → gestionnaire) :
 *   1. obtenirJetonAcces()          → Token OAuth2
 *   2. obtenirClePublique()         → Clé RSA pour chiffrer le PIN marchand
 *   3. envoyerVersGestionnaire()    → Cash-In du compte marchand vers le gestionnaire
 *
 * Note : Le flux OTP (/payments/otp) est réservé aux comptes de type "merchant".
 * Notre compte est de type "retailer" donc on utilise le QR Code pour la collecte.
 */
class OrangeMoneyService
{
    // ─────────────────────────────────────────────────────────────────────────
    // CONSTANTES API
    // ─────────────────────────────────────────────────────────────────────────
    private const ENDPOINT_PUBLIC_KEYS = '/api/account/v1/publicKeys';
    private const ENDPOINT_QR_CODE     = '/api/eWallet/v4/qrcode';
    private const ENDPOINT_CASHIN      = '/api/eWallet/v1/cashins';
    private const ENDPOINT_STATUS      = '/api/eWallet/v1/transactions/%s/status';

    /** URL de base selon l'environnement */
    protected string $baseUrl;

    /** URL d'authentification OAuth2 */
    protected string $authUrl;

    /** Identifiant client Orange Sonatel */
    protected string $clientId;

    /** Secret client Orange Sonatel */
    protected string $clientSecret;

    /** Code marchand Orange Sonatel (ex: "119354") */
    protected string $merchantCode;

    /** Numéro MSISDN du compte marchand (ex: "771234567") */
    protected string $merchantMsisdn;

    /** PIN du compte marchand (en clair, sera chiffré avant envoi) */
    protected string $merchantPin;

    public function __construct()
    {
        $env = config('services.orange_sonatel.env', 'sandbox');

        if ($env === 'production') {
            $this->baseUrl = 'https://api.orange-sonatel.com';
            $this->authUrl = 'https://api.orange-sonatel.com/oauth/token';
        } else {
            $this->baseUrl = 'https://api.sandbox.orange-sonatel.com';
            $this->authUrl = 'https://api.sandbox.orange-sonatel.com/oauth/token';
        }

        $this->clientId       = (string) config('services.orange_sonatel.client_id', '');
        $this->clientSecret   = (string) config('services.orange_sonatel.client_secret', '');
        $this->merchantCode   = (string) config('services.orange_sonatel.merchant_code', '');
        $this->merchantMsisdn = (string) config('services.orange_sonatel.merchant_msisdn', '');
        $this->merchantPin    = (string) config('services.orange_sonatel.merchant_pin', '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTHENTIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtenir un jeton d'accès OAuth2 (mis en cache 50 minutes).
     *
     * Note : La durée du token Sonatel n'est pas documentée publiquement,
     * on met en cache 50 min par sécurité (renouvellement fréquent).
     */
    public function obtenirJetonAcces(): ?string
    {
        $cleCache = 'orange_sonatel_access_token';

        if (Cache::has($cleCache)) {
            return Cache::get($cleCache);
        }

        try {
            $reponse = Http::asForm()->post($this->authUrl, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($reponse->successful()) {
                $donnees = $reponse->json();
                $jeton   = $donnees['access_token'] ?? null;

                if ($jeton) {
                    // Durée par défaut si non fournie : 50 minutes
                    $expiresIn = (int) ($donnees['expires_in'] ?? 3000);
                    $cacheDuration = max(60, $expiresIn - 300); // 5 min de marge

                    Cache::put($cleCache, $jeton, now()->addSeconds($cacheDuration));
                    Log::info('Orange Sonatel : Nouveau jeton obtenu et mis en cache.');

                    return $jeton;
                }
            }

            Log::error('Orange Sonatel : Échec authentification.', [
                'status'  => $reponse->status(),
                'body'    => $reponse->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception auth.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Vider le cache du token (utile après changement de credentials).
     */
    public function viderCacheJeton(): void
    {
        Cache::forget('orange_sonatel_access_token');
        Log::info('Orange Sonatel : Cache du jeton vidé.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLÉ PUBLIQUE RSA (pour chiffrer le PIN marchand)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Récupérer la clé publique RSA d'Orange Sonatel.
     *
     * Cette clé sert à chiffrer le PIN marchand avant de l'envoyer à l'API.
     * Elle est mise en cache 24h (la clé change rarement).
     */
    public function obtenirClePublique(): ?string
    {
        $cleCache = 'orange_sonatel_public_key';

        if (Cache::has($cleCache)) {
            return Cache::get($cleCache);
        }

        $jeton = $this->obtenirJetonAcces();
        if (! $jeton) {
            return null;
        }

        try {
            $reponse = Http::withToken($jeton)
                ->get($this->baseUrl . self::ENDPOINT_PUBLIC_KEYS);

            if ($reponse->successful()) {
                $donnees    = $reponse->json();
                $clePublique = $donnees['key'] ?? ($donnees[0]['key'] ?? null);

                if ($clePublique) {
                    Cache::put($cleCache, $clePublique, now()->addHours(24));
                    Log::info('Orange Sonatel : Clé publique RSA récupérée et mise en cache.');

                    return $clePublique;
                }
            }

            Log::error('Orange Sonatel : Échec récupération clé publique.', [
                'status' => $reponse->status(),
                'body'   => $reponse->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception clé publique.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Chiffrer un PIN avec la clé publique RSA d'Orange Sonatel.
     *
     * @param  string  $pin         PIN en clair (ex: "1234")
     * @param  string  $clePublique Clé publique RSA (PEM ou base64)
     * @return string|null          PIN chiffré en base64
     */
    public function chiffrerPin(string $pin, string $clePublique): ?string
    {
        try {
            // Préparer la clé au format PEM si nécessaire
            if (! str_contains($clePublique, '-----BEGIN')) {
                $clePublique = "-----BEGIN PUBLIC KEY-----\n"
                    . chunk_split($clePublique, 64, "\n")
                    . "-----END PUBLIC KEY-----\n";
            }

            $ressourceCle = openssl_get_publickey($clePublique);
            if (! $ressourceCle) {
                Log::error('Orange Sonatel : Clé RSA invalide.');

                return null;
            }

            $pinChiffre = '';
            $resultat   = openssl_public_encrypt($pin, $pinChiffre, $ressourceCle, OPENSSL_PKCS1_PADDING);

            if (! $resultat) {
                Log::error('Orange Sonatel : Échec du chiffrement RSA du PIN.');

                return null;
            }

            return base64_encode($pinChiffre);

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception chiffrement PIN.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Obtenir le PIN marchand chiffré (récupère la clé et chiffre en une étape).
     */
    protected function obtenirPinMarchandChiffre(): ?string
    {
        $clePublique = $this->obtenirClePublique();
        if (! $clePublique) {
            Log::error('Orange Sonatel : Impossible d\'obtenir la clé publique pour chiffrer le PIN.');

            return null;
        }

        return $this->chiffrerPin($this->merchantPin, $clePublique);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FLUX QR CODE — Paiement membre → compte marchand
    // (Compatible avec les comptes de type "retailer")
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Générer un QR Code de paiement Orange Money.
     *
     * Le membre scanne ce QR Code avec son application Maxit (Orange Money)
     * et valide le paiement. Orange Sonatel appelle ensuite notre webhook
     * pour confirmer la transaction.
     *
     * @param  float   $montant       Montant en FCFA
     * @param  string  $reference     Référence interne (ex: "sub_42" ou "cot_15")
     * @param  string  $libelle       Libellé affiché dans l'app Maxit (ex: "Abonnement Sama Tontine")
     * @param  array   $metadata      Données supplémentaires (type, id, etc.)
     * @param  int     $validiteSecondes  Durée de validité du QR Code en secondes (défaut: 300 = 5 min)
     * @return array|null             {
     *   'qrCode'    => string (base64 PNG),
     *   'deepLink'  => string (URL pour ouvrir Maxit directement),
     *   'qrId'      => string (ID unique du QR Code pour suivi),
     *   'validFor'  => array (startDateTime, endDateTime),
     * }
     */
    public function genererQRCode(
        float $montant,
        string $reference,
        string $libelle = 'Paiement Sama Tontine',
        array $metadata = [],
        int $validiteSecondes = 300
    ): ?array {
        $jeton = $this->obtenirJetonAcces();
        if (! $jeton) {
            return null;
        }

        // URL du webhook — on s'assure qu'il n'y a pas de double /api/api/
        $appUrl = rtrim(config('app.url'), '/');
        if (str_ends_with($appUrl, '/api')) {
            $appUrl = substr($appUrl, 0, -4);
        }
        $baseWebhook = $appUrl . '/api/payments/om/webhook';

        $metadataMerged = array_merge($metadata, ['internal_ref' => $reference]);
        $metadataStr = [];
        foreach ($metadataMerged as $k => $v) {
            $metadataStr[$k] = (string) $v;
        }

        try {
            $reponse = Http::withToken($jeton)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Callback-Url' => $baseWebhook . '/success',
                ])
                ->post($this->baseUrl . self::ENDPOINT_QR_CODE, [
                    'amount' => [
                        'unit'  => 'XOF',
                        'value' => (int) $montant,
                    ],
                    'callbackSuccessUrl' => $baseWebhook . '/success?ref=' . urlencode($reference),
                    'callbackCancelUrl'  => $baseWebhook . '/cancel?ref=' . urlencode($reference),
                    'code'              => $this->merchantCode,
                    'name'              => substr($libelle, 0, 45), // Limite de caractères de l'API
                    'validity'          => $validiteSecondes,
                    'metadata'          => $metadataStr,
                ]);

            if ($reponse->successful()) {
                $donnees = $reponse->json();
                Log::info('Orange Sonatel : QR Code généré.', [
                    'reference' => $reference,
                    'montant'   => $montant,
                    'qrId'      => $donnees['qrId'] ?? 'N/A',
                ]);

                return [
                    'qrCode'   => $donnees['qrCode']   ?? null,  // Base64 PNG
                    'deepLink' => $donnees['deepLinks']['MAXIT'] ?? $donnees['deepLink'] ?? null,
                    'qrId'     => $donnees['qrId']     ?? null,
                    'validFor' => $donnees['validFor']  ?? null,
                    'validity' => $donnees['validity']  ?? $validiteSecondes,
                ];
            }

            Log::error('Orange Sonatel : Échec génération QR Code.', [
                'reference' => $reference,
                'status'    => $reponse->status(),
                'body'      => $reponse->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception QR Code.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FLUX OTP — ÉTAPE 3 : Cash-In vers le gestionnaire
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envoyer l'argent depuis le compte marchand vers le numéro OM du gestionnaire.
     *
     * Appelé automatiquement après confirmation du paiement membre.
     *
     * @param  string  $numeroDest   Numéro Orange Money du gestionnaire
     * @param  float   $montant      Montant à envoyer (montant base, sans les frais envoi)
     * @param  string  $reference    Référence de la transaction originale
     * @return array|null
     */
    public function envoyerVersGestionnaire(
        string $numeroDest,
        float $montant,
        string $reference,
        string $description = 'Paiement Tontine'
    ): ?array {
        $jeton = $this->obtenirJetonAcces();
        if (! $jeton) {
            return null;
        }

        $pinChiffre = $this->obtenirPinMarchandChiffre();
        if (! $pinChiffre) {
            return null;
        }

        // Normaliser les numéros au format international (221XXXXXXXXX)
        $merchantMsisdnNorm = $this->normaliserMsisdn($this->merchantMsisdn);
        $numeroDest         = $this->normaliserMsisdn($numeroDest);

        try {
            $reponse = Http::withToken($jeton)
                ->post($this->baseUrl . self::ENDPOINT_CASHIN, [
                    'partner' => [
                        'idType'           => 'MSISDN',
                        'id'               => $merchantMsisdnNorm,
                        'encryptedPinCode' => $pinChiffre,
                    ],
                    'customer' => [
                        'idType' => 'MSISDN',
                        'id'     => $numeroDest,
                    ],
                    'amount' => [
                        'value' => (int) $montant,
                        'unit'  => 'XOF',
                    ],
                    'reference'           => 'cashin_'.$reference,
                    'receiveNotification' => true,
                    'description'         => substr($description, 0, 50),
                ]);

            if ($reponse->successful()) {
                $donnees = $reponse->json();
                Log::info('Orange Sonatel : Cash-In vers gestionnaire effectué.', [
                    'dest'      => substr($numeroDest, 0, 3).'XXXXXX',
                    'montant'   => $montant,
                    'reference' => $reference,
                    'status'    => $donnees['status'] ?? 'N/A',
                ]);

                return $donnees;
            }

            $errorData = $reponse->json();
            $errorCode = $errorData['code'] ?? null;
            $errorDetail = $errorData['detail'] ?? 'Erreur inconnue';

            $messagesFR = [
                '2000' => "Le compte client n'existe pas.",
                '2001' => "Le numéro de téléphone est invalide.",
                '2010' => "Le code PIN marchand doit être changé.",
                '2011' => "Code PIN invalide (2 tentatives restantes).",
                '2012' => "Code PIN invalide (1 tentative restante).",
                '2013' => "Code PIN invalide, compte bloqué.",
                '2020' => "Solde insuffisant.",
                '2021' => "Votre solde marchand est insuffisant pour ce transfert.",
                '2022' => "Le solde du bénéficiaire est insuffisant/incompatible.",
                '2023' => "Le plafond du compte est atteint.",
                '2024' => "Le plafond de votre compte marchand est atteint.",
                '2025' => "Le plafond du compte du bénéficiaire est atteint.",
                '2041' => "Transaction non autorisée.",
                '2042' => "Transaction non autorisée pour le marchand.",
                '2043' => "Transaction non autorisée pour le bénéficiaire.",
            ];

            $messageClair = $messagesFR[(string)$errorCode] ?? "Erreur API Sonatel : " . $errorDetail;

            Log::error('Orange Sonatel : Échec Cash-In gestionnaire.', [
                'reference' => $reference,
                'status'    => $reponse->status(),
                'code'      => $errorCode,
                'body'      => $reponse->body(),
            ]);

            return [
                'error' => true,
                'code' => $errorCode,
                'message' => $messageClair
            ];

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception Cash-In.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VÉRIFICATION DU STATUT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vérifier le statut d'une transaction Orange Sonatel.
     *
     * @param  string  $transactionId  L'ID de transaction retourné par l'API
     */
    public function verifierStatutTransaction(string $transactionId): ?array
    {
        $jeton = $this->obtenirJetonAcces();
        if (! $jeton) {
            return null;
        }

        try {
            $endpoint = sprintf(self::ENDPOINT_STATUS, $transactionId);
            $reponse = Http::withToken($jeton)
                ->get($this->baseUrl . $endpoint);

            if ($reponse->successful()) {
                return $reponse->json();
            }

            Log::error('Orange Sonatel : Échec vérification statut.', [
                'transactionId' => $transactionId,
                'status'        => $reponse->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception vérification statut.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Normaliser un numéro MSISDN au format attendu par l'API Orange Sonatel.
     *
     * L'API Orange Sonatel eWallet attend EXACTEMENT 9 chiffres (format local sénégalais).
     *   - "77XXXXXXX"    (9 chiffres)   → inchangé (format attendu)
     *   - "221XXXXXXXXX" (12 chiffres)  → supprime le 221 → "XXXXXXXXX"
     *   - "+221XXXXXXXX" (avec +)       → supprime le +221 → "XXXXXXXXX"
     *   - "077XXXXXXX"   (10 chiffres)  → supprime le 0 devant → "XXXXXXXXX"
     *
     * @param  string  $numero  Numéro brut
     * @return string           Numéro à 9 chiffres exacts
     */
    protected function normaliserMsisdn(string $numero): string
    {
        // Supprimer espaces, tirets, parenthèses
        $numero = preg_replace('/[\s\-\(\)]/', '', $numero);

        // Supprimer le "+" si présent
        $numero = ltrim($numero, '+');

        // Supprimer l'indicatif pays 221 s'il est présent
        if (str_starts_with($numero, '221') && strlen($numero) === 12) {
            $numero = substr($numero, 3);
        }

        // Supprimer le 0 devant si format 077XXXXXXX
        if (str_starts_with($numero, '0') && strlen($numero) === 10) {
            $numero = substr($numero, 1);
        }

        return $numero; // Doit être 9 chiffres ex: "77XXXXXXX"
    }

    /**
     * Normaliser le statut brut Orange Sonatel vers un statut interne.
     *
     * Codes statut Orange Sonatel eWallet :
     *   "TS" (Transaction Success)  → success
     *   "TF" (Transaction Failed)   → failed
     *   "TP" (Transaction Pending)  → pending
     *   "TC" (Transaction Cancelled)→ cancelled
     */
    public function normaliserStatut(array $donneesStatut): string
    {
        $statut = strtoupper($donneesStatut['status'] ?? '');

        return match ($statut) {
            'TS', 'SUCCESS'   => 'success',
            'TF', 'FAILED'    => 'failed',
            'TC', 'CANCELLED' => 'cancelled',
            default           => 'pending',
        };
    }

    /**
     * Vérifier si le service est correctement configuré.
     */
    public function estConfigure(): bool
    {
        return ! empty($this->clientId)
            && ! empty($this->clientSecret)
            && ! empty($this->merchantCode)
            && ! empty($this->merchantMsisdn)
            && ! empty($this->merchantPin);
    }
    /**
     * Consulter le solde du compte marchand.
     * Note: L'endpoint dépend de l'API Sonatel (ex: POST /api/eWallet/v1/account/balances)
     * 
     * @return array|null
     */
    public function consulterSolde(): ?array
    {
        $jeton = $this->obtenirJetonAcces();
        if (! $jeton) {
            return null;
        }

        // L'API nécessite le PIN chiffré pour consulter le solde
        $pinChiffre = $this->obtenirPinMarchandChiffre();
        if (! $pinChiffre) {
            return [
                'error' => true,
                'message' => 'Impossible de chiffrer le PIN marchand.'
            ];
        }

        // L'endpoint documenté par Sonatel pour un Retailer
        $endpoint = '/api/eWallet/v1/account/retailer/balance';
        
        $merchantMsisdnNorm = $this->normaliserMsisdn($this->merchantMsisdn);
        
        try {
            $reponse = Http::withToken($jeton)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post($this->baseUrl . $endpoint, [
                    'idType'           => 'MSISDN',
                    'id'               => $merchantMsisdnNorm,
                    'encryptedPinCode' => $pinChiffre,
                    'wallet'           => 'PRINCIPAL'
                ]);

            if ($reponse->successful()) {
                return $reponse->json();
            }

            Log::error('Orange Sonatel : Échec consultation solde.', [
                'status' => $reponse->status(),
                'body'   => $reponse->body(),
            ]);

            return [
                'error' => true,
                'status' => $reponse->status(),
                'message' => $reponse->body()
            ];

        } catch (\Exception $e) {
            Log::error('Orange Sonatel : Exception consultation solde.', ['message' => $e->getMessage()]);

            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

}
