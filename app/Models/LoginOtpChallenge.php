<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginOtpChallenge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'guard',
        'user_id',
        'company_id',
        'device_hash',
        'device_name',
        'otp_code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}