<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Staff\Role;
use LdapRecord\Container;
use Throwable;

/**
 * Reads the staff directory straight from OpenLDAP via LdapRecord.
 *
 * In production OpenLDAP is the source of truth for staff identities and their
 * group memberships (which Keycloak also federates for SSO). This directory view
 * proves LDAP is a first-class data source, not just an auth backend. When LDAP
 * is unreachable (e.g. running locally without the Docker stack) it degrades
 * gracefully to a clearly-labelled demo directory.
 */
final class LdapStaffDirectory
{
    /** @return array{connected: bool, staff: list<array{name: string, uid: string, email: string, role: string}>} */
    public function all(): array
    {
        try {
            $connection = Container::getInstance()->getDefaultConnection();
            $base = config('ldap.connections.default.settings.base_dn', 'dc=biteplate,dc=local');

            $results = $connection->query()
                ->in($base)
                ->rawFilter('(objectClass=inetOrgPerson)')
                ->get();

            $staff = [];
            foreach ($results as $entry) {
                $staff[] = [
                    'name' => $entry['cn'][0] ?? ($entry['uid'][0] ?? 'Unknown'),
                    'uid' => $entry['uid'][0] ?? '',
                    'email' => $entry['mail'][0] ?? '',
                    'role' => $this->roleFromGroups($entry['memberof'] ?? [])->label(),
                ];
            }

            if ($staff !== []) {
                return ['connected' => true, 'staff' => $staff];
            }
        } catch (Throwable) {
            // fall through to the demo directory
        }

        return ['connected' => false, 'staff' => $this->demoDirectory()];
    }

    /** @param list<string> $groups */
    private function roleFromGroups(array $groups): Role
    {
        foreach ($groups as $dn) {
            $dn = strtolower($dn);
            foreach (Role::cases() as $role) {
                if (str_contains($dn, str_replace('_', '', $role->value)) || str_contains($dn, $role->value)) {
                    return $role;
                }
            }
        }

        return Role::Waiter;
    }

    /** @return list<array{name: string, uid: string, email: string, role: string}> */
    private function demoDirectory(): array
    {
        return [
            ['name' => 'Alex Morgan', 'uid' => 'amorgan', 'email' => 'alex.morgan@biteplate.local', 'role' => Role::Manager->label()],
            ['name' => 'Marco Rossi', 'uid' => 'mrossi', 'email' => 'marco.rossi@biteplate.local', 'role' => Role::HeadChef->label()],
            ['name' => 'Sam Lee', 'uid' => 'slee', 'email' => 'sam.lee@biteplate.local', 'role' => Role::Waiter->label()],
            ['name' => 'Dana Kerr', 'uid' => 'dkerr', 'email' => 'dana.kerr@biteplate.local', 'role' => Role::Cashier->label()],
        ];
    }
}
