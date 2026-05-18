<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CompanyUser;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TenantLeadController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $baseQuery = Lead::query()->where('company_id', $user->company_id);
        $totalLeads = (clone $baseQuery)->count();
        $newLeadsToday = (clone $baseQuery)->whereDate('created_at', today())->count();
        $quotationsSent = (clone $baseQuery)->where('quotation_status', 'sent')->count();
        $wonLeads = (clone $baseQuery)->where('lead_status', 'won')->count();

        $summary = [
            'total_leads' => $totalLeads,
            'new_leads_today' => $newLeadsToday,
            'quotations_sent' => $quotationsSent,
            'won_leads' => $wonLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0.0,
        ];

        $pipelineDefinitions = [
            ['key' => 'new', 'label' => 'New Leads', 'color' => '#0f5b46'],
            ['key' => 'contacted', 'label' => 'Contacted', 'color' => '#17765f'],
            ['key' => 'quoted', 'label' => 'Quoted', 'color' => '#2ecc71'],
            ['key' => 'won', 'label' => 'Won', 'color' => '#f4c20d'],
            ['key' => 'lost', 'label' => 'Lost', 'color' => '#e74c3c'],
        ];

        $salesPipeline = collect($pipelineDefinitions)
            ->map(function (array $definition) use ($baseQuery, $totalLeads): array {
                $count = (clone $baseQuery)->where('lead_status', $definition['key'])->count();

                return [
                    'key' => $definition['key'],
                    'label' => $definition['label'],
                    'value' => $count,
                    'percentage' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0.0,
                    'color' => $definition['color'],
                ];
            })
            ->values()
            ->all();

        $sourceCounts = (clone $baseQuery)
            ->get(['lead_source'])
            ->groupBy('lead_source')
            ->map->count()
            ->sortDesc()
            ->take(5);

        $leadSources = $sourceCounts
            ->map(function (int $count, string $source) use ($totalLeads): array {
                return [
                    'label' => $source,
                    'value' => $count,
                    'percentage' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        $destinationCounts = (clone $baseQuery)
            ->get(['preferred_destinations'])
            ->flatMap(function (Lead $lead): array {
                return $lead->preferred_destinations ?? [];
            })
            ->filter()
            ->groupBy(fn (string $destination): string => $destination)
            ->map->count()
            ->sortDesc()
            ->take(5);

        $leadDestinations = $destinationCounts
            ->map(function (int $count, string $destination) use ($totalLeads): array {
                return [
                    'label' => $destination,
                    'value' => $count,
                    'percentage' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        $trendMonths = collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonthsNoOverflow($offset)->startOfMonth());

        $trendLeadDates = (clone $baseQuery)
            ->whereBetween('created_at', [$trendMonths->first(), now()->endOfMonth()])
            ->get(['created_at']);

        $trendGroups = $trendLeadDates->groupBy(function (Lead $lead): string {
            return $lead->created_at?->format('Y-m') ?? '';
        });

        $leadTrend = $trendMonths
            ->map(function ($month) use ($trendGroups): array {
                $monthKey = $month->format('Y-m');

                return [
                    'label' => $month->format('M Y'),
                    'value' => $trendGroups->get($monthKey, collect())->count(),
                ];
            })
            ->values()
            ->all();

        $followUpsDueToday = (clone $baseQuery)
            ->with('assignedSalesPerson:id,name,email')
            ->whereDate('follow_up_date', today())
            ->orderBy('follow_up_time')
            ->limit(5)
            ->get()
            ->map(function (Lead $lead): array {
                return [
                    'id' => $lead->id,
                    'full_name' => $lead->full_name,
                    'customer_id' => $lead->customer_id,
                    'lead_status' => $lead->lead_status,
                    'priority' => $lead->priority,
                    'follow_up_date' => optional($lead->follow_up_date)->toDateString(),
                    'follow_up_time' => optional($lead->follow_up_time)?->format('H:i'),
                    'assigned_sales_person' => $lead->assignedSalesPerson ? [
                        'id' => $lead->assignedSalesPerson->id,
                        'name' => $lead->assignedSalesPerson->name,
                        'email' => $lead->assignedSalesPerson->email,
                    ] : null,
                ];
            })
            ->values()
            ->all();

        $recentActivities = AuditLog::query()
            ->where('company_id', $user->company_id)
            ->where('auditable_type', Lead::class)
            ->whereIn('action', ['lead.created', 'lead.updated', 'lead.deleted'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (AuditLog $log): array {
                $eventData = $log->event_data ?? [];
                $leadName = $eventData['full_name'] ?? 'Lead';

                $message = match ($log->action) {
                    'lead.created' => "Lead created for {$leadName}",
                    'lead.updated' => "Lead updated for {$leadName}",
                    'lead.deleted' => "Lead deleted for {$leadName}",
                    default => Str::headline(str_replace('.', ' ', $log->action)),
                };

                return [
                    'action' => $log->action,
                    'title' => $message,
                    'lead_id' => $eventData['lead_reference'] ?? null,
                    'full_name' => $leadName,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'summary' => $summary,
            'sales_pipeline' => $salesPipeline,
            'leads_by_source' => $leadSources,
            'follow_ups_due_today' => $followUpsDueToday,
            'recent_activities' => $recentActivities,
            'leads_by_destination' => $leadDestinations,
            'leads_trend' => $leadTrend,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'lead_status' => ['nullable', 'string', 'max:60'],
            'priority' => ['nullable', 'string', 'max:30'],
            'assigned_sales_person_id' => ['nullable', 'integer'],
            'follow_up_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Lead::query()
            ->where('company_id', $user->company_id)
            ->with(['assignedSalesPerson:id,name,email'])
            ->latest();

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_whatsapp', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhere('lead_id', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['lead_status']) && $filters['lead_status'] !== 'all') {
            $query->where('lead_status', $filters['lead_status']);
        }

        if (! empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_sales_person_id'])) {
            $query->where('assigned_sales_person_id', $filters['assigned_sales_person_id']);
        }

        if (! empty($filters['follow_up_date'])) {
            $query->whereDate('follow_up_date', $filters['follow_up_date']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $leads = $query->paginate($perPage);

        return response()->json($leads);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $data = $this->validatePayload($request, $user->company_id);
        $data['company_id'] = $user->company_id;

        $data['uploaded_documents'] = $this->handleUploads($request);

        $lead = Lead::query()->create($data);

        AuditLog::query()->create([
            'actor_guard' => 'tenant',
            'actor_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => 'lead.created',
            'auditable_type' => Lead::class,
            'auditable_id' => $lead->id,
            'event_data' => [
                'lead_id' => $lead->id,
                'lead_reference' => $lead->lead_id,
                'full_name' => $lead->full_name,
                'customer_id' => $lead->customer_id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Lead created successfully.',
            'lead' => $lead->load('assignedSalesPerson:id,name,email'),
        ], 201);
    }

    public function show(Request $request, string $lead): JsonResponse
    {
        $row = $this->findLeadForTenant($request, $lead);

        return response()->json($row->load('assignedSalesPerson:id,name,email'));
    }

    public function update(Request $request, string $lead): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $row = $this->findLeadForTenant($request, $lead);

        $data = $this->validatePayload($request, $user->company_id, $row->id);

        $newDocuments = $this->handleUploads($request);
        if (! empty($newDocuments)) {
            $existing = $row->uploaded_documents ?? [];
            $data['uploaded_documents'] = array_values(array_merge($existing, $newDocuments));
        }

        $row->update($data);

        AuditLog::query()->create([
            'actor_guard' => 'tenant',
            'actor_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => 'lead.updated',
            'auditable_type' => Lead::class,
            'auditable_id' => $row->id,
            'event_data' => [
                'lead_id' => $row->id,
                'lead_reference' => $row->lead_id,
                'full_name' => $row->full_name,
                'customer_id' => $row->customer_id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Lead updated successfully.',
            'lead' => $row->fresh()->load('assignedSalesPerson:id,name,email'),
        ]);
    }

    public function destroy(Request $request, string $lead): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $row = $this->findLeadForTenant($request, $lead);

        AuditLog::query()->create([
            'actor_guard' => 'tenant',
            'actor_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => 'lead.deleted',
            'auditable_type' => Lead::class,
            'auditable_id' => $row->id,
            'event_data' => [
                'lead_id' => $row->id,
                'lead_reference' => $row->lead_id,
                'full_name' => $row->full_name,
                'customer_id' => $row->customer_id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Lead::query()
            ->where('company_id', $user->company_id)
            ->where('id', $row->id)
            ->delete();

        return response()->json(['message' => 'Lead deleted successfully.']);
    }

    private function findLeadForTenant(Request $request, string $leadId): Lead
    {
        $user = $this->currentTenantUser($request);

        return Lead::query()
            ->where('company_id', $user->company_id)
            ->where('id', $leadId)
            ->firstOrFail();
    }

    private function currentTenantUser(Request $request): CompanyUser
    {
        /** @var CompanyUser $user */
        $user = $request->user();

        return $user;
    }

    private function validatePayload(Request $request, string $companyId, ?string $leadId = null): array
    {
        return $request->validate([
            'customer_id' => ['required', 'string', 'max:120'],
            'lead_id' => [
                'required',
                'string',
                'max:120',
                Rule::unique('leads', 'lead_id')
                    ->where('company_id', $companyId)
                    ->ignore($leadId),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('leads', 'email')
                    ->where('company_id', $companyId)
                    ->where('customer_id', $request->input('customer_id'))
                    ->ignore($leadId),
            ],
            'phone_whatsapp' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'lead_source' => ['required', 'string', 'max:120'],
            'travel_start_date' => ['required', 'date'],
            'travel_end_date' => ['required', 'date', 'after_or_equal:travel_start_date'],
            'number_of_days' => ['nullable', 'integer', 'min:0'],
            'number_of_nights' => ['nullable', 'integer', 'min:0'],
            'number_of_pax' => ['required', 'integer', 'min:1'],
            'adults' => ['required', 'integer', 'min:0'],
            'children' => ['nullable', 'integer', 'min:0'],
            'infants' => ['nullable', 'integer', 'min:0'],
            'preferred_destinations' => ['required', 'array', 'min:1'],
            'preferred_destinations.*' => ['string', 'max:120'],
            'trip_type' => ['required', 'string', 'max:100'],
            'residency_type' => ['required', 'string', 'max:120'],
            'budget_range' => ['required', 'string', 'max:120'],
            'estimated_budget_amount' => ['nullable', 'numeric', 'min:0'],
            'preferred_vehicle' => ['nullable', 'string', 'max:120'],
            'accommodation_type' => ['required', 'string', 'max:120'],
            'room_preference' => ['nullable', 'string', 'max:120'],
            'meal_plan' => ['nullable', 'string', 'max:120'],
            'activities_interested_in' => ['required', 'array', 'min:1'],
            'activities_interested_in.*' => ['string', 'max:120'],
            'special_interests' => ['nullable', 'string'],
            'dietary_requirement' => ['nullable', 'string'],
            'language_preference' => ['nullable', 'string', 'max:120'],
            'guide_preference' => ['nullable', 'string', 'max:120'],
            'lead_status' => ['required', 'string', 'max:60'],
            'assigned_sales_person_id' => [
                'required',
                'integer',
                Rule::exists('company_users', 'id')->where('company_id', $companyId),
            ],
            'priority' => ['required', 'string', 'max:30'],
            'follow_up_date' => ['required', 'date'],
            'follow_up_time' => ['nullable', 'date_format:H:i'],
            'next_action' => ['required', 'string', 'max:255'],
            'quotation_status' => ['nullable', 'string', 'max:60'],
            'probability_of_winning' => ['nullable', 'integer', 'min:0', 'max:100'],
            'client_request_summary' => ['nullable', 'string'],
            'passport_visa_notes' => ['nullable', 'string'],
            'internal_sales_notes' => ['nullable', 'string'],
            'payment_special_conditions' => ['nullable', 'string'],
            'uploaded_documents' => ['nullable', 'array'],
            'uploaded_documents.*' => ['string', 'max:500'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:5120'],
        ]);
    }

    private function handleUploads(Request $request): array
    {
        $documentPaths = [];

        if ($request->filled('uploaded_documents')) {
            $documentPaths = array_values($request->input('uploaded_documents', []));
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $documentPaths[] = $file->store('leads/documents', 'public');
            }
        }

        return $documentPaths;
    }
}
