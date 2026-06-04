<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('TenantService', function () {
    it('creates a tenant with the given name and subdomain', function () {
        $service = new TenantService;
        $tenant = $service->create(fake()->company(), 'test-'.fake()->lexify('??????'));

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->status)->toBe('active')
            ->and($tenant->plan)->toBe('free');
    });

    it('suspend sets status to suspended and caches are cleared', function () {
        Cache::spy();
        $service = new TenantService;
        $tenant = Tenant::factory()->create();

        $service->suspend($tenant);
        $tenant->refresh();

        expect($tenant->status)->toBe('suspended')
            ->and($tenant->suspended_at)->not->toBeNull();

        Cache::shouldHaveReceived('forget')->atLeast()->once();
    });

    it('activate restores suspended tenant', function () {
        $service = new TenantService;
        $tenant = Tenant::factory()->suspended()->create();

        $service->activate($tenant);
        $tenant->refresh();

        expect($tenant->status)->toBe('active')
            ->and($tenant->suspended_at)->toBeNull();
    });

    it('updateConfig deep-merges config', function () {
        $service = new TenantService;
        $tenant = Tenant::factory()->create(['config' => ['locale' => 'en']]);

        $service->updateConfig($tenant, ['timezone' => 'Africa/Harare']);
        $tenant->refresh();

        expect($tenant->config)->toMatchArray(['locale' => 'en', 'timezone' => 'Africa/Harare']);
    });
});
