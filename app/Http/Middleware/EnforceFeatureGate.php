<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFeatureGate
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = tenant();

        if ($tenant === null || ! $tenant->can($feature)) {
            return response()->json([
                'error'   => 'This feature is not available on your current plan.',
                'feature' => $feature,
                'plan'    => $tenant?->plan,
            ], 403);
        }

        return $next($request);
    }
}
