<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->list($request->user()->tenant);

        return $this->paginated($users);
    }

    public function updateRole(Request $request, string $userId): JsonResponse
    {
        $data = $request->validate(['role' => ['required', 'in:admin,member']]);

        $target = $request->user()->tenant->users()->findOrFail($userId);
        $updated = $this->userService->updateRole($target, $request->user(), $data['role']);

        return $this->success($updated);
    }

    public function destroy(Request $request, string $userId): JsonResponse
    {
        $target = $request->user()->tenant->users()->findOrFail($userId);
        $this->userService->remove($target, $request->user());

        return response()->json(null, 204);
    }
}
