<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\ClientResource;
use App\Models\Client;
use App\Services\OtpService;
use App\Services\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class ClientAuthController extends Controller
{
    protected $otpService;
    protected $sessionService;

    public function __construct(OtpService $otpService, UserSessionService $sessionService)
    {
        $this->otpService = $otpService;
        $this->sessionService = $sessionService;
    }

    private function validateRequest(Request $request, array $rules, array $messages)
    {
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors()->first()
            ]);
        }
        return null;
    }

    public function sendOtp(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
        ]);

        if ($validation) return $validation;

        try {
            $client = Client::firstOrCreate(['email' => $request->email]);

            $generateOtp = $this->otpService->generateOtp($request->email);
            $otp = $generateOtp[0];
            $expiresAt = $generateOtp[1];

            Mail::to($client->email)->send(new OtpEmail($otp, $client->name));

            $this->sessionService->logSessionActivity($request, 'client.otp.sent', [], $client);

            return response()->json([
                'result' => true,
                'message' => __('messages.otp_sent'),
                'expiresAt' => $expiresAt,
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'client.otp.exception', ['error' => $e->getMessage()]);
            return response()->json([
                'result' => false,
                'message' => __('messages.error_occurred'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'otp.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.otp')]),
        ]);

        if ($validation) return $validation;

        try {
            $client = Client::where('email', $request->email)->first();

            if (!$client || !$client->is_active) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.account_inactive')
                ]);
            }

            if ($this->otpService->verifyOtp($request->email, $request->otp)) {
                $client->update([
                    'email_verified_at' => now(),
                    'last_login' => now(),
                ]);

                Auth::guard('client')->login($client);
                $token = $client->createToken('client_token')->plainTextToken;

                $this->sessionService->logSessionActivity($request, 'client.login.success', [], $client);

                return response()->json([
                    'result' => true,
                    'message' => __('messages.otp_verified'),
                    'client' => [
                        'token' => $token,
                        ...(new ClientResource($client))->toArray($request),
                    ],
                ]);
            }

            return response()->json([
                'result' => false,
                'message' => __('messages.invalid_otp_or_expired')
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'client.otp.exception', ['error' => $e->getMessage()]);
            return response()->json([
                'result' => false,
                'message' => __('messages.error_occurred'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }

    public function googleLogin(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email',
            'name' => 'required|string',
            'social_id' => 'required|string',
        ], []);

        if ($validation) return $validation;

        try {
            $client = Client::updateOrCreate(
                ['email' => $request->email],
                [
                    'name' => $request->name,
                    'social_id' => $request->social_id,
                    'social_provider' => 'google',
                    'email_verified_at' => now(),
                    'last_login' => now(),
                    'is_active' => true,
                ]
            );

            $token = $client->createToken('client_token')->plainTextToken;

            $this->sessionService->logSessionActivity($request, 'client.google.success', [], $client);

            return response()->json([
                'result' => true,
                'message' => __('messages.login_success'),
                'client' => [
                    'token' => $token,
                    ...(new ClientResource($client))->toArray($request),
                ],
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'client.google.exception', ['error' => $e->getMessage()]);
            return response()->json([
                'result' => false,
                'message' => __('messages.error_occurred'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'result' => true,
            'client' => new ClientResource($request->user())
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $this->sessionService->logSessionActivity($request, 'client.logout');

        return response()->json(['result' => true, 'message' => __('messages.logout_success')]);
    }
}
