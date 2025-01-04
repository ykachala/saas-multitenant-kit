<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantService
{
    public function create(string $name, string $subdomain, string $plan = 'free'): Tenant
    {
        return Tenant::create([
            'name'      => $name,
            'subdomain' => $subdomain,
            'plan'      => $plan,
            'status'    => 'active',
        ]);
    }

    public function suspend(Tenant $tenant): void
    {
        $tenant->update(['status' => 'suspended', 'suspended_at' => now()]);
        $this->invalidateCache($tenant);
    }

    public function activate(Tenant $tenant): void
    {
        $tenant->update(['status' => 'active', 'suspended_at' => null]);
        $this->invalidateCache($tenant);
    }

    public function updateConfig(Tenant $tenant, array $config): void
    {
        $tenant->update(['config' => array_merge($tenant->config ?? [], $config)]);
        $this->invalidateCache($tenant);
    }

    public function invalidateCache(Tenant $tenant): void
    {
        $prefix = config('tenancy.cache.prefix', 'tenant:');
        Cache::forget($prefix . $tenant->subdomain);
        if ($tenant->custom_domain) {
            Cache::forget($prefix . $tenant->custom_domain);
        }
        Cache::forget($prefix . $tenant->id);
    }
}
