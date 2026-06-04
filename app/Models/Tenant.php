<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $subdomain
 * @property string|null $custom_domain
 * @property string $status
 * @property string $plan
 * @property array<string, mixed> $config
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $suspended_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'subdomain',
        'custom_domain',
        'status',
        'plan',
        'config',
        'trial_ends_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function can(string $feature): bool
    {
        $features = config('features.'.$this->plan, []);

        return (bool) ($features[$feature] ?? false);
    }

    public function withinLimit(string $resource, int $current): bool
    {
        $limit = config('tenancy.plans.'.$this->plan.'.'.$resource, 0);

        return $limit === -1 || $current < $limit;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
