<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolve($request);

        if ($tenant === null) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        if (! $tenant->isActive()) {
            return response()->json(['error' => 'Tenant is suspended'], 403);
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }

    private function resolve(Request $request): ?Tenant
    {
        $identifier = $this->extractIdentifier($request);

        if ($identifier === null) {
            return null;
        }

        $ttl = (int) config('tenancy.cache.ttl', 300);
        $prefix = config('tenancy.cache.prefix', 'tenant:');

        return Cache::remember(
            $prefix.$identifier,
            $ttl,
            fn () => $this->findTenant($identifier, $request),
        );
    }

    private function extractIdentifier(Request $request): ?string
    {
        if ($header = $request->header('X-Tenant-ID')) {
            return $header;
        }

        if ($subdomain = $request->header('X-Tenant-Subdomain')) {
            return $subdomain;
        }

        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            return $parts[0];
        }

        return null;
    }

    private function findTenant(string $identifier, Request $request): ?Tenant
    {
        return Tenant::where('subdomain', $identifier)->first()
            ?? Tenant::where('custom_domain', $request->getHost())->first()
            ?? (strlen($identifier) === 36 ? Tenant::find($identifier) : null);
    }
}
