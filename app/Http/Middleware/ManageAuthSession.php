<?php

namespace App\Http\Middleware;

use App\Models\Session as UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

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
            $user = $request->user();
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
                'user_id'       => $userId,
                'token_id'      => $tokenId,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'last_activity' => time(),
                'payload'       => '',
            ]
        );

        return $next($request);
    }
}
