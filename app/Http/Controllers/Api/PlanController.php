<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name']);

        $plan = Plan::query()->create($data);

        $this->writeAudit($request, 'plan.created', $plan, ['new' => $plan->toArray()]);

        return response()->json($plan, 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $old = $plan->toArray();
        $data = $this->validatePayload($request, $plan);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name'], $plan->id);

        $plan->update($data);

        $this->writeAudit($request, 'plan.updated', $plan, [
            'old' => $old,
            'new' => $plan->fresh()->toArray(),
        ]);

        return response()->json($plan->fresh());
    }

    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        $old = $plan->toArray();
        $plan->delete();

        $this->writeAudit($request, 'plan.deleted', null, [
            'old' => $old,
            'plan_id' => $plan->id,
        ]);

        return response()->json([
            'message' => 'Plan deleted successfully.',
        ]);
    }

    private function validatePayload(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->ignore($plan?->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('plans', 'slug')->ignore($plan?->id),
            ],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'is_custom_pricing' => ['required', 'boolean'],
            'users_limit' => ['nullable', 'integer', 'min:1'],
            'branches_limit' => ['nullable', 'integer', 'min:1'],
            'vehicles_limit' => ['nullable', 'integer', 'min:1'],
            'bookings_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['required', 'string', 'max:255'],
            'is_featured' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(?string $slugInput, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slugInput ?: $name);
        $base = $base !== '' ? $base : 'plan';

        $slug = $base;
        $counter = 1;

        while (
            Plan::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function writeAudit(Request $request, string $action, ?Plan $plan = null, array $eventData = []): void
    {
        AuditLog::query()->create([
            'actor_guard' => 'platform',
            'actor_id' => $request->user('platform')?->id,
            'action' => $action,
            'auditable_type' => Plan::class,
            'auditable_id' => $plan ? (string) $plan->id : null,
            'event_data' => $eventData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
