<?php declare(strict_types=1);

namespace App\Enums;

enum TokenAbility: string
{
    case READ  = 'read';
    case WRITE = 'write';
    case ADMIN = 'admin';

    public static function forRole(string $role): array
    {
        return match ($role) {
            'owner' => [self::READ->value, self::WRITE->value, self::ADMIN->value],
            'admin' => [self::READ->value, self::WRITE->value],
            default => [self::READ->value],
        };
    }
}
