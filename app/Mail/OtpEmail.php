<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OtpEmail extends Mailable
{
    public $otp;
    public $userName;

    public function __construct($otp, $userName)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->view('emails.otp')
            ->subject('Your OTP Code')
            ->with([
                'otp' => $this->otp,
                'userName' => $this->userName,
            ]);
    }
}
