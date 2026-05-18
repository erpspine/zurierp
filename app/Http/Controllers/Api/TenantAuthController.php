<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
}
