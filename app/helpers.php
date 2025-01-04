<?php declare(strict_types=1);

use App\Models\Tenant;

if (! function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        try {
            return app('tenant');
        } catch (\Throwable) {
            return null;
        }
    }
}
