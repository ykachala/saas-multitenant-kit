<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Tenant;
use Stripe\StripeClient;

class StripeDriver implements BillingDriverInterface
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient((string) config('services.stripe.secret'));
    }

    public function createSubscription(Tenant $tenant, string $priceId, string $email): array
    {
        $customer = $this->stripe->customers->create([
            'email' => $email,
            'metadata' => ['tenant_id' => $tenant->id],
        ]);

        $subscription = $this->stripe->subscriptions->create([
            'customer' => $customer->id,
            'items' => [['price' => $priceId]],
            'metadata' => ['tenant_id' => $tenant->id],
        ]);

        $tenant->update([
            'config' => array_merge($tenant->config ?? [], [
                'stripe_customer_id' => $customer->id,
                'stripe_subscription_id' => $subscription->id,
            ]),
        ]);

        return $subscription->toArray();
    }

    public function cancelSubscription(Tenant $tenant): void
    {
        $subscriptionId = $tenant->config['stripe_subscription_id'] ?? null;

        if ($subscriptionId === null) {
            return;
        }

        $this->stripe->subscriptions->cancel($subscriptionId);
    }

    public function getInvoices(Tenant $tenant): array
    {
        $customerId = $tenant->config['stripe_customer_id'] ?? null;

        if ($customerId === null) {
            return [];
        }

        $invoices = $this->stripe->invoices->all(['customer' => $customerId, 'limit' => 24]);

        return $invoices->data;
    }

    public function handleWebhook(array $payload): void
    {
        $type = $payload['type'] ?? null;

        match ($type) {
            'invoice.payment_failed' => $this->onPaymentFailed($payload['data']['object']),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($payload['data']['object']),
            default => null,
        };
    }

    private function onPaymentFailed(array $invoice): void
    {
        $tenantId = $invoice['metadata']['tenant_id'] ?? null;
        if ($tenantId) {
            Tenant::where('id', $tenantId)->update(['status' => 'suspended']);
        }
    }

    private function onSubscriptionDeleted(array $subscription): void
    {
        $tenantId = $subscription['metadata']['tenant_id'] ?? null;
        if ($tenantId) {
            Tenant::where('id', $tenantId)->update(['plan' => 'free']);
        }
    }
}
