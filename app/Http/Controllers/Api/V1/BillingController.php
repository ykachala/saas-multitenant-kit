<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends ApiController
{
    public function __construct(private readonly BillingService $billingService) {}

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_code' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $tenant = $request->user()->tenant;
        $result = $this->billingService->subscribe($tenant, $data['plan_code'], $data['email']);

        return $this->success($result, 201);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->billingService->cancel($request->user()->tenant);

        return $this->success(['cancelled' => true]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = $this->billingService->invoices($request->user()->tenant);

        return $this->success($invoices);
    }

    public function webhook(Request $request): JsonResponse
    {
        $this->billingService->handleWebhook($request->all());

        return $this->success(['received' => true]);
    }
}
