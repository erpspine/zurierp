<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Company extends Model
{
    use HasFactory, HasUuids;

    private const TENANT_ROLE_NAMES = [
        'Company Admin',
        'Finance Manager',
        'Sales Manager',
        'Operations Manager',
        'Fleet Manager',
        'Reservations Officer',
        'Accountant',
        'Driver / Guide',
        'Viewer',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'legal_name',
        'company_code',
        'registration_number',
        'tin',
        'vat_number',
        'industry',
        'business_type',
        'incorporation_date',
        'country',
        'region',
        'city',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'google_map_location',
        'phone',
        'alt_phone',
        'email',
        'website',
        'whatsapp',
        'logo_path',
        'email_logo_path',
        'document_logo_path',
        'default_currency',
        'multi_currency_enabled',
        'financial_year_start',
        'tax_enabled',
        'notify_email',
        'notify_whatsapp',
        'notify_sms',
        'notify_on',
        'status',
        'plan_id',
        'subscription_status',
    ];

    protected function casts(): array
    {
        return [
            'incorporation_date' => 'date',
            'multi_currency_enabled' => 'boolean',
            'tax_enabled' => 'boolean',
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_on' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Company $company): void {
            foreach (self::TENANT_ROLE_NAMES as $roleName) {
                Role::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'guard_name' => 'tenant',
                        'name' => $roleName,
                    ],
                    [
                        'type' => 'tenant',
                    ]
                );
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasValidSubscription(): bool
    {
        $today = Carbon::today();

        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->exists();
    }
}
