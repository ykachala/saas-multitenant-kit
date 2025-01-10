<?php declare(strict_types=1);

use App\Models\Invite;
use App\Models\Tenant;
use App\Services\AuthService;
use App\Services\TenantService;
use Illuminate\Validation\ValidationException;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('AuthService', function () {
    beforeEach(function () {
        $this->service = new AuthService(new TenantService());
    });

    it('registerTenant creates tenant, owner user, and returns token', function () {
        $result = $this->service->registerTenant(
            fake()->company(),
            'sub-' . fake()->unique()->lexify('??????'),
            fake()->name(),
            fake()->safeEmail(),
            'password123',
        );

        expect($result)->toHaveKeys(['tenant', 'user', 'token'])
            ->and($result['user']->role)->toBe('owner');
    });

    it('login throws on wrong password', function () {
        $tenant = Tenant::factory()->create();
        \App\Models\User::factory()->for($tenant)->create([
            'email'    => 'x@x.com',
            'password' => \Illuminate\Support\Facades\Hash::make('correct'),
        ]);

        expect(fn () => $this->service->login($tenant, 'x@x.com', 'wrong'))
            ->toThrow(ValidationException::class);
    });

    it('createInvite generates a 64-char token', function () {
        $tenant = Tenant::factory()->create();
        $invite = $this->service->createInvite($tenant, fake()->safeEmail());

        expect($invite->token)->toHaveLength(64)
            ->and($invite->isPending())->toBeTrue();
    });

    it('acceptInvite rejects expired token', function () {
        $invite = Invite::factory()->expired()->create();

        expect(fn () => $this->service->acceptInvite($invite->token, fake()->name(), 'pass1234'))
            ->toThrow(ValidationException::class);
    });
});
