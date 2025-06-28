<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\ClientResource;
use App\Models\Client;
use App\Services\OtpService;
use App\Services\ClientSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpEmail;
use Illuminate\Support\Facades\Validator;
use Exception;

class ClientAuthController extends Controller
{
    protected $otpService;
    protected $sessionService;

    public function __construct(OtpService $otpService, ClientSessionService $sessionService)
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

    public function sendOtpLogin(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email|exists:clients,email',

        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'email.exists' => __('messages.validation.invalid_email'),
        ]);

        if ($validation) return $validation;

        try {
            $client = Client::where('email', $request->email)->first();

            $generateOtp = $this->otpService->generateOtp($request->email);
            $otp = $generateOtp[0];
            $expiresAt = $generateOtp[1];

            Mail::to($request->email)->send(new OtpEmail($otp, $client->name));

            $this->sessionService->logSessionActivity($request, 'client.otp.sent', [], $client);

            return response()->json([
                'result' => true,
                'message' => __('messages.otp_sent'),
                'expiresAt' => $expiresAt,
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'client.otp.exception', ['error' => $e->getMessage()]);
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function verifyOtpLogin(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'email' => 'required|email|exists:clients,email',
            'otp' => 'required|numeric',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'otp.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.otp')]),
        ]);

        if ($validation) return $validation;

        try {
            $client = Client::with('occupation')->where('email', $request->email)->first();


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

            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function sendOtpRegister(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'name' => 'required|string|min:2|max:255',
            'gender' => 'nullable|in:male,female,other',
            'birthdate' => 'nullable|date',
            'occupation_id' => 'required|exists:occupations,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:clients,email',
        ], [
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
        ]);

        if ($validation) return $validation;

        try {
            $client = Client::where('email', $request->email)->first();

            if ($client) {
                $name = $client->name;
                $logEvent = 'client.otp.sent';
            } else {
                $name = "Guest";
                $logEvent = 'client.otp.register.sent';
            }

            $generateOtp = $this->otpService->generateOtp($request->email);
            $otp = $generateOtp[0];
            $expiresAt = $generateOtp[1];

            Mail::to($request->email)->send(new OtpEmail($otp, $name));

            $this->sessionService->logSessionActivity($request, $logEvent, [], $client);

            return response()->json([
                'result' => true,
                'message' => __('messages.otp_sent'),
                'expiresAt' => $expiresAt,
            ]);
        } catch (Exception $e) {
            $this->sessionService->logSessionActivity($request, 'client.otp.exception', ['error' => $e->getMessage()]);
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
    public function verifyOtpRegister(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'name' => 'required|string|min:2|max:255',
            'gender' => 'nullable|in:male,female,other',
            'birthdate' => 'nullable|date',
            'occupation_id' => 'required|exists:occupations,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:clients,email',
            'otp' => 'required|numeric',
        ], [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name')]),
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'email.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.email')]),
            'otp.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.otp')]),
        ]);

        if ($validation) return $validation;

        try {
            if (!$this->otpService->verifyOtp($request->email, $request->otp)) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.invalid_otp_or_expired'),
                ]);
            }

            $client = Client::create([
                'name' => $request->name,
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'occupation_id' => $request->occupation_id,
                'phone' => $request->phone,
                'email' => $request->email,
                'email_verified_at' => now(),
                'last_login' => now(),
            ]);

            $token = $client->createToken('client_token')->plainTextToken;

            $this->sessionService->logSessionActivity($request, 'client.register.success', [], $client);
            $client->load('occupation');
            return response()->json([
                'result' => true,
                'message' => __('messages.otp_verified'),
                'client' => [
                    'token' => $token,
                    ...(new ClientResource($client))->toArray($request),
                ],
            ]);
        } catch (\Exception $e) {
            $this->sessionService->logSessionActivity($request, 'client.register.exception', ['error' => $e->getMessage()]);
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
