<?php

declare(strict_types=1);

namespace App\Domain\Staff;

/**
 * Abstract base for all staff. Each subclass declares its own {@see role()} and
 * {@see permissions()}; the shared {@see can()} check lives here once. This is
 * straightforward inheritance + polymorphism doing the job a sprawl of role
 * conditionals would otherwise do.
 */
abstract class Staff
{
    public function __construct(
        protected readonly string $id,
        protected readonly string $name,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    abstract public function role(): Role;

    /** @return list<Permission> */
    abstract public function permissions(): array;

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
