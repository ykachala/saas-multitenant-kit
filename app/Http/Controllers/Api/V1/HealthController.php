<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $dbOk = true;
        $cacheOk = true;

        try {
            DB::select('SELECT 1');
        } catch (\Throwable) {
            $dbOk = false;
        }

        try {
            Cache::put('health_check', true, 5);
            $cacheOk = (bool) Cache::get('health_check');
        } catch (\Throwable) {
            $cacheOk = false;
        }

        $status = $dbOk && $cacheOk ? 200 : 503;

        return response()->json([
            'status'    => $status === 200 ? 'ok' : 'degraded',
            'checks'    => ['database' => $dbOk, 'cache' => $cacheOk],
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
