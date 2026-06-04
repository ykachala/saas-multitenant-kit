<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Tenant;

interface BillingDriverInterface
{
    public function createSubscription(Tenant $tenant, string $planCode, string $email): array;

    public function cancelSubscription(Tenant $tenant): void;

    public function getInvoices(Tenant $tenant): array;

    public function handleWebhook(array $payload): void;
}
