<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #1f2937;">
<p>Hello,</p>
<p>We detected a login attempt from a new device. Use the one-time code below to continue:</p>
<p style="font-size: 24px; letter-spacing: 6px; font-weight: bold; margin: 20px 0;">{{ $otpCode }}</p>
<p>This code expires at {{ $expiresAt }}.</p>
@if($deviceName)
<p>Device: {{ $deviceName }}</p>
@endif
@if($ipAddress)
<p>IP: {{ $ipAddress }}</p>
@endif
<p>If this was not you, please reset your password immediately.</p>
</body>
</html>