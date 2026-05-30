<?php

declare(strict_types=1);

namespace App\Domain\Staff;

final class HeadChef extends Staff
{
    public function role(): Role
    {
        return Role::HeadChef;
    }

    /** Runs the kitchen queue; deliberately has no access to billing. */
    public function permissions(): array
    {
        return [
            Permission::ViewFloor,
            Permission::ViewKitchenQueue,
            Permission::ManageKitchenQueue,
        ];
    }
}
