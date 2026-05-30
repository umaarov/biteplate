<?php

declare(strict_types=1);

namespace App\Domain\Staff;

final class Cashier extends Staff
{
    public function role(): Role
    {
        return Role::Cashier;
    }

    /** Views and closes bills; cannot touch kitchen orders. */
    public function permissions(): array
    {
        return [
            Permission::ViewFloor,
            Permission::ViewBilling,
            Permission::CloseBill,
        ];
    }
}
