<?php declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('EnforceFeatureGate middleware', function () {
    it('allows access when tenant has the feature', function () {
        $tenant = Tenant::factory()->onPlan('pro')->create();
        $user   = User::factory()->for($tenant)->create(['role' => 'owner']);

        $token = $user->createToken('test')->plainTextToken;

        // Add a test route protected by the feature gate in the test
        \Illuminate\Support\Facades\Route::middleware(['tenant', 'auth:sanctum', 'feature:api_access'])
            ->get('/test-feature-gate', fn () => response()->json(['ok' => true]));

        $response = $this->withHeaders([
            'X-Tenant-ID'   => $tenant->subdomain,
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/test-feature-gate');

        $response->assertStatus(200);
    });

    it('blocks access when tenant plan does not include the feature', function () {
        $tenant = Tenant::factory()->onPlan('free')->create();
        $user   = User::factory()->for($tenant)->create(['role' => 'owner']);

        $token = $user->createToken('test')->plainTextToken;

        \Illuminate\Support\Facades\Route::middleware(['tenant', 'auth:sanctum', 'feature:api_access'])
            ->get('/test-feature-gate-blocked', fn () => response()->json(['ok' => true]));

        $response = $this->withHeaders([
            'X-Tenant-ID'   => $tenant->subdomain,
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/test-feature-gate-blocked');

        $response->assertStatus(403)->assertJsonStructure(['error', 'feature', 'plan']);
    });

    it('suspended tenant is rejected by ResolveTenant', function () {
        $tenant = Tenant::factory()->suspended()->create();

        // The health endpoint is intentionally tenant-agnostic (load-balancer probe),
        // so exercise ResolveTenant against a route that actually applies the middleware.
        \Illuminate\Support\Facades\Route::middleware(['tenant'])
            ->get('/test-suspended-tenant', fn () => response()->json(['ok' => true]));

        $response = $this->withHeaders(['X-Tenant-ID' => $tenant->subdomain])
            ->getJson('/test-suspended-tenant');

        $response->assertStatus(403);
    });
});
