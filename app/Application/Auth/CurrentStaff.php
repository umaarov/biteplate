<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Staff\Permission;
use App\Domain\Staff\Role;
use App\Domain\Staff\Staff;
use App\Domain\Staff\StaffFactory;
use Illuminate\Contracts\Session\Session;

/**
 * Resolves the signed-in {@see Staff} domain object from the session, whatever
 * identity provider put it there (the Keycloak OIDC callback in production, the
 * dev role switcher locally). Everything in the app asks this for permissions —
 * the polymorphic {@see Staff::can()} is the single authorisation check.
 */
final class CurrentStaff
{
    private const KEY = 'staff';

    private ?Staff $cached = null;

    public function __construct(private readonly Session $session)
    {
    }

    public function check(): bool
    {
        return $this->session->has(self::KEY);
    }

    public function staff(): ?Staff
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $data = $this->session->get(self::KEY);

        if (! is_array($data)) {
            return null;
        }

        return $this->cached = StaffFactory::fromRoleName($data['role'], $data['id'], $data['name']);
    }

    public function can(Permission $permission): bool
    {
        return $this->staff()?->can($permission) ?? false;
    }

    public function login(Role $role, string $id, string $name): void
    {
        $this->cached = null;
        $this->session->put(self::KEY, ['role' => $role->value, 'id' => $id, 'name' => $name]);
    }

    public function logout(): void
    {
        $this->cached = null;
        $this->session->forget(self::KEY);
    }
}
