<?php declare(strict_types=1);

use App\Models\Invite;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('auth API', function () {
    it('registers a new tenant and returns token', function () {
        $response = $this->postJson('/api/v1/register', [
            'tenant_name' => fake()->company(),
            'subdomain'   => 'test-' . fake()->unique()->lexify('??????'),
            'name'        => fake()->name(),
            'email'       => fake()->safeEmail(),
            'password'    => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['tenant', 'user', 'token']]);
    });

    it('rejects duplicate subdomain on registration', function () {
        $existing = Tenant::factory()->create(['subdomain' => 'taken']);

        $response = $this->postJson('/api/v1/register', [
            'tenant_name' => fake()->company(),
            'subdomain'   => 'taken',
            'name'        => fake()->name(),
            'email'       => fake()->safeEmail(),
            'password'    => 'password123',
        ]);

        $response->assertStatus(422);
    });

    it('logs in with valid credentials', function () {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->for($tenant)->create([
            'email'    => 'user@test.com',
            'password' => Hash::make('secret123'),
        ]);

        // Simulate tenant resolution via header
        $response = $this->withHeaders(['X-Tenant-ID' => $tenant->subdomain])
            ->postJson('/api/v1/login', ['email' => 'user@test.com', 'password' => 'secret123']);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['token']]);
    });

    it('rejects wrong password on login', function () {
        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create([
            'email'    => 'user2@test.com',
            'password' => Hash::make('correctpass'),
        ]);

        $response = $this->withHeaders(['X-Tenant-ID' => $tenant->subdomain])
            ->postJson('/api/v1/login', ['email' => 'user2@test.com', 'password' => 'wrongpass']);

        $response->assertStatus(422);
    });

    it('accepts a valid invite and creates user', function () {
        $invite = Invite::factory()->create(['role' => 'member']);

        $response = $this->postJson('/api/v1/invites/accept', [
            'token'    => $invite->token,
            'name'     => fake()->name(),
            'password' => 'newpassword1',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['data' => ['id', 'email', 'role']]);
    });

    it('rejects expired invite', function () {
        $invite = Invite::factory()->expired()->create();

        $response = $this->postJson('/api/v1/invites/accept', [
            'token'    => $invite->token,
            'name'     => fake()->name(),
            'password' => 'newpassword1',
        ]);

        $response->assertStatus(422);
    });
});
