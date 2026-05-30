<?php

declare(strict_types=1);

namespace App\Domain\Staff;

final class Manager extends Staff
{
    public function role(): Role
    {
        return Role::Manager;
    }

    /** A manager can do everything. */
    public function permissions(): array
    {
        return Permission::cases();
    }
}
