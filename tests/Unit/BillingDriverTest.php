<?php declare(strict_types=1);

use App\Models\Tenant;
use App\Services\Billing\PaystackDriver;
use App\Services\Billing\StripeDriver;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('PaystackDriver', function () {
    it('createSubscription posts to Paystack API and returns data', function () {
        Http::fake([
            'api.paystack.co/subscription' => Http::response([
                'status' => true,
                'data'   => ['subscription_code' => 'SUB_' . fake()->uuid()],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $driver = new PaystackDriver();

        $result = $driver->createSubscription($tenant, 'PLN_test', fake()->safeEmail());

        expect($result)->toHaveKey('subscription_code');
        Http::assertSentCount(1);
    });

    it('getInvoices returns empty array when no customer code', function () {
        $tenant = Tenant::factory()->create(['config' => []]);
        $driver = new PaystackDriver();

        expect($driver->getInvoices($tenant))->toBe([]);
    });

    it('handleWebhook subscription.create stores subscription code on tenant', function () {
        $tenant = Tenant::factory()->create();
        $driver = new PaystackDriver();

        $driver->handleWebhook([
            'event' => 'subscription.create',
            'data'  => [
                'subscription_code' => 'SUB_abc123',
                'email_token'       => 'email_tok_xyz',
                'metadata'          => ['tenant_id' => $tenant->id],
                'customer'          => ['customer_code' => 'CUS_abc'],
            ],
        ]);

        $tenant->refresh();
        expect($tenant->config['paystack_subscription_code'])->toBe('SUB_abc123');
    });
});

describe('StripeDriver', function () {
    it('getInvoices returns empty array when no stripe_customer_id', function () {
        $tenant = Tenant::factory()->create(['config' => []]);

        // Mock Stripe client to avoid real API calls
        $driver = Mockery::mock(StripeDriver::class)->makePartial();

        expect($driver->getInvoices($tenant))->toBe([]);
    });
});
