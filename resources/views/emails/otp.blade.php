<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.otp.otp_subject') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background-color: #000000;
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .email-body {
            padding: 20px;
            line-height: 1.6;
            text-align: center;
        }

        .otp {
            font-size: 32px;
            font-weight: bold;
            color: #000000;
            margin: 20px 0;
            letter-spacing: 5px;
        }

        .email-footer {
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #777;
            background-color: #f7f7f7;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{{ __('messages.otp.welcome', ['name' => $userName]) }}</h1>
        </div>
        <div class="email-body">
            <p>{{ __('messages.otp.otp_message') }}</p>
            <div class="otp">{{ $otp }}</div>
            <p>{{ __('messages.otp.otp_validity') }}</p>
        </div>
        <div class="email-footer">
            <p>{{ __('messages.otp.thank_you') }}</p>
        </div>
    </div>
</body>

</html>
