<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Session as UserSession;
use App\Http\Resources\V1\Admin\SessionResource as V1SessionResource;
use App\Services\UserSessionService;
use Exception;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(UserSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function getAllSessions()
    {
        try {
            $user = Auth::user();
            $sessions = UserSession::where('user_id', $user->id)->get();

            return response()->json([
                'result' => true,
                'message' => __('messages.session.session_retrieved'),
                'sessions' => V1SessionResource::collection($sessions),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.session.failed_to_retrieve'), $e);
        }
    }

    public function logoutOtherDevices(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required|string',
            ]);

            $user = $request->user();

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.session.invalid_password'),
                ]);
            }

            $currentToken = $user->currentAccessToken();
            if (!$currentToken) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.session.no_active_token'),
                ]);
            }

            $sessionCheck = $this->sessionService->getSessionFromDevice($request);

            if (!$sessionCheck['result']) {
                return response()->json([
                    'result' => false,
                    'message' => $sessionCheck['message'],
                ]);
            }

            $currentSession = $sessionCheck['session'];

            UserSession::where('user_id', $user->id)
                ->where('id', '!=', $currentSession->id)
                ->delete();

            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.session.logout_other_devices'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.session.session_update_failed'), $e);
        }
    }

    public function logoutSpecificDevice(Request $request)
    {
        try {
            $request->validate([
                'sessionId' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = $request->user();

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.session.invalid_password'),
                ]);
            }

            $session = UserSession::where('id', $request->sessionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$session) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.session.invalid_session'),
                ]);
            }

            if ($session->token_id) {
                $user->tokens()->where('id', $session->token_id)->delete();
            }

            $session->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.session.logout_specific_device'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.session.session_update_failed'), $e);
        }
    }
}
