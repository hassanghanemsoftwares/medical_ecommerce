<?php

namespace App\Http\Middleware;

use App\Models\Session as UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Stevebauman\Location\Facades\Location;

class ManageAuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

        if (!$deviceId) {
            return response()->json([
                'result' => false,
                'message' => __('messages.session.device_id_required'),
            ], 400);
        }

        $trackingJson = $request->header('X-Tracking-Data');
        $tracking = json_decode($trackingJson, true) ?? [];

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
        $ip = $request->ip();
        if ((!$latitude || !$longitude) && $ip && !$this->isReservedIP($ip)) {
            $geo = Cache::remember("ip_location_$ip", now()->addDays(7), fn() => Location::get($ip));
            if ($geo) {
                $latitude = $latitude ?? $geo->latitude;
                $longitude = $longitude ?? $geo->longitude;
            }
        }

        $user = $request->user();
        $userId = null;
        $tokenId = null;

        if ($user) {
            // Delete expired tokens
            $expiredTokens = PersonalAccessToken::where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', Carbon::now())
                ->pluck('id');

            if ($expiredTokens->isNotEmpty()) {
                PersonalAccessToken::whereIn('id', $expiredTokens)->delete();
                UserSession::whereIn('token_id', $expiredTokens)->delete();
            }

            // Refresh user and token ID
            $user = $request->user(); // re-fetch user
            $tokenId = $user?->currentAccessToken()?->id;

            if (!$tokenId) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.session.invalid_token'),
                ], 401);
            }

            $userId = $user->id;
        }

        UserSession::updateOrCreate(
            ['id' => $deviceId],
            [
                'user_id'            => $userId,
                'token_id'           => $tokenId,
                'notification_token' => $request->header('X-Notification-Token'),
                'ip_address'         => $ip,
                'user_agent'         => $validated['user_agent'] ?? $request->userAgent(),
                'screen_resolution'  => $validated['screen_resolution'] ?? null,
                'timezone'           => $validated['timezone'] ?? null,
                'language'           => $validated['language'] ?? 'en',
                'referrer'           => $validated['referrer'] ?? null,
                'current_page'       => $validated['page'] ?? null,
                'latitude'           => $latitude,
                'longitude'          => $longitude,
               'last_activity' => time(),
                'is_active'          => true,
                'payload'            => $trackingJson,
            ]
        );

        return $next($request);
    }

    private function isReservedIP(string $ip): bool
    {
        return collect(['127.', '10.', '172.16.', '192.168.'])->contains(
            fn($range) => str_starts_with($ip, $range)
        );
    }
}
