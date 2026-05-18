<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::query()
            ->orderByDesc('created_at')
            ->get([
                'id', 'name', 'company_code', 'status', 'subscription_status',
                'plan_id', 'industry', 'email', 'phone', 'country', 'city', 'created_at',
            ]);

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Basic info
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tin' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'incorporation_date' => ['nullable', 'date'],
            // Address
            'country' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'google_map_location' => ['nullable', 'string', 'max:500'],
            // Contact
            'phone' => ['nullable', 'string', 'max:40'],
            'alt_phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            // Branding (file uploads)
            'logo' => ['nullable', 'file', 'image', 'max:2048'],
            'email_logo' => ['nullable', 'file', 'image', 'max:2048'],
            'document_logo' => ['nullable', 'file', 'image', 'max:2048'],
            // Finance
            'default_currency' => ['required', 'string', 'max:10'],
            'multi_currency_enabled' => ['required', 'boolean'],
            'financial_year_start' => ['required', 'integer', 'min:1', 'max:12'],
            'tax_enabled' => ['required', 'boolean'],
            // First admin user
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'max:40'],
            'admin_password' => ['required', 'string', 'min:8'],
            'admin_role' => ['nullable', 'string', 'max:100'],
            // Notifications
            'notify_email' => ['required', 'boolean'],
            'notify_whatsapp' => ['required', 'boolean'],
            'notify_sms' => ['required', 'boolean'],
            'notify_on' => ['nullable', 'array'],
            'notify_on.*' => ['string'],
        ]);

        DB::beginTransaction();

        try {
            $logoPath = null;
            $emailLogoPath = null;
            $documentLogoPath = null;

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('companies/logos', 'public');
            }

            if ($request->hasFile('email_logo')) {
                $emailLogoPath = $request->file('email_logo')->store('companies/logos', 'public');
            }

            if ($request->hasFile('document_logo')) {
                $documentLogoPath = $request->file('document_logo')->store('companies/logos', 'public');
            }

            $company = Company::query()->create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'company_code' => $this->generateCompanyCode(),
                'registration_number' => $data['registration_number'] ?? null,
                'tin' => $data['tin'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'industry' => $data['industry'] ?? null,
                'business_type' => $data['business_type'] ?? null,
                'incorporation_date' => $data['incorporation_date'] ?? null,
                'country' => $data['country'] ?? null,
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'address_line_1' => $data['address_line_1'] ?? null,
                'address_line_2' => $data['address_line_2'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'google_map_location' => $data['google_map_location'] ?? null,
                'phone' => $data['phone'] ?? null,
                'alt_phone' => $data['alt_phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'logo_path' => $logoPath,
                'email_logo_path' => $emailLogoPath,
                'document_logo_path' => $documentLogoPath,
                'default_currency' => $data['default_currency'],
                'multi_currency_enabled' => $data['multi_currency_enabled'],
                'financial_year_start' => $data['financial_year_start'],
                'tax_enabled' => $data['tax_enabled'],
                'notify_email' => $data['notify_email'],
                'notify_whatsapp' => $data['notify_whatsapp'],
                'notify_sms' => $data['notify_sms'],
                'notify_on' => $data['notify_on'] ?? [],
                'status' => 'active',
                'subscription_status' => 'trial',
            ]);

            // Find the admin role that was auto-created via model booted()
            $roleName = $data['admin_role'] ?? 'Company Admin';
            $adminRole = Role::query()
                ->where('company_id', $company->id)
                ->where('guard_name', 'tenant')
                ->where('name', $roleName)
                ->first();

            $adminUser = CompanyUser::query()->create([
                'company_id' => $company->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'status' => 'active',
            ]);

            if ($adminRole) {
                $adminUser->roles()->sync([$adminRole->id]);
            }

            AuditLog::query()->create([
                'actor_guard' => 'platform',
                'actor_id' => $request->user('platform')?->id,
                'action' => 'company.created',
                'auditable_type' => Company::class,
                'auditable_id' => $company->id,
                'event_data' => [
                    'company_name' => $company->name,
                    'company_code' => $company->company_code,
                    'admin_email' => $data['admin_email'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Company created successfully.',
                'company' => $company->only([
                    'id', 'name', 'company_code', 'status', 'subscription_status',
                ]),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json($company);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'legal_name'             => ['nullable', 'string', 'max:255'],
            'registration_number'    => ['nullable', 'string', 'max:100'],
            'tin'                    => ['nullable', 'string', 'max:100'],
            'vat_number'             => ['nullable', 'string', 'max:100'],
            'industry'               => ['nullable', 'string', 'max:100'],
            'business_type'          => ['nullable', 'string', 'max:100'],
            'incorporation_date'     => ['nullable', 'date'],
            'country'                => ['nullable', 'string', 'max:100'],
            'region'                 => ['nullable', 'string', 'max:100'],
            'city'                   => ['nullable', 'string', 'max:100'],
            'address_line_1'         => ['nullable', 'string', 'max:255'],
            'address_line_2'         => ['nullable', 'string', 'max:255'],
            'postal_code'            => ['nullable', 'string', 'max:30'],
            'google_map_location'    => ['nullable', 'string', 'max:500'],
            'phone'                  => ['nullable', 'string', 'max:40'],
            'alt_phone'              => ['nullable', 'string', 'max:40'],
            'email'                  => ['nullable', 'email', 'max:255'],
            'website'                => ['nullable', 'url', 'max:255'],
            'whatsapp'               => ['nullable', 'string', 'max:40'],
            'logo'                   => ['nullable', 'file', 'image', 'max:2048'],
            'email_logo'             => ['nullable', 'file', 'image', 'max:2048'],
            'document_logo'          => ['nullable', 'file', 'image', 'max:2048'],
            'default_currency'       => ['required', 'string', 'max:10'],
            'multi_currency_enabled' => ['required', 'boolean'],
            'financial_year_start'   => ['required', 'integer', 'min:1', 'max:12'],
            'tax_enabled'            => ['required', 'boolean'],
            'notify_email'           => ['required', 'boolean'],
            'notify_whatsapp'        => ['required', 'boolean'],
            'notify_sms'             => ['required', 'boolean'],
            'notify_on'              => ['nullable', 'array'],
            'notify_on.*'            => ['string'],
            'status'                 => ['nullable', 'string', 'in:active,inactive,suspended'],
            'subscription_status'    => ['nullable', 'string', 'in:trial,active,past_due,cancelled'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
            unset($data['logo']);
        }
        if ($request->hasFile('email_logo')) {
            $data['email_logo_path'] = $request->file('email_logo')->store('companies/logos', 'public');
            unset($data['email_logo']);
        }
        if ($request->hasFile('document_logo')) {
            $data['document_logo_path'] = $request->file('document_logo')->store('companies/logos', 'public');
            unset($data['document_logo']);
        }

        $company->update($data);

        AuditLog::query()->create([
            'actor_guard'    => 'platform',
            'actor_id'       => $request->user('platform')?->id,
            'action'         => 'company.updated',
            'auditable_type' => Company::class,
            'auditable_id'   => $company->id,
            'event_data'     => ['company_name' => $company->name, 'company_code' => $company->company_code],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Company updated successfully.',
            'company' => $company->fresh(),
        ]);
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        AuditLog::query()->create([
            'actor_guard'    => 'platform',
            'actor_id'       => $request->user('platform')?->id,
            'action'         => 'company.deleted',
            'auditable_type' => Company::class,
            'auditable_id'   => $company->id,
            'event_data'     => ['company_name' => $company->name, 'company_code' => $company->company_code],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        $company->delete();

        return response()->json(['message' => 'Company deleted successfully.']);
    }

    private function generateCompanyCode(): string
    {
        $last = Company::query()
            ->whereNotNull('company_code')
            ->orderByDesc('created_at')
            ->value('company_code');

        $next = 1;

        if ($last && preg_match('/ZT-(\d+)$/', $last, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return 'ZT-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
