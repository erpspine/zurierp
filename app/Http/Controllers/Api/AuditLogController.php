<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'actor_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ]);

        $query = AuditLog::query()
            ->where('actor_guard', 'platform')
            ->latest();

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('action', 'like', "%{$search}%")
                    ->orWhere('auditable_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['action']) && $filters['action'] !== 'all') {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $logs = $query->paginate($perPage);

        $actorIds = collect($logs->items())
            ->pluck('actor_id')
            ->filter()
            ->unique()
            ->values();

        $actors = PlatformUser::query()
            ->withTrashed()
            ->whereIn('id', $actorIds)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $rows = collect($logs->items())->map(function (AuditLog $log) use ($actors): array {
            $actor = $log->actor_id ? $actors->get($log->actor_id) : null;

            return [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'event_data' => $log->event_data,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
                'actor' => [
                    'id' => $actor?->id,
                    'name' => $actor?->name,
                    'email' => $actor?->email,
                ],
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
