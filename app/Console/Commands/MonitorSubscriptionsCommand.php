<?php

namespace App\Console\Commands;

use App\Mail\CompanySubscriptionStatusMail;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MonitorSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:monitor';

    protected $description = 'Send expiring and expired subscription notifications and update expired subscriptions';

    public function handle(): int
    {
        $today = Carbon::today();
        $reminderCutoff = $today->copy()->addDays(3);

        $expiringSubscriptions = Subscription::query()
            ->with(['company', 'plan'])
            ->whereIn('status', ['active', 'trial'])
            ->whereDate('ends_at', '>=', $today)
            ->whereDate('ends_at', '<=', $reminderCutoff)
            ->get();

        foreach ($expiringSubscriptions as $subscription) {
            if ($this->notificationLogged($subscription, 'subscription.expiring_notice_sent')) {
                continue;
            }

            $daysRemaining = $today->diffInDays($subscription->ends_at, false);
            $this->sendLifecycleEmail($subscription, 'expiring', max(0, $daysRemaining));
            $this->logNotification($subscription, 'subscription.expiring_notice_sent', [
                'days_remaining' => max(0, $daysRemaining),
            ]);
        }

        $expiredSubscriptions = Subscription::query()
            ->with(['company', 'plan'])
            ->whereIn('status', ['active', 'trial'])
            ->whereDate('ends_at', '<', $today)
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            $company = $subscription->company;
            if ($company && ! $company->hasValidSubscription()) {
                $company->update(['subscription_status' => 'expired']);
            }

            if (! $this->notificationLogged($subscription, 'subscription.expired_notice_sent')) {
                $this->sendLifecycleEmail($subscription->fresh(['company', 'plan']), 'expired');
                $this->logNotification($subscription, 'subscription.expired_notice_sent');
            }
        }

        $this->info('Subscription lifecycle monitoring completed.');

        return self::SUCCESS;
    }

    private function sendLifecycleEmail(Subscription $subscription, string $statusType, ?int $daysRemaining = null): void
    {
        $company = $subscription->company;

        if (! $company) {
            return;
        }

        $recipients = collect([
            $company->email,
            ...$company->users()->pluck('email')->all(),
        ])
            ->filter()
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values();

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new CompanySubscriptionStatusMail(
                    subscription: $subscription,
                    statusType: $statusType,
                    loginUrl: rtrim((string) config('app.url'), '/'),
                    daysRemaining: $daysRemaining,
                ));
            } catch (\Throwable $mailException) {
                report($mailException);
            }
        }
    }

    private function notificationLogged(Subscription $subscription, string $action): bool
    {
        return AuditLog::query()
            ->where('action', $action)
            ->where('auditable_type', Subscription::class)
            ->where('auditable_id', $subscription->id)
            ->exists();
    }

    private function logNotification(Subscription $subscription, string $action, array $eventData = []): void
    {
        AuditLog::query()->create([
            'actor_guard' => 'system',
            'actor_id' => null,
            'company_id' => $subscription->company_id,
            'action' => $action,
            'auditable_type' => Subscription::class,
            'auditable_id' => $subscription->id,
            'event_data' => array_merge([
                'license_key' => $subscription->license_key,
            ], $eventData),
            'ip_address' => null,
            'user_agent' => 'artisan subscriptions:monitor',
        ]);
    }
}
