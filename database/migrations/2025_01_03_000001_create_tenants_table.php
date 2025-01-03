<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->enum('plan', ['free', 'starter', 'pro', 'enterprise'])->default('free');
            $table->jsonb('config')->default('{}');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
