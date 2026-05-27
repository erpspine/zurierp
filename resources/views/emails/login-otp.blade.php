<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP</title>
</head>
<body style="margin: 0; padding: 24px; background: #f3f6f4; font-family: Arial, sans-serif; color: #173328;">
<div style="max-width: 680px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #0f3a28 0%, #1a6645 100%); border-radius: 18px 18px 0 0; padding: 24px 28px; color: #ffffff;">
        <div style="font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; opacity: 0.78; margin-bottom: 10px;">Zuri ERP</div>
        <h1 style="margin: 0; font-size: 28px; line-height: 1.2; font-weight: 700;">Verify your login</h1>
        <p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: rgba(255,255,255,0.86);">We detected a sign-in attempt from a new device. Enter this one-time code to continue.</p>
    </div>

    <div style="background: #ffffff; border: 1px solid #dfe9e3; border-top: none; border-radius: 0 0 18px 18px; padding: 28px; box-shadow: 0 20px 45px rgba(18, 42, 31, 0.08);">
        <div style="margin-bottom: 22px; border: 1px solid #e3ece7; border-radius: 16px; background: #fbfdfc; overflow: hidden;">
            <div style="padding: 14px 18px; background: #f4f8f5; border-bottom: 1px solid #e3ece7; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #4b685a;">One-Time Password</div>
            <div style="padding: 20px 18px 22px; text-align: center;">
                <div style="display: inline-block; padding: 14px 18px; border-radius: 12px; border: 1px solid #cfe3d7; background: #eef6f1; font-size: 34px; line-height: 1; letter-spacing: 0.32em; font-weight: 700; color: #0d2f21;">{{ $otpCode }}</div>
                <p style="margin: 14px 0 0; font-size: 13px; color: #4f6f60;">Expires at <strong style="color: #0f3a28;">{{ $expiresAt }}</strong></p>
            </div>
        </div>

        <div style="margin-bottom: 22px; padding: 18px; border-radius: 14px; background: linear-gradient(180deg, #f8fbf9 0%, #f2f7f4 100%); border: 1px solid #e0ebe4;">
            <div style="font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; color: #668073; margin-bottom: 10px; font-weight: 700;">Security Details</div>
            @if($deviceName)
                <p style="margin: 0 0 6px; font-size: 14px; color: #355547;"><strong style="color: #0f3a28;">Device:</strong> {{ $deviceName }}</p>
            @endif
            @if($ipAddress)
                <p style="margin: 0; font-size: 14px; color: #355547;"><strong style="color: #0f3a28;">IP Address:</strong> {{ $ipAddress }}</p>
            @endif
        </div>

        <p style="margin: 0 0 14px; font-size: 14px; line-height: 1.7; color: #486558;">If you did not request this login, reset your password immediately and contact support.</p>
        <p style="margin: 0; font-size: 14px; line-height: 1.7; color: #486558;">Regards,<br><strong style="color: #0f3a28;">Zuri ERP Team</strong></p>
    </div>
</div>
</body>
</html>