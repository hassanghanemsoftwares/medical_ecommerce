<?php

namespace App\Services;

use App\Http\Resources\V1\SessionResource;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Session as UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserSessionService
{
    public function getSessionFromCookie(): array
    {
        try {
            $cookieName = config('session.cookie');
            $decrypted = Cookie::get($cookieName);

            if (!$decrypted) {
                return [
                    'result' => false,
                    'message' => __('messages.session.session_required'),
                    'session' => null,
                ];
            }
            $parts = explode('|', Crypt::decryptString($decrypted));
            $possibleSessionIds = array_filter([$parts[0] ?? null, $parts[1] ?? null]);
            $session = UserSession::whereIn('id', $possibleSessionIds)->first();
            if (!$session) {
                return [
                    'result' => false,
                    'message' => __('messages.session.session_required'),
                    'session' => null,
                ];
            }

            return [
                'result' => true,
                'message' => null,
                'session' => $session,
            ];
        } catch (DecryptException $e) {
            return [
                'result' => false,
                'message' => __('messages.session.session_required'),
                'session' => null,
            ];
        }
    }
    public function logSessionActivity(Request $request, string $event, array $extra = [], $causer = null, $subject = null): void
    {
        $session = $this->getSessionFromCookie()['session'];
        $data = (new SessionResource($session))->toArray($request);
        $properties = array_merge([
            'email'        => $request->email,
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

        $activity = activity()->inLog('login')->withProperties(['session' => $properties]);

        if ($causer) {
            $activity->causedBy($causer);
        }

        if ($subject) {
            $activity->performedOn($subject);
        }

        $activity->log($event);
    }
}
