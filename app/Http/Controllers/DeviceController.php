<?php

namespace App\Http\Controllers;

use App\Models\AppDevice;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Enregistre ou met à jour un appareil (Guest ou Connecté)
     */
    public function registerOrPing(Request $request)
    {
        $data = $request->validate([
            'device_uuid' => 'required|string|max:255',
            'fcm_token'   => 'nullable|string',
            'asc_code'    => 'nullable|string|exists:ascs,code_unique',
            'platform'    => 'nullable|string|max:50',
        ]);

        $device = AppDevice::updateOrCreate(
            ['device_uuid' => $data['device_uuid']],
            [
                'fcm_token'    => $data['fcm_token'] ?? null,
                'asc_code'     => $data['asc_code'] ?? null,
                'platform'     => $data['platform'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Appareil enregistré avec succès',
            'device'  => $device,
        ]);
    }

    /**
     * Statistiques globales sur les appareils / utilisateurs (Pour Super Admin)
     */
    public function stats()
    {
        $totalDevices = AppDevice::count();
        $activeToday = AppDevice::where('last_seen_at', '>=', now()->subDay())->count();
        $activeThisWeek = AppDevice::where('last_seen_at', '>=', now()->subDays(7))->count();

        $ascBreakdown = AppDevice::with('asc:code_unique,nom')
            ->whereNotNull('asc_code')
            ->selectRaw('asc_code, count(*) as total')
            ->groupBy('asc_code')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'total_installations' => $totalDevices,
            'active_today'        => $activeToday,
            'active_this_week'    => $activeThisWeek,
            'asc_breakdown'       => $ascBreakdown,
        ]);
    }
}
