<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CompanyUser;
use App\Models\Itinerary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantItineraryController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);
        $today = today();

        $baseQuery = Itinerary::query()->where('company_id', $user->company_id);
        $totalItineraries = (clone $baseQuery)->count();

        $draftCount = (clone $baseQuery)
            ->whereDate('start_date', '>', $today)
            ->doesntHave('days')
            ->count();

        $confirmedCount = (clone $baseQuery)
            ->whereDate('start_date', '>', $today)
            ->has('days')
            ->count();

        $inUseCount = (clone $baseQuery)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $completedCount = (clone $baseQuery)
            ->whereDate('end_date', '<', $today)
            ->count();

        $statusBreakdown = [
            ['key' => 'draft', 'label' => 'Draft', 'value' => $draftCount, 'color' => '#94a3b8'],
            ['key' => 'confirmed', 'label' => 'Confirmed', 'value' => $confirmedCount, 'color' => '#10b981'],
            ['key' => 'in_use', 'label' => 'In Use', 'value' => $inUseCount, 'color' => '#3b82f6'],
            ['key' => 'completed', 'label' => 'Completed', 'value' => $completedCount, 'color' => '#8b5cf6'],
        ];

        $itinerariesByStatus = collect($statusBreakdown)
            ->map(function (array $status) use ($totalItineraries): array {
                return [
                    'key' => $status['key'],
                    'label' => $status['label'],
                    'value' => $status['value'],
                    'percentage' => $totalItineraries > 0 ? round(($status['value'] / $totalItineraries) * 100, 1) : 0.0,
                    'color' => $status['color'],
                ];
            })
            ->values()
            ->all();

        $upcomingItineraries = (clone $baseQuery)
            ->withCount('days')
            ->whereDate('start_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(function (Itinerary $itinerary): array {
                $status = $this->futureStatus($itinerary);

                return [
                    'id' => $itinerary->id,
                    'name' => $itinerary->name,
                    'start_date' => optional($itinerary->start_date)->toDateString(),
                    'end_date' => optional($itinerary->end_date)->toDateString(),
                    'status' => $status,
                    'status_label' => Str::headline(str_replace('_', ' ', $status)),
                    'days_count' => $itinerary->days_count,
                ];
            })
            ->values()
            ->all();

        $destinationCounts = (clone $baseQuery)
            ->with('lead:id,preferred_destinations')
            ->get()
            ->flatMap(function (Itinerary $itinerary): array {
                return $itinerary->lead?->preferred_destinations ?? [];
            })
            ->filter()
            ->groupBy(fn (string $destination): string => $destination)
            ->map->count()
            ->sortDesc()
            ->take(5);

        $topDestinations = $destinationCounts
            ->map(function (int $count, string $destination): array {
                return [
                    'label' => $destination,
                    'value' => $count,
                ];
            })
            ->values()
            ->all();

        $recentActivities = AuditLog::query()
            ->where('company_id', $user->company_id)
            ->where('auditable_type', Itinerary::class)
            ->whereIn('action', ['itinerary.created', 'itinerary.updated', 'itinerary.deleted'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (AuditLog $log): array {
                $eventData = $log->event_data ?? [];
                $itineraryName = $eventData['name'] ?? 'Itinerary';

                $title = match ($log->action) {
                    'itinerary.created' => "New itinerary \"{$itineraryName}\" created",
                    'itinerary.updated' => "Itinerary \"{$itineraryName}\" updated",
                    'itinerary.deleted' => "Itinerary \"{$itineraryName}\" deleted",
                    default => Str::headline(str_replace('.', ' ', $log->action)),
                };

                return [
                    'action' => $log->action,
                    'title' => $title,
                    'itinerary_id' => $eventData['itinerary_id'] ?? null,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $trendMonths = collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonthsNoOverflow($offset)->startOfMonth());

        $trendRows = (clone $baseQuery)
            ->whereBetween('created_at', [$trendMonths->first(), now()->endOfMonth()])
            ->get(['created_at']);

        $trendGroups = $trendRows->groupBy(function (Itinerary $itinerary): string {
            return $itinerary->created_at?->format('Y-m') ?? '';
        });

        $itinerariesByMonth = $trendMonths
            ->map(function ($month) use ($trendGroups): array {
                $monthKey = $month->format('Y-m');

                return [
                    'label' => $month->format('M Y'),
                    'value' => $trendGroups->get($monthKey, collect())->count(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'summary' => [
                'total_itineraries' => $totalItineraries,
                'draft' => $draftCount,
                'confirmed' => $confirmedCount,
                'in_use' => $inUseCount,
                'completed' => $completedCount,
            ],
            'itineraries_by_status' => $itinerariesByStatus,
            'upcoming_itineraries' => $upcomingItineraries,
            'top_destinations' => $topDestinations,
            'recent_activities' => $recentActivities,
            'itineraries_by_month' => $itinerariesByMonth,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function currentTenantUser(Request $request): CompanyUser
    {
        /** @var CompanyUser $user */
        $user = $request->user();

        return $user;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $itineraries = Itinerary::query()
            ->where('company_id', $user->company_id)
            ->with(['createdBy:id,name,email', 'lead:id,lead_id,full_name', 'days:id,itinerary_id,day_number,title,date'])
            ->latest()
            ->paginate(20);

        return response()->json($itineraries);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $data = $request->validate([
            'lead_id' => [
                'required',
                'uuid',
                Rule::exists('leads', 'id')
                    ->where('company_id', $user->company_id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;

        $itinerary = Itinerary::query()->create($data);

        $this->logItineraryAudit($request, $user, 'itinerary.created', $itinerary);

        return response()->json([
            'message' => 'Itinerary created successfully.',
            'itinerary' => $itinerary->load('createdBy:id,name,email', 'lead:id,lead_id,full_name'),
        ], 201);
    }

    public function show(Request $request, Itinerary $itinerary): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        if ($itinerary->company_id !== $user->company_id) {
            abort(404);
        }

        $itinerary->load([
            'createdBy:id,name,email',
            'lead:id,lead_id,full_name,email',
            'days' => function ($query) {
                $query->orderBy('day_number')
                    ->with([
                        'accommodations',
                        'activities' => fn ($q) => $q->orderBy('order'),
                        'transports' => fn ($q) => $q->orderBy('order'),
                        'meals',
                        'images' => fn ($q) => $q->orderBy('order'),
                    ]);
            },
        ]);

        return response()->json($itinerary);
    }

    public function update(Request $request, Itinerary $itinerary): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        if ($itinerary->company_id !== $user->company_id) {
            abort(404);
        }

        $data = $request->validate([
            'lead_id' => [
                'nullable',
                'uuid',
                Rule::exists('leads', 'id')
                    ->where('company_id', $user->company_id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $itinerary->update(array_filter($data, fn ($v) => $v !== null));

        $this->logItineraryAudit($request, $user, 'itinerary.updated', $itinerary->fresh());

        return response()->json([
            'message' => 'Itinerary updated successfully.',
            'itinerary' => $itinerary->fresh()->load('createdBy:id,name,email', 'lead:id,lead_id,full_name'),
        ]);
    }

    public function destroy(Request $request, Itinerary $itinerary): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        if ($itinerary->company_id !== $user->company_id) {
            abort(404);
        }

        $this->logItineraryAudit($request, $user, 'itinerary.deleted', $itinerary);

        $itinerary->delete();

        return response()->json(['message' => 'Itinerary deleted successfully.']);
    }

    private function logItineraryAudit(Request $request, CompanyUser $user, string $action, Itinerary $itinerary): void
    {
        AuditLog::query()->create([
            'actor_guard' => 'tenant',
            'actor_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => $action,
            'auditable_type' => Itinerary::class,
            'auditable_id' => $itinerary->id,
            'event_data' => [
                'itinerary_id' => $itinerary->id,
                'lead_id' => $itinerary->lead_id,
                'name' => $itinerary->name,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function futureStatus(Itinerary $itinerary): string
    {
        if (($itinerary->days_count ?? 0) > 0) {
            return 'confirmed';
        }

        return 'draft';
    }
}
