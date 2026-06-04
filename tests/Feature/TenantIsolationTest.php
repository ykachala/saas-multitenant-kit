<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('tenant isolation', function () {
    it('global scope prevents cross-tenant user access', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->for($tenantA)->create();
        User::factory()->for($tenantB)->create();

        // Bind tenant A into the container
        app()->instance('tenant', $tenantA);

        // With scope active, only tenant A's user should appear
        expect(User::all())->toHaveCount(1)
            ->and(User::first()->id)->toBe($userA->id);
    });

    it('withoutGlobalScope bypasses tenant filter', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        User::factory()->for($tenantA)->create();
        User::factory()->for($tenantB)->create();

        app()->instance('tenant', $tenantA);

        expect(User::withoutGlobalScope(TenantScope::class)->count())->toBe(2);
    });

    it('Tenant can() returns correct feature flags per plan', function () {
        $freeTenant = Tenant::factory()->onPlan('free')->create();
        $proTenant = Tenant::factory()->onPlan('pro')->create();

        expect($freeTenant->can('api_access'))->toBeFalse()
            ->and($proTenant->can('api_access'))->toBeTrue()
            ->and($proTenant->can('sso'))->toBeFalse();
    });

    it('Tenant withinLimit respects plan seat limits', function () {
        $freeTenant = Tenant::factory()->onPlan('free')->create();
        // free plan: seats = 3
        expect($freeTenant->withinLimit('seats', 2))->toBeTrue()
            ->and($freeTenant->withinLimit('seats', 3))->toBeFalse();
    });

    it('enterprise plan has unlimited seats', function () {
        $tenant = Tenant::factory()->onPlan('enterprise')->create();
        expect($tenant->withinLimit('seats', 99999))->toBeTrue();
    });
});
