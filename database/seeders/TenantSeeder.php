<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenantA = Tenant::firstOrCreate(['subdomain' => 'acme'], [
            'name' => 'Acme Corp',
            'plan' => 'pro',
            'status' => 'active',
            'config' => ['timezone' => 'Africa/Harare', 'locale' => 'en'],
        ]);

        User::firstOrCreate(['email' => 'owner@acme.test'], [
            'tenant_id' => $tenantA->id,
            'name' => 'Acme Owner',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        User::firstOrCreate(['email' => 'admin@acme.test'], [
            'tenant_id' => $tenantA->id,
            'name' => 'Acme Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $tenantB = Tenant::firstOrCreate(['subdomain' => 'globex'], [
            'name' => 'Globex Inc',
            'plan' => 'starter',
            'status' => 'active',
            'config' => ['timezone' => 'Africa/Johannesburg', 'locale' => 'en'],
        ]);

        User::firstOrCreate(['email' => 'owner@globex.test'], [
            'tenant_id' => $tenantB->id,
            'name' => 'Globex Owner',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);
    }
}
