<?php

namespace App\Services;

use App\Models\AppDevice;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class PushNotificationService
{
    /**
     * Envoyer une notification Push à tous les appareils d'une ASC
     */
    public function sendToAsc($ascCode, $title, $body, $data = [])
    {
        $devices = AppDevice::where('asc_code', $ascCode)
            ->whereNotNull('fcm_token')
            ->get();

        if ($devices->isEmpty()) {
            return;
        }

        $tokens = $devices->pluck('fcm_token')->toArray();

        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Envoyer une notification Push à une liste de tokens
     */
    public function sendToTokens(array $tokens, $title, $body, $data = [])
    {
        if (empty($tokens)) return;

        try {
            $messaging = Firebase::messaging();

            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $report = $messaging->sendMulticast($message, $tokens);

            Log::info("Push Notification sent to " . count($tokens) . " devices. Success: {$report->successes()->count()}, Failures: {$report->failures()->count()}");
        } catch (\Throwable $e) {
            Log::error("Firebase Push Error: " . $e->getMessage());
        }
    }
}
