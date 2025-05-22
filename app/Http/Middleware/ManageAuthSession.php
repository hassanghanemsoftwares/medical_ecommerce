<?php

namespace App\Http\Middleware;

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

            $session->update([
                'user_id' => $user->id,
                'token_id' => $tokenId,
                'last_activity' => Carbon::now()->timestamp,
            ]);
        }

        return $next($request);
    }
}
