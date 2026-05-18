<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformUser;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = PlatformUser::query()
            ->with(['roles' => fn ($query) => $query->platform()->select('roles.id', 'roles.name')])
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:platform_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where(fn ($query) => $query
                ->where('guard_name', 'platform')
                ->where('type', 'platform')
                ->whereNull('company_id'))],
        ]);

        $user = PlatformUser::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => $data['status'],
        ]);

        $user->roles()->sync(! empty($data['role_id']) ? [$data['role_id']] : []);

        $this->writeAudit(
            $request,
            'platform-user.created',
            $user,
            [
                'new' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role_id' => $data['role_id'] ?? null,
                ],
            ]
        );

        return response()->json(
            $user->load(['roles' => fn ($query) => $query->platform()->select('roles.id', 'roles.name')]),
            201
        );
    }

    public function update(Request $request, PlatformUser $platformUser): JsonResponse
    {
        $oldData = [
            'name' => $platformUser->name,
            'email' => $platformUser->email,
            'status' => $platformUser->status,
            'role_id' => $platformUser->roles()->value('roles.id'),
        ];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('platform_users', 'email')->ignore($platformUser->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where(fn ($query) => $query
                ->where('guard_name', 'platform')
                ->where('type', 'platform')
                ->whereNull('company_id'))],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $platformUser->update($payload);
        $platformUser->roles()->sync(! empty($data['role_id']) ? [$data['role_id']] : []);

        $this->writeAudit(
            $request,
            'platform-user.updated',
            $platformUser,
            [
                'old' => $oldData,
                'new' => [
                    'name' => $platformUser->name,
                    'email' => $platformUser->email,
                    'status' => $platformUser->status,
                    'role_id' => $data['role_id'] ?? null,
                ],
            ]
        );

        return response()->json(
            $platformUser->load(['roles' => fn ($query) => $query->platform()->select('roles.id', 'roles.name')])
        );
    }

    public function destroy(Request $request, PlatformUser $platformUser): JsonResponse
    {
        $actorId = $request->user('platform')?->id;

        if ($actorId === $platformUser->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $platformUser->delete();

        $this->writeAudit(
            $request,
            'platform-user.deleted',
            $platformUser,
            [
                'old' => [
                    'name' => $platformUser->name,
                    'email' => $platformUser->email,
                    'status' => $platformUser->status,
                ],
            ]
        );

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::query()
            ->platform()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    private function writeAudit(Request $request, string $action, ?PlatformUser $targetUser = null, array $eventData = []): void
    {
        AuditLog::query()->create([
            'actor_guard' => 'platform',
            'actor_id' => $request->user('platform')?->id,
            'action' => $action,
            'auditable_type' => $targetUser ? PlatformUser::class : null,
            'auditable_id' => $targetUser ? (string) $targetUser->id : null,
            'event_data' => $eventData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
