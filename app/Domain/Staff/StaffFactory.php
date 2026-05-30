<?php

declare(strict_types=1);

namespace App\Domain\Staff;

/**
 * Builds the right {@see Staff} subclass from a role. The auth layer hands us the
 * role it read off a Keycloak token (or LDAP group) and gets back a fully-typed
 * staff object whose permissions are then enforced everywhere.
 */
final class StaffFactory
{
    public static function create(Role $role, string $id, string $name): Staff
    {
        return match ($role) {
            Role::Manager => new Manager($id, $name),
            Role::HeadChef => new HeadChef($id, $name),
            Role::Waiter => new Waiter($id, $name),
            Role::Cashier => new Cashier($id, $name),
        };
    }

    public static function fromRoleName(string $roleName, string $id, string $name): Staff
    {
        $role = Role::tryFrom(strtolower($roleName)) ?? Role::Waiter;

        return self::create($role, $id, $name);
    }
}
