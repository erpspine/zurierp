<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CompanyUser;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var CompanyUser $user */
        $user = $request->user('tenant');

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
        /** @var CompanyUser $user */
        $user = $request->user('tenant');

        $data = $this->validatePayload($request, $user->company_id);
        $data['company_id'] = $user->company_id;

        $data['uploaded_documents'] = $this->handleUploads($request);

        $lead = Lead::query()->create($data);

        AuditLog::query()->create([
            'actor_guard' => 'tenant',
            'actor_id' => $user->id,
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
        /** @var CompanyUser $user */
        $user = $request->user('tenant');

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
        /** @var CompanyUser $user */
        $user = $request->user('tenant');

        $row = $this->findLeadForTenant($request, $lead);

        AuditLog::query()->create([
            'actor_guard' => 'tenant',
            'actor_id' => $user->id,
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
        /** @var CompanyUser $user */
        $user = $request->user('tenant');

        return Lead::query()
            ->where('company_id', $user->company_id)
            ->where('id', $leadId)
            ->firstOrFail();
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
