<?php declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invite;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Invite> */
class InviteFactory extends Factory
{
    protected $model = Invite::class;

    public function definition(): array
    {
        return [
            'tenant_id'  => Tenant::factory(),
            'email'      => fake()->safeEmail(),
            'role'       => fake()->randomElement(['admin', 'member']),
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(['accepted_at' => now()]);
    }
}
