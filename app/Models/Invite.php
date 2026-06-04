<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $tenant_id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property Carbon|null $accepted_at
 * @property Carbon $expires_at
 */
class Invite extends Model
{
    /** @use HasFactory<InviteFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
