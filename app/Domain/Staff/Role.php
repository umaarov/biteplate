<?php

declare(strict_types=1);

namespace App\Domain\Staff;

/**
 * The four BitePlate staff roles. The string values intentionally match the
 * Keycloak realm-role / LDAP group names, so the role mapped off an OIDC token
 * resolves straight to a {@see Staff} subclass via {@see StaffFactory}.
 */
enum Role: string
{
    case Manager = 'manager';
    case HeadChef = 'head_chef';
    case Waiter = 'waiter';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::HeadChef => 'Head Chef',
            self::Waiter => 'Waiter',
            self::Cashier => 'Cashier',
        };
    }
}
