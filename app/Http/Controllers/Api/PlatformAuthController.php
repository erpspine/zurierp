<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = PlatformUser::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $plainToken = Str::random(80);

        $user->forceFill([
            'api_token' => hash('sha256', $plainToken),
            'last_login_at' => now(),
        ])->save();

        AuditLog::query()->create([
            'actor_guard' => 'platform',
            'actor_id' => $user->id,
            'action' => 'platform-auth.login',
            'auditable_type' => PlatformUser::class,
            'auditable_id' => (string) $user->id,
            'event_data' => [
                'email' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'token' => $plainToken,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user('platform'));
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PlatformUser $user */
        $user = $request->user('platform');

        AuditLog::query()->create([
            'actor_guard' => 'platform',
            'actor_id' => $user->id,
            'action' => 'platform-auth.logout',
            'auditable_type' => PlatformUser::class,
            'auditable_id' => (string) $user->id,
            'event_data' => [
                'email' => $user->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Logged out']);
    }
}
