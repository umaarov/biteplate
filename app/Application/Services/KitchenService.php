<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\OrderRepository;
use App\Domain\Kitchen\Chef;
use App\Domain\Kitchen\Command\CancelOrderCommand;
use App\Domain\Kitchen\Command\PrepareOrderCommand;
use App\Domain\Kitchen\KitchenQueue;
use App\Domain\Kitchen\StationRouter;
use App\Domain\Ordering\Order;
use App\Domain\Ordering\OrderStatus;
use App\Domain\Shared\DomainException;
use App\Infrastructure\Observers\OrderObserverRegistrar;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Drives the kitchen board using the Command pattern.
 *
 * Prepare/cancel run through real {@see PrepareOrderCommand}/{@see CancelOrderCommand}
 * objects on a {@see KitchenQueue}. Because each web request is a fresh process,
 * the undo stack is persisted as a lightweight memento journal in the cache —
 * the same "remember the previous state so you can reverse it" idea the Command's
 * own undo() uses, just durable across requests so the head chef's Undo button
 * works between page loads.
 */
final class KitchenService
{
    private const JOURNAL = 'biteplate:kitchen:undo';

    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderObserverRegistrar $observers,
        private readonly StationRouter $router,
        private readonly Cache $cache,
    ) {
    }

    /**
     * The live board grouped by station, plus the head chef's master list.
     *
     * @return array{stations: array<string, list<\App\Domain\Shared\KitchenTicket>>, master: list<array{order: string, table: int, ticket: \App\Domain\Shared\KitchenTicket}>, orders: list<Order>}
     */
    public function board(): array
    {
        $orders = $this->orders->activeForKitchen();
        $tickets = [];
        foreach ($orders as $order) {
            $tickets = [...$tickets, ...$order->kitchenTickets()];
        }

        return [
            'stations' => $this->router->route($tickets),
            'master' => $this->router->master($orders),
            'orders' => $orders,
        ];
    }

    public function prepare(string $orderId, string $chefName = 'Kitchen'): Order
    {
        $order = $this->require($orderId);
        $this->observers->attachAll($order);

        $previous = $order->status();
        $queue = new KitchenQueue();
        $queue->run(new PrepareOrderCommand(new Chef('KIT', $chefName), $order));

        $this->orders->save($order);
        $this->journalPush($orderId, $previous);

        return $order;
    }

    public function markReady(string $orderId): Order
    {
        $order = $this->require($orderId);
        $this->observers->attachAll($order);

        $previous = $order->status();
        $order->markReady();

        $this->orders->save($order);
        $this->journalPush($orderId, $previous);

        return $order;
    }

    public function serve(string $orderId): Order
    {
        $order = $this->require($orderId);
        $previous = $order->status();
        $order->serve();
        $this->orders->save($order);
        $this->journalPush($orderId, $previous);

        return $order;
    }

    public function cancel(string $orderId, string $reason, string $chefName = 'Kitchen'): Order
    {
        $order = $this->require($orderId);
        $this->observers->attachAll($order);

        $previous = $order->status();
        $queue = new KitchenQueue();
        $queue->run(new CancelOrderCommand(new Chef('KIT', $chefName), $order, $reason));

        $this->orders->save($order);
        $this->journalPush($orderId, $previous);

        return $order;
    }

    public function canUndo(): bool
    {
        return $this->journal() !== [];
    }

    /** Reverse the last kitchen action — the persisted equivalent of command undo(). */
    public function undoLast(): ?Order
    {
        $journal = $this->journal();
        $entry = array_shift($journal);

        if ($entry === null) {
            throw new DomainException('There is nothing to undo on the kitchen board.');
        }

        $this->cache->put(self::JOURNAL, $journal, now()->addHours(12));

        $order = $this->require($entry['order_id']);
        $this->observers->attachAll($order);
        $order->restoreStatus(OrderStatus::from($entry['previous']));
        $this->orders->save($order);

        return $order;
    }

    private function journalPush(string $orderId, OrderStatus $previous): void
    {
        $journal = $this->journal();
        array_unshift($journal, ['order_id' => $orderId, 'previous' => $previous->value]);
        $this->cache->put(self::JOURNAL, array_slice($journal, 0, 50), now()->addHours(12));
    }

    /** @return list<array{order_id: string, previous: string}> */
    private function journal(): array
    {
        return $this->cache->get(self::JOURNAL, []);
    }

    private function require(string $orderId): Order
    {
        return $this->orders->find($orderId) ?? throw new DomainException("Order {$orderId} not found.");
    }
}
