<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\UserSessionService;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class ManageAuthSession
{
    protected $sessionService;

    public function __construct(UserSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $sessionCheck = $this->sessionService->getSessionFromCookie();

        if (!$sessionCheck['result']) {
            return response()->json([
                'result' => false,
                'message' => $sessionCheck['message'],
            ]);
        }

        $session = $sessionCheck['session'];
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();
            $tokenId = $token instanceof PersonalAccessToken ? $token->id : null;
            $expiredTokens = PersonalAccessToken::where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', Carbon::now())
                ->get();

            if ($expiredTokens->isNotEmpty()) {
                $expiredTokenIds = $expiredTokens->pluck('id');

                // Delete expired tokens
                PersonalAccessToken::whereIn('id', $expiredTokenIds)->delete();

                // Delete sessions that have these token_ids
                Session::whereIn('token_id', $expiredTokenIds)->delete();
            }
            $session->update([
                'user_id' => $user->id,
                'token_id' => $tokenId,
                'last_activity' => Carbon::now()->timestamp,
            ]);
        } else {
            $session->update([
                'user_id' => null,
                'token_id' => null,
                'last_activity' => Carbon::now()->timestamp,
            ]);
        }

        return $next($request);
    }
}
