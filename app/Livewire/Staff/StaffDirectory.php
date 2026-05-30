<?php

declare(strict_types=1);

namespace App\Livewire\Staff;

use App\Domain\Staff\Permission;
use App\Domain\Staff\StaffFactory;
use App\Infrastructure\Auth\LdapStaffDirectory;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Manager-only staff directory, read from OpenLDAP. Also shows, for each role,
 * the exact permission set the polymorphic {@see \App\Domain\Staff\Staff}
 * subclasses grant — the RBAC model from Scenario E, made visible.
 */
#[Layout('layouts.app')]
final class StaffDirectory extends Component
{
    public function render(LdapStaffDirectory $directory): View
    {
        $roleMatrix = [];
        foreach (\App\Domain\Staff\Role::cases() as $role) {
            $staff = StaffFactory::create($role, 'sample', $role->label());
            $roleMatrix[$role->label()] = array_map(
                static fn (Permission $p) => $p->label(),
                $staff->permissions(),
            );
        }

        return view('livewire.staff.staff-directory', [
            'directory' => $directory->all(),
            'roleMatrix' => $roleMatrix,
            'allPermissions' => array_map(static fn (Permission $p) => $p->label(), Permission::cases()),
        ]);
    }
}
