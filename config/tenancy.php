<?php

use App\Services\Billing\PaystackDriver;
use App\Services\Billing\StripeDriver;

return [
    'mode' => env('TENANCY_MODE', 'shared'),

    'resolution' => [
        'subdomain' => true,
        'header' => 'X-Tenant-ID',
        'custom_domain' => true,
    ],

    'cache' => [
        'ttl' => 300, // 5 minutes
        'prefix' => 'tenant:',
    ],

    'billing' => [
        'driver' => env('BILLING_DRIVER', 'paystack'),
        'drivers' => [
            'paystack' => PaystackDriver::class,
            'stripe' => StripeDriver::class,
        ],
    ],

    'plans' => [
        'free' => ['seats' => 3,   'projects' => 5,   'storage_gb' => 1],
        'starter' => ['seats' => 10,  'projects' => 20,  'storage_gb' => 10],
        'pro' => ['seats' => 50,  'projects' => 100, 'storage_gb' => 100],
        'enterprise' => ['seats' => -1,  'projects' => -1,  'storage_gb' => -1],
    ],
];
