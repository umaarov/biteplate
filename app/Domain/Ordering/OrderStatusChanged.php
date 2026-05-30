<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/** Emitted whenever an order changes lifecycle status; shipped to Kafka. */
final readonly class OrderStatusChanged implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public int $tableNumber,
        public string $waiterId,
        public OrderStatus $from,
        public OrderStatus $to,
        private DateTimeImmutable $at = new DateTimeImmutable(),
    ) {
    }

    public function name(): string
    {
        return 'order.'.$this->to->value;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }

    public function payload(): array
    {
        return [
            'order_id' => $this->orderId,
            'table_number' => $this->tableNumber,
            'waiter_id' => $this->waiterId,
            'from' => $this->from->value,
            'to' => $this->to->value,
            'at' => $this->at->format(DATE_ATOM),
        ];
    }
}
