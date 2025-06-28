<?php
// app/Http/Middleware/TrackClientSession.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ClientSession;
use Illuminate\Support\Facades\Log;

class TrackClientSession
{
    public function handle(Request $request, Closure $next)
    {
        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

        if ($deviceId) {
            ClientSession::updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'client_id' => optional($request->user('sanctum'))->id,
                    'notification_token' => $request->header('X-Notification-Token') ?? null,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'is_active' => true,
                    'last_activity' => now(),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                ]
            );
        }

        return $next($request);
    }
}
