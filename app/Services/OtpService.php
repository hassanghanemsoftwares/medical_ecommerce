<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    public function generateOtp($email)
    {

        if (Cache::get('otp_attempts_' . $email) >= 25) {
            throw new Exception('Too many OTP requests. Please try again later.');
        }
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);
        Otp::updateOrCreate(
            ['email' => $email],
            ['otp' => $otp, 'expires_at' => $expiresAt]
        );
        Cache::put('otp_attempts_' . $email, Cache::get('otp_attempts_' . $email, 0) + 1, now()->addHour());

        return [$otp, $expiresAt];
    }

    public function verifyOtp($email, $otp)
    {
        $otpRecord = Otp::where('email', $email)
            ->where('otp', $otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otpRecord) {
            $otpRecord->delete();
            return true;
        }

        return false;
    }
}
