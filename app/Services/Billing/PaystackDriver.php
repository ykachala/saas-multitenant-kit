<?php declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class PaystackDriver implements BillingDriverInterface
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl   = (string) config('services.paystack.payment_url', 'https://api.paystack.co');
    }

    public function createSubscription(Tenant $tenant, string $planCode, string $email): array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription", [
                'customer'  => $email,
                'plan'      => $planCode,
                'metadata'  => ['tenant_id' => $tenant->id],
            ])
            ->throw()
            ->json();

        return $response['data'] ?? [];
    }

    public function cancelSubscription(Tenant $tenant): void
    {
        $subscriptionCode = $tenant->config['paystack_subscription_code'] ?? null;

        if ($subscriptionCode === null) {
            return;
        }

        Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription/disable", [
                'code'  => $subscriptionCode,
                'token' => $tenant->config['paystack_email_token'] ?? '',
            ])
            ->throw();
    }

    public function getInvoices(Tenant $tenant): array
    {
        $customer = $tenant->config['paystack_customer_code'] ?? null;

        if ($customer === null) {
            return [];
        }

        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction", ['customer' => $customer])
            ->throw()
            ->json();

        return $response['data'] ?? [];
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;

        match ($event) {
            'subscription.create' => $this->onSubscriptionCreated($payload['data']),
            'invoice.payment_failed' => $this->onPaymentFailed($payload['data']),
            default => null,
        };
    }

    private function onSubscriptionCreated(array $data): void
    {
        $tenantId = $data['metadata']['tenant_id'] ?? null;

        if ($tenantId === null) {
            return;
        }

        $tenant = Tenant::find($tenantId);
        $tenant?->update([
            'config' => array_merge($tenant->config ?? [], [
                'paystack_subscription_code' => $data['subscription_code'] ?? null,
                'paystack_customer_code'     => $data['customer']['customer_code'] ?? null,
                'paystack_email_token'       => $data['email_token'] ?? null,
            ]),
        ]);
    }

    private function onPaymentFailed(array $data): void
    {
        $tenantId = $data['metadata']['tenant_id'] ?? null;

        if ($tenantId === null) {
            return;
        }

        Tenant::where('id', $tenantId)->update(['status' => 'suspended']);
    }
}
