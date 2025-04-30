<?php

return [

    'default' => [
        'created' => ':model record has been created',
        'updated' => ':model record has been updated',
        'deleted' => ':model record has been deleted',
    ],

    'models' => [
        'otp' => 'OTP',
        'team' => 'Team',
        'session' => 'Session',
        'user' => 'User account',
        'login' => 'Login attempt',
        'forgot' => 'Forgot password',
        'reset' => 'Password reset',
    ],

    'login' => [
        'success' => 'User logged in successfully',
        'failed' => [
            'credentials' => 'Login failed: Invalid credentials',
            'inactive' => 'Login failed: Account is inactive',
        ],
        'exception' => 'Login failed due to an exception',
    ],

    'otp' => [
        'success' => 'OTP verified successfully and user logged in',
        'failed' => [
            'credentials' => 'OTP verification failed: Invalid credentials',
            'inactive' => 'OTP verification failed: Account is inactive',
            'expired' => 'OTP verification failed: Invalid or expired OTP',
        ],
        'exception' => 'OTP verification failed due to an exception',
    ],

    'forgot' => [
        'success' => 'Password reset link sent to user',
        'failed' => [
            'validation' => 'Forgot password failed due to validation errors',
        ],
        'exception' => 'Forgot password failed due to an exception',
    ],

    'reset' => [
        'success' => 'Password reset successfully',
        'failed' => 'Password reset failed',
        'failed_validation' => 'Password reset failed due to validation errors',
        'exception' => 'Password reset failed due to an exception',
        'sent'=>"Password reset email sent"
    ],

];
