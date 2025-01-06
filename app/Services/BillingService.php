<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Services\Billing\BillingDriverInterface;

class BillingService
{
    private BillingDriverInterface $driver;

    public function __construct()
    {
        $driverClass = config('tenancy.billing.drivers.' . config('tenancy.billing.driver'));

        if (! class_exists($driverClass)) {
            throw new \RuntimeException("Billing driver [{$driverClass}] not found.");
        }

        $this->driver = app($driverClass);
    }

    public function subscribe(Tenant $tenant, string $planCode, string $email): array
    {
        return $this->driver->createSubscription($tenant, $planCode, $email);
    }

    public function cancel(Tenant $tenant): void
    {
        $this->driver->cancelSubscription($tenant);
        $tenant->update(['plan' => 'free']);
    }

    public function invoices(Tenant $tenant): array
    {
        return $this->driver->getInvoices($tenant);
    }

    public function handleWebhook(array $payload): void
    {
        $this->driver->handleWebhook($payload);
    }
}
