<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Invite;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function registerTenant(string $tenantName, string $subdomain, string $ownerName, string $email, string $password): array
    {
        $tenant = $this->tenantService->create($tenantName, $subdomain);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $ownerName,
            'email'     => $email,
            'password'  => Hash::make($password),
            'role'      => 'owner',
        ]);

        $token = $user->createToken('default')->plainTextToken;

        return compact('tenant', 'user', 'token');
    }

    public function login(Tenant $tenant, string $email, string $password): string
    {
        $user = User::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        return $user->createToken('default')->plainTextToken;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function createInvite(Tenant $tenant, string $email, string $role = 'member'): Invite
    {
        return Invite::create([
            'tenant_id'  => $tenant->id,
            'email'      => $email,
            'role'       => $role,
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function acceptInvite(string $token, string $name, string $password): User
    {
        $invite = Invite::where('token', $token)->whereNull('accepted_at')->first();

        if ($invite === null || ! $invite->isPending()) {
            throw ValidationException::withMessages(['token' => 'Invalid or expired invitation.']);
        }

        $user = User::create([
            'tenant_id' => $invite->tenant_id,
            'name'      => $name,
            'email'     => $invite->email,
            'password'  => Hash::make($password),
            'role'      => $invite->role,
        ]);

        $invite->update(['accepted_at' => now()]);

        return $user;
    }
}
