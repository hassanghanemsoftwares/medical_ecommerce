<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\UserResource as V1UserResource;
use App\Services\OtpService;
use App\Services\UserSessionService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpEmail;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
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

    public function login(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'password.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.password')]),
        ]);

        if ($validation) {
            return $validation;
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                $this->sessionService->logSessionActivity($request, 'login.failed.credentials');
                return response()->json([
                    'result' => false,
                    'message' => __('messages.invalid_credentials')
                ]);
            }

            if (!$user->is_active) {
                $this->sessionService->logSessionActivity($request, 'login.failed.inactive', [], $user, $user);
                return response()->json([
                    'result' => false,
                    'message' => __('messages.account_inactive')
                ]);
            }

            $generateOtp = $this->otpService->generateOtp($request->email);
            $otp = $generateOtp[0];
            $expiresAt = $generateOtp[1];

            Mail::to($user->email)->send(new OtpEmail($otp, $user->name));

            return response()->json([
                'result' => true,
                'message' => __('messages.otp_sent'),
                'expiresAt' => $expiresAt,
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'login.exception', ['error' => $e->getMessage()]);
            return $this->errorResponse( __('messages.error_occurred'), $e);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email',
            'password' => 'required|string',
            'otp' => 'required|numeric',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'password.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.password')]),
            'otp.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.otp')]),
        ]);

        if ($validation) {
            return $validation;
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                $this->sessionService->logSessionActivity($request, 'otp.failed.credentials');
                return response()->json([
                    'result' => false,
                    'message' => __('messages.invalid_credentials')
                ]);
            }

            if (!$user->is_active) {
                $this->sessionService->logSessionActivity($request, 'otp.failed.inactive', [], $user, $user);
                return response()->json([
                    'result' => false,
                    'message' => __('messages.account_inactive')
                ]);
            }

            if ($this->otpService->verifyOtp($request->email, $request->otp)) {
                Auth::login($user);
                setPermissionsTeamId($user->teams()->first()?->id);

                $expiresAt =  now()->addMinutes(120);
                $accessToken = $user->createToken('authToken', ['*'], $expiresAt);
                $token = $accessToken->plainTextToken;

                $this->sessionService->logSessionActivity($request, 'login.success', [], $user, $user);

                $userData = [
                    'token' => $token,
                    ...(new V1UserResource($user))->toArray($request),
                ];

                return response()->json([
                    'result' => true,
                    'message' => __('messages.otp_verified'),
                    'user' => $userData,
                ]);
            }

            $this->sessionService->logSessionActivity($request, 'otp.failed.expired');
            return response()->json([
                'result' => false,
                'message' => __('messages.invalid_otp_or_expired')
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'otp.exception', ['error' => $e->getMessage()]);
            return $this->errorResponse( __('messages.error_occurred'), $e);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'email.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.email')]),
        ]);

        if ($validation) {
            return $validation;
        }

        try {
            $user = User::where('email', $request->email)->first();
            $token = Password::createToken($user);
            $user->sendPasswordResetNotification($token);

            $this->sessionService->logSessionActivity($request, 'forgot.success', [], $user, $user);

            return response()->json([
                'result' => true,
                'message' => __('messages.password_reset_link_sent')
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'forgot.exception', ['error' => $e->getMessage()]);
            return $this->errorResponse( __('messages.error_occurred'), $e);
        }
    }

    public function resetPassword(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'token.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.token')]),
            'password.confirmed' => __('messages.validation.confirmed', ['attribute' => __('messages.validation.attributes.password')]),
        ]);

        if ($validation) {
            return $validation;
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'result' => true,
                    'message' => __("messages.password_reset.success")
                ]);
            }

            return response()->json([
                'result' => false,
                'message' => __("messages.password_reset.error")
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.error_occurred'), $e);
        }
    }
}
