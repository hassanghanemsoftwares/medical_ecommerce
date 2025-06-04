<?php

namespace App\Services;

use App\Http\Resources\V1\Admin\SessionResource;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Session as UserSession;
use Illuminate\Http\Request;

class UserSessionService
{
    public function getSessionFromCookie(): array
    {
        try {
            $cookieName = config('session.cookie');
            $cookieValue = Cookie::get($cookieName);

            if (!$cookieValue) {
                return [
                    'result' => false,
                    'message' => __('messages.session.session_required') . ' (cookie missing)',
                    'session' => null,
                ];
            }

            // Attempt to decrypt session ID (some Laravel setups don't encrypt this)
            try {
                $sessionId = Crypt::decryptString($cookieValue);
            } catch (DecryptException) {
                $sessionId = $cookieValue; // Use raw if not encrypted
            }

            if (str_contains($sessionId, '|')) {
                $parts = explode('|', $sessionId);
                $possibleSessionIds = array_filter([$parts[0] ?? null, $parts[1] ?? null]);
                $session = UserSession::whereIn('id', $possibleSessionIds)->first();
            } else {
                $session = UserSession::where('id', $sessionId)->first();
            }


            if (!$session) {
                return [
                    'result' => false,
                    'message' => __('messages.session.session_required') . ' (session not found)' . $sessionId,
                    'session' => null,
                ];
            }

            return [
                'result' => true,
                'message' => null,
                'session' => $session,
            ];
        } catch (\Throwable $e) {
            return [
                'result' => false,
                'message' => __('messages.session.session_required') . ' (error)',
                'session' => null,
            ];
        }
    }

    public function logSessionActivity(Request $request, string $event, array $extra = [], $causer = null, $subject = null): void
    {
        $sessionResult = $this->getSessionFromCookie();

        if (!$sessionResult['result'] || !$sessionResult['session']) {
            return; // Optionally, log failure
        }

        $session = $sessionResult['session'];
        $data = (new SessionResource($session))->toArray($request);

        $properties = array_merge([
            'email'        => $request->email ?? null,
            'ip'           => $data['ip_address'],
            'browser'      => $data['browser'],
            'platform'     => $data['platform'],
            'device'       => $data['device'],
            'is_mobile'    => $data['is_mobile'],
            'is_tablet'    => $data['is_tablet'],
            'is_desktop'   => $data['is_desktop'],
            'is_robot'     => $data['is_robot'],
            'location'     => $data['location'],
        ], $extra);

        $activity = activity()
            ->inLog('login')
            ->withProperties(['session' => $properties]);

        if ($causer) {
            $activity->causedBy($causer);
        }

        if ($subject) {
            $activity->performedOn($subject);
        }

        $activity->log($event);
    }
}
