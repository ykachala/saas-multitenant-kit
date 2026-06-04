<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'alpha_dash', 'max:63', 'unique:tenants,subdomain'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $result = $this->authService->registerTenant(
            $data['tenant_name'],
            $data['subdomain'],
            $data['name'],
            $data['email'],
            $data['password'],
        );

        return $this->success($result, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $tenant = tenant();

        if ($tenant === null) {
            return $this->error('Tenant not found.', 404);
        }

        $token = $this->authService->login($tenant, $data['email'], $data['password']);

        return $this->success(compact('token'));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(['logged_out' => true]);
    }

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['sometimes', 'in:admin,member'],
        ]);

        $tenant = $request->user()->tenant;
        $invite = $this->authService->createInvite($tenant, $data['email'], $data['role'] ?? 'member');

        return $this->success($invite, 201);
    }

    public function acceptInvite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = $this->authService->acceptInvite($data['token'], $data['name'], $data['password']);

        return $this->success($user, 201);
    }
}
