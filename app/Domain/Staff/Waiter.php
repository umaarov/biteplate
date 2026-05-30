<?php

declare(strict_types=1);

namespace App\Domain\Staff;

final class Waiter extends Staff
{
    public function role(): Role
    {
        return Role::Waiter;
    }

    public function permissions(): array
    {
        return [
            Permission::ViewFloor,
            Permission::TakeOrder,
            Permission::ModifyOrder,
            Permission::ViewKitchenQueue,
            Permission::ViewBilling,
        ];
    }
}
