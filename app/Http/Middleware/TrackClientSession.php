<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ClientSession;

class TrackClientSession
{
    public function handle(Request $request, Closure $next)
    {
        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

        if (!$deviceId) {
            return response()->json([
                'result' => false,
                'message' => __('messages.session.device_id_required'),
            ]);
        }

        $user = $request->user('sanctum');

        ClientSession::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'client_id'             => $user?->id,
                'token_id' => $user?->currentAccessToken()?->id,
                'notification_token'    => $request->header('X-Notification-Token'),
                'ip_address'            => $request->ip(),
                'user_agent'            => $request->userAgent(),
                'is_active'             => true,
                'last_activity'         => now(),
                'latitude'              => $request->input('latitude'),
                'longitude'             => $request->input('longitude'),
            ]
        );

        return $next($request);
    }
}
