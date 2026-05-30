<?php

declare(strict_types=1);

namespace App\Domain\Staff;

/**
 * Discrete capabilities in the system. Permissions — not roles — are what the
 * code checks (`$staff->can(Permission::CloseBill)`), so adding a new role never
 * means hunting down scattered `if role == ...` checks (Scenario E).
 */
enum Permission: string
{
    case ViewFloor = 'view_floor';
    case TakeOrder = 'take_order';
    case ModifyOrder = 'modify_order';
    case ViewKitchenQueue = 'view_kitchen_queue';
    case ManageKitchenQueue = 'manage_kitchen_queue';
    case ViewBilling = 'view_billing';
    case CloseBill = 'close_bill';
    case ViewReports = 'view_reports';
    case ManageMenu = 'manage_menu';
    case ManageStaff = 'manage_staff';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
