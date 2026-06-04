<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'subdomain' => fake()->unique()->slug(2),
            'plan' => fake()->randomElement(['free', 'starter', 'pro', 'enterprise']),
            'status' => 'active',
            'config' => [],
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended', 'suspended_at' => now()]);
    }

    public function onPlan(string $plan): static
    {
        return $this->state(['plan' => $plan]);
    }
}
