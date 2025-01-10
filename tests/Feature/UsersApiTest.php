<?php declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('users API', function () {
    it('returns 401 without auth token', function () {
        $tenant = Tenant::factory()->create();

        $this->withHeaders(['X-Tenant-ID' => $tenant->subdomain])
            ->getJson('/api/v1/users')
            ->assertStatus(401);
    });

    it('lists users for the authenticated tenant', function () {
        $tenant = Tenant::factory()->create();
        $owner  = User::factory()->for($tenant)->create(['role' => 'owner']);
        User::factory()->count(3)->for($tenant)->create();

        // Users from another tenant — must not appear
        $other = Tenant::factory()->create();
        User::factory()->count(2)->for($other)->create();

        $token = $owner->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'X-Tenant-ID'   => $tenant->subdomain,
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 4); // owner + 3 members
    });

    it('owner can update another user role', function () {
        $tenant = Tenant::factory()->create();
        $owner  = User::factory()->for($tenant)->create(['role' => 'owner']);
        $member = User::factory()->for($tenant)->create(['role' => 'member']);

        $token = $owner->createToken('test')->plainTextToken;

        $this->withHeaders([
            'X-Tenant-ID'   => $tenant->subdomain,
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson("/api/v1/users/{$member->id}/role", ['role' => 'admin'])
            ->assertStatus(200)
            ->assertJsonPath('data.role', 'admin');
    });

    it('member cannot delete another user', function () {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->for($tenant)->create(['role' => 'member']);
        $target = User::factory()->for($tenant)->create(['role' => 'member']);

        $token = $member->createToken('test')->plainTextToken;

        // member calling delete will hit UserService which throws ValidationException
        $this->withHeaders([
            'X-Tenant-ID'   => $tenant->subdomain,
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/v1/users/{$target->id}")
            ->assertStatus(422);
    });
});
