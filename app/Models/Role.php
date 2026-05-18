<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'guard_name',
        'name',
        'type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function platformUsers(): BelongsToMany
    {
        return $this->belongsToMany(PlatformUser::class, 'platform_user_roles');
    }

    public function companyUsers(): BelongsToMany
    {
        return $this->belongsToMany(CompanyUser::class, 'company_user_roles');
    }

    public function scopePlatform(Builder $query): Builder
    {
        return $query
            ->where('type', 'platform')
            ->where('guard_name', 'platform')
            ->whereNull('company_id');
    }

    public function scopeTenant(Builder $query, ?string $companyId = null): Builder
    {
        $companyId ??= auth('tenant')->user()?->company_id;

        return $query
            ->where('type', 'tenant')
            ->where('guard_name', 'tenant')
            ->when($companyId, fn (Builder $builder) => $builder->where('company_id', $companyId));
    }
}
