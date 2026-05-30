<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Ordering\Order;

interface OrderRepository
{
    public function nextId(): string;

    public function save(Order $order): void;

    public function find(string $id): ?Order;

    /** The open draft order for a table, if one exists. */
    public function draftForTable(int $tableNumber): ?Order;

    /** @return list<Order> Orders currently live on the kitchen board. */
    public function activeForKitchen(): array;

    /** @return list<Order> Orders for a table that are billable (sent/in-prep/ready/served). */
    public function billableForTable(int $tableNumber): array;

    /** @return list<Order> Every billable order across the floor. */
    public function allBillable(): array;
}
