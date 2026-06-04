<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function list(Tenant $tenant, int $perPage = 20): LengthAwarePaginator
    {
        return User::where('tenant_id', $tenant->id)->paginate($perPage);
    }

    public function updateRole(User $target, User $actor, string $role): User
    {
        if ($actor->role !== 'owner' && $actor->role !== 'admin') {
            throw ValidationException::withMessages(['role' => 'Insufficient permissions.']);
        }

        if ($target->role === 'owner' && $actor->role !== 'owner') {
            throw ValidationException::withMessages(['role' => 'Cannot change owner role.']);
        }

        $target->update(['role' => $role]);

        return $target->fresh();
    }

    public function remove(User $target, User $actor): void
    {
        if ($actor->role !== 'owner' && $actor->role !== 'admin') {
            throw ValidationException::withMessages(['user' => 'Insufficient permissions.']);
        }

        if ($target->id === $actor->id) {
            throw ValidationException::withMessages(['user' => 'Cannot remove yourself.']);
        }

        if ($target->role === 'owner') {
            throw ValidationException::withMessages(['user' => 'Cannot remove the owner.']);
        }

        $target->delete();
    }
}
