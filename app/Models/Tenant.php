<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

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
            'config'        => 'array',
            'trial_ends_at' => 'datetime',
            'suspended_at'  => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function can(string $feature): bool
    {
        $features = config('features.' . $this->plan, []);
        return (bool) ($features[$feature] ?? false);
    }

    public function withinLimit(string $resource, int $current): bool
    {
        $limit = config('tenancy.plans.' . $this->plan . '.' . $resource, 0);
        return $limit === -1 || $current < $limit;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
