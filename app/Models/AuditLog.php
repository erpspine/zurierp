<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_guard',
        'actor_id',
        'company_id',
        'action',
        'auditable_type',
        'auditable_id',
        'event_data',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'array',
        ];
    }
}
