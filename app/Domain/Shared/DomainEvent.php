<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;

/**
 * A fact that has happened in the domain (an order was sent to the kitchen,
 * a reservation was made). Events are the payload the {@see EventBus} ships to
 * Kafka, and the mechanism the Observer pattern uses to fan changes out to
 * waitstaff, the manager dashboard and the kitchen displays.
 */
interface DomainEvent
{
    /** Stable machine name, e.g. "order.sent_to_kitchen" — used as the routing key. */
    public function name(): string;

    public function occurredAt(): DateTimeImmutable;

    /** @return array<string, mixed> JSON-serialisable payload. */
    public function payload(): array;
}
