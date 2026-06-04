<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Public — tenant context required for login
Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('invites/accept', [AuthController::class, 'acceptInvite']);

    Route::middleware('tenant')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Authenticated routes — requires valid Sanctum token + active tenant
    Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);

        // Tenant
        Route::get('tenant', [TenantController::class, 'show']);
        Route::patch('tenant/config', [TenantController::class, 'updateConfig']);

        // Users
        Route::get('users', [UserController::class, 'index']);
        Route::patch('users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);

        // Invites — owner/admin only
        Route::post('invites', [AuthController::class, 'invite']);

        // Billing
        Route::post('billing/subscribe', [BillingController::class, 'subscribe']);
        Route::delete('billing/subscription', [BillingController::class, 'cancel']);
        Route::get('billing/invoices', [BillingController::class, 'invoices']);
    });

    // Billing webhooks — no auth, verified by driver
    Route::post('billing/webhook', [BillingController::class, 'webhook']);

    // Health
    Route::get('health', HealthController::class);
});
