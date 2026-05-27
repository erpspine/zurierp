<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Activated</title>
</head>
<body style="margin: 0; padding: 24px; background: #f3f6f4; font-family: Arial, sans-serif; color: #173328;">
<div style="max-width: 680px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #0f3a28 0%, #1a6645 100%); border-radius: 18px 18px 0 0; padding: 24px 28px; color: #ffffff;">
        <div style="font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; opacity: 0.78; margin-bottom: 10px;">Zuri ERP</div>
        <h1 style="margin: 0; font-size: 28px; line-height: 1.2; font-weight: 700;">Subscription activated</h1>
        <p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: rgba(255,255,255,0.86);">Your company now has access to the platform with the subscription below.</p>
    </div>

    <div style="background: #ffffff; border: 1px solid #dfe9e3; border-top: none; border-radius: 0 0 18px 18px; padding: 28px; box-shadow: 0 20px 45px rgba(18, 42, 31, 0.08);">
        <p style="margin: 0 0 22px; font-size: 15px; line-height: 1.7; color: #486558;">Your subscription for <strong style="color: #0f3a28;">{{ $subscription->company->name }}</strong> is now active.</p>

        <div style="border: 1px solid #e3ece7; border-radius: 16px; overflow: hidden; background: #fbfdfc; margin-bottom: 22px;">
            <div style="padding: 14px 18px; background: #f4f8f5; border-bottom: 1px solid #e3ece7; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #4b685a;">Subscription Details</div>
            <div style="padding: 8px 18px 4px;">
                <div style="padding: 10px 0; border-bottom: 1px solid #ebf2ee;">
                    <div style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #668073; margin-bottom: 4px;">Plan</div>
                    <div style="font-size: 16px; font-weight: 700; color: #0d2f21;">{{ $subscription->plan?->name ?? 'Custom Plan' }}</div>
                </div>
                <div style="padding: 10px 0; border-bottom: 1px solid #ebf2ee;">
                    <div style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #668073; margin-bottom: 4px;">License Key</div>
                    <div style="font-size: 15px; font-weight: 700; color: #0d2f21;">{{ $subscription->license_key }}</div>
                </div>
                <div style="padding: 10px 0; border-bottom: 1px solid #ebf2ee;">
                    <div style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #668073; margin-bottom: 4px;">Period</div>
                    <div style="font-size: 15px; font-weight: 700; color: #0d2f21;">{{ optional($subscription->starts_at)->format('d/m/Y') }} to {{ optional($subscription->ends_at)->format('d/m/Y') }}</div>
                </div>
                <div style="padding: 10px 0 14px;">
                    <div style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #668073; margin-bottom: 4px;">Amount Paid</div>
                    <div style="display: inline-block; padding: 10px 14px; border-radius: 10px; background: #eef6f1; border: 1px solid #d6e7dc; font-size: 15px; font-weight: 700; color: #0d2f21;">{{ $subscription->currency }} {{ number_format((float) $subscription->amount_paid, 2) }}</div>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 22px; padding: 18px; border-radius: 14px; background: linear-gradient(180deg, #f8fbf9 0%, #f2f7f4 100%); border: 1px solid #e0ebe4;">
            <div style="font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; color: #668073; margin-bottom: 8px; font-weight: 700;">Login URL</div>
            <a href="{{ $loginUrl }}" style="font-size: 15px; color: #14573a; font-weight: 700; text-decoration: none;">{{ $loginUrl }}</a>
        </div>

        <p style="margin: 0; font-size: 14px; line-height: 1.7; color: #486558;">Regards,<br><strong style="color: #0f3a28;">Zuri ERP Team</strong></p>
    </div>
</div>
</body>
</html>