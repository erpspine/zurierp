<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\CompanyUser;
use App\Models\LoginOtpChallenge;
use App\Models\TrustedDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'company_id' => ['required', 'uuid'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = CompanyUser::query()
            ->where('company_id', $credentials['company_id'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $device = $this->resolveDevice($request);

        $trustedDevice = TrustedDevice::query()
            ->where('guard', 'tenant')
            ->where('user_id', $user->id)
            ->where('device_hash', $device['hash'])
            ->first();

        if (! $trustedDevice) {
            $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $challenge = LoginOtpChallenge::query()->create([
                'guard' => 'tenant',
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'device_hash' => $device['hash'],
                'device_name' => $device['name'],
                'otp_code_hash' => Hash::make($otpCode),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Mail::to($user->email)->send(new LoginOtpMail(
                otpCode: $otpCode,
                expiresAt: $challenge->expires_at->toDateTimeString(),
                ipAddress: $request->ip(),
                deviceName: $device['name']
            ));

            return response()->json([
                'message' => 'OTP sent to your email. Verify to complete login.',
                'requires_otp' => true,
                'otp_challenge_id' => $challenge->id,
                'expires_at' => $challenge->expires_at->toIso8601String(),
            ], 202);
        }

        $trustedDevice->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_used_at' => now(),
        ])->save();

        return $this->issueTokenResponse($user);
    }

    public function verifyLoginOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'uuid'],
            'otp' => ['required', 'digits:6'],
        ]);

        /** @var LoginOtpChallenge|null $challenge */
        $challenge = LoginOtpChallenge::query()
            ->where('id', $data['challenge_id'])
            ->where('guard', 'tenant')
            ->where('consumed_at', null)
            ->first();

        if (! $challenge || $challenge->expires_at->isPast()) {
            return response()->json([
                'message' => 'Invalid or expired OTP challenge.',
            ], 422);
        }

        if ($challenge->attempts >= 5) {
            return response()->json([
                'message' => 'Too many failed attempts. Request a new OTP.',
            ], 429);
        }

        if (! Hash::check($data['otp'], $challenge->otp_code_hash)) {
            $challenge->attempts = (int) $challenge->attempts + 1;
            $challenge->save();

            return response()->json([
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $user = CompanyUser::query()
            ->where('id', $challenge->user_id)
            ->where('company_id', $challenge->company_id)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'User not found for this OTP challenge.',
            ], 404);
        }

        $challenge->consumed_at = now();
        $challenge->save();

        TrustedDevice::query()->updateOrCreate(
            [
                'guard' => 'tenant',
                'user_id' => $user->id,
                'device_hash' => $challenge->device_hash,
            ],
            [
                'company_id' => $user->company_id,
                'device_name' => $challenge->device_name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
            ]
        );

        return $this->issueTokenResponse($user);
    }

    private function issueTokenResponse(CompanyUser $user): JsonResponse
    {
        $plainToken = Str::random(80);

        $user->forceFill([
            'api_token' => hash('sha256', $plainToken),
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'token' => $plainToken,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var CompanyUser $user */
        $user = $request->user();

        return response()->json($user);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var CompanyUser $user */
        $user = $request->user();
        $user->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Logged out']);
    }

    private function resolveDevice(Request $request): array
    {
        $deviceName = trim((string) ($request->header('X-Device-Id') ?? $request->input('device_id') ?? $request->userAgent() ?? 'unknown-device'));

        if ($deviceName === '') {
            $deviceName = 'unknown-device';
        }

        return [
            'name' => Str::limit($deviceName, 255, ''),
            'hash' => hash('sha256', Str::lower($deviceName)),
        ];
    }
}
