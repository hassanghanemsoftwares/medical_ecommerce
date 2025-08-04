<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ClientSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Stevebauman\Location\Facades\Location;

class TrackClientSession
{
    public function handle(Request $request, Closure $next)
    {
        $trackingJson = $request->header('X-Tracking-Data');
        $tracking = json_decode($trackingJson, true) ?? [];

        $deviceId = $tracking['device_id'] ?? null;
        if (!$deviceId) {
            return response()->json([
                'result' => false,
                'message' => __('messages.session.device_id_required'),
            ]);
        }

        // Validate tracking fields
        $validator = Validator::make($tracking, [
            'user_agent'        => 'nullable|string|max:255',
            'screen_resolution' => 'nullable|string|max:30',
            'timezone'          => 'nullable|string|max:50',
            'language'          => 'nullable|string|max:10',
            'referrer'          => 'nullable|url|max:500',
            'page'              => 'nullable|url|max:500',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __('messages.session.invalid_tracking_data'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;

        // Fallback to IP-based geolocation
        if (!$latitude || !$longitude) {
            $ip = $request->ip();
            if ($ip && !self::isReservedIP($ip)) {
                $geo = Cache::remember("ip_location_$ip", now()->addDays(7), fn() => Location::get($ip));

                if ($geo) {
                    $latitude = $latitude ?? $geo->latitude;
                    $longitude = $longitude ?? $geo->longitude;
                }
            }
        }

        $user = $request->user('sanctum');

        ClientSession::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'client_id'          => $user?->id,
                'token_id'           => $user?->currentAccessToken()?->id,
                'notification_token' => $request->header('X-Notification-Token'),
                'ip_address'         => $request->ip(),
                'user_agent'         => $validated['user_agent'] ?? null,
                'screen_resolution'  => $validated['screen_resolution'] ?? null,
                'timezone'           => $validated['timezone'] ?? null,
                'language'           => $validated['language'] ?? 'en',
                'referrer'           => $validated['referrer'] ?? null,
                'current_page'       => $validated['page'] ?? null,
                'is_active'          => true,
                'last_activity'      => now(),
                'latitude'           => $latitude,
                'longitude'          => $longitude,
            ]
        );

        return $next($request);
    }

    private static function isReservedIP(string $ip): bool
    {
        return collect([
            '127.',
            '10.',
            '172.16.',
            '192.168.',
        ])->contains(fn($range) => str_starts_with($ip, $range));
    }
}
