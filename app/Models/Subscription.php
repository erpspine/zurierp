<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'plan_id',
        'license_key',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'status',
        'activated_at',
        'cancelled_at',
        'amount_paid',
        'currency',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'payment_date',
        'invoice_number',
        'invoice_generated_at',
        'created_by',
        'created_by_name',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'    => 'date',
            'ends_at'      => 'date',
            'payment_date' => 'date',
            'invoice_generated_at' => 'datetime',
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'amount_paid'  => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Checks if subscription is currently valid */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }
}
