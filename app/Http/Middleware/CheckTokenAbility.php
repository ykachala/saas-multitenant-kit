<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (! $request->user()?->tokenCan($ability)) {
            return response()->json(['error' => 'Insufficient token permissions.'], 403);
        }

        return $next($request);
    }
}
