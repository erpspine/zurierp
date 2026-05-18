<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Subscription::query()
            ->with(['company:id,name,company_code', 'plan:id,name,slug'])
            ->orderByDesc('created_at');

        if ($request->filled('company_id')) {
            $q->where('company_id', $request->company_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $q->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $q->where(function ($query) use ($term): void {
                $query->where('license_key', 'like', $term)
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $term)
                        ->orWhere('company_code', 'like', $term));
            });
        }

        return response()->json($q->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id'        => ['required', 'uuid', 'exists:companies,id'],
            'plan_id'           => ['nullable', 'integer', 'exists:plans,id'],
            'billing_cycle'     => ['required', 'in:monthly,quarterly,semi_annual,annual'],
            'duration_months'   => ['nullable', 'integer', 'min:1', 'max:60'],
            'starts_at'         => ['required', 'date'],
            'amount_paid'       => ['required', 'numeric', 'min:0'],
            'currency'          => ['required', 'string', 'max:10'],
            'payment_method'    => ['required', 'in:cash,bank_transfer,mobile_money,card,cheque,other'],
            'payment_reference' => ['nullable', 'string', 'max:200'],
            'payment_notes'     => ['nullable', 'string', 'max:1000'],
            'payment_date'      => ['nullable', 'date'],
        ]);

        $startsAt = Carbon::parse($data['starts_at']);
        $durationMonths = $this->resolveDurationMonths(
            $data['billing_cycle'],
            isset($data['duration_months']) ? (int) $data['duration_months'] : null
        );
        $endsAt   = $this->calculateEndDate($startsAt, $durationMonths);

        $company = Company::findOrFail($data['company_id']);
        $licenseKey = $this->generateLicenseKey($company);
        $invoiceNumber = $this->generateInvoiceNumber();

        $subscription = Subscription::create([
            ...$data,
            'license_key'     => $licenseKey,
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'status'          => 'active',
            'activated_at'    => now(),
            'invoice_number'  => $invoiceNumber,
            'invoice_generated_at' => now(),
            'created_by'      => $request->user()->id,
            'created_by_name' => $request->user()->name,
        ]);

        // Update company subscription_status
        $company->update(['subscription_status' => 'active', 'plan_id' => $data['plan_id'] ?? $company->plan_id]);

        AuditLog::create([
            'actor_guard'    => 'platform',
            'actor_id'       => $request->user()->id,
            'action'         => 'subscription.created',
            'auditable_type' => Subscription::class,
            'auditable_id'   => $subscription->id,
            'event_data'     => [
                'company'      => $company->name,
                'license_key'  => $licenseKey,
                'invoice_number' => $invoiceNumber,
                'billing_cycle' => $data['billing_cycle'],
                'duration_months' => $durationMonths,
                'amount_paid'  => $data['amount_paid'],
                'ends_at'      => $endsAt->toDateString(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message'      => 'Subscription activated successfully.',
            'subscription' => $subscription->load(['company:id,name,company_code', 'plan:id,name,slug']),
            'invoice_download_url' => url("/api/admin/subscriptions/{$subscription->id}/invoice"),
        ], 201);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return response()->json(
            $subscription->load(['company:id,name,company_code,email,phone', 'plan:id,name,slug,monthly_price'])
        );
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->status === 'cancelled') {
            return response()->json(['message' => 'Already cancelled.'], 422);
        }

        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // If this was the company's active sub, mark company as cancelled
        $company = $subscription->company;
        $hasOtherActive = Subscription::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->where('id', '!=', $subscription->id)
            ->exists();

        if (! $hasOtherActive) {
            $company->update(['subscription_status' => 'cancelled']);
        }

        AuditLog::create([
            'actor_guard'    => 'platform',
            'actor_id'       => $request->user()->id,
            'action'         => 'subscription.cancelled',
            'auditable_type' => Subscription::class,
            'auditable_id'   => $subscription->id,
            'event_data'     => ['license_key' => $subscription->license_key, 'company' => $company->name],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return response()->json([
            'message'      => 'Subscription cancelled.',
            'subscription' => $subscription->fresh()->load(['company:id,name,company_code', 'plan:id,name,slug']),
        ]);
    }

    public function downloadInvoice(Request $request, Subscription $subscription)
    {
        $subscription->load(['company', 'plan']);

        if (! $subscription->invoice_number) {
            $subscription->update([
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_generated_at' => now(),
            ]);
            $subscription->refresh();
        }

        $pdf = Pdf::loadView('invoices.subscription', [
            'subscription' => $subscription,
            'company' => $subscription->company,
            'plan' => $subscription->plan,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'invoice-' . Str::slug($subscription->invoice_number) . '.pdf';

        return $pdf->download($filename);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function calculateEndDate(Carbon $start, int $durationMonths): Carbon
    {
        return $start->copy()->addMonths($durationMonths)->subDay();
    }

    private function resolveDurationMonths(string $cycle, ?int $durationMonths): int
    {
        return match ($cycle) {
            'monthly' => max(1, $durationMonths ?? 1),
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1,
        };
    }

    private function generateLicenseKey(Company $company): string
    {
        $code    = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $company->company_code ?? $company->id));
        $code    = substr($code, 0, 8);
        $yearMon = now()->format('Ym');
        $random  = strtoupper(Str::random(8));

        $key = "ZL-{$code}-{$yearMon}-{$random}";

        // Ensure uniqueness
        while (Subscription::query()->where('license_key', $key)->exists()) {
            $random = strtoupper(Str::random(8));
            $key    = "ZL-{$code}-{$yearMon}-{$random}";
        }

        return $key;
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd');

        do {
            $candidate = $prefix . '-' . strtoupper(Str::random(6));
        } while (Subscription::query()->where('invoice_number', $candidate)->exists());

        return $candidate;
    }
}
