<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends ApiController
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success($request->user()->tenant);
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'config' => ['required', 'array'],
            'config.timezone' => ['sometimes', 'string', 'timezone'],
            'config.locale' => ['sometimes', 'string', 'max:10'],
        ]);

        $tenant = $request->user()->tenant;
        $this->tenantService->updateConfig($tenant, $validated['config']);

        return $this->success($tenant->fresh());
    }

    public function suspend(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $this->tenantService->suspend($tenant);

        return $this->success(['suspended' => true]);
    }
}
