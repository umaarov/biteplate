<?php

declare(strict_types=1);

namespace App\Domain\Kitchen;

use App\Domain\Ordering\Order;
use App\Domain\Shared\KitchenStation;
use App\Domain\Shared\KitchenTicket;

/**
 * Scenario E — the Multi-Screen Kitchen.
 *
 * The waiter still sends one order to one {@see KitchenQueue}; this router fans
 * that order's tickets out to the correct station boards (hot / cold / dessert /
 * bar) using the station each {@see KitchenTicket} already carries. Because
 * routing reads a property of the ticket rather than branching on dish type, a
 * new station is supported by adding an enum case — the waiter's interface never
 * changes. {@see master()} gives the head chef the all-stations overview.
 */
final class StationRouter
{
    /**
     * @param  list<KitchenTicket>  $tickets
     * @return array<string, list<KitchenTicket>>  station value => its tickets
     */
    public function route(array $tickets): array
    {
        $boards = [];

        foreach (KitchenStation::cases() as $station) {
            $boards[$station->value] = [];
        }

        foreach ($tickets as $ticket) {
            $boards[$ticket->station->value][] = $ticket;
        }

        return array_filter($boards, static fn (array $b) => $b !== []);
    }

    /** @return array<string, list<KitchenTicket>> */
    public function routeOrder(Order $order): array
    {
        return $this->route($order->kitchenTickets());
    }

    /**
     * The head chef's master view: every active ticket across all stations.
     *
     * @param  list<Order>  $orders
     * @return list<array{order: string, table: int, ticket: KitchenTicket}>
     */
    public function master(array $orders): array
    {
        $rows = [];

        foreach ($orders as $order) {
            foreach ($order->kitchenTickets() as $ticket) {
                $rows[] = ['order' => $order->id(), 'table' => $order->tableNumber(), 'ticket' => $ticket];
            }
        }

        return $rows;
    }
}
