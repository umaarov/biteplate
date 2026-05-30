<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Outbound port for domain events.
 *
 * The domain depends only on this interface; the application is free to bind it
 * to Kafka in production or to a synchronous, in-process dispatcher in tests and
 * local development. This is the seam that keeps the messaging technology out of
 * the business rules (Dependency Inversion).
 */
interface EventBus
{
    public function publish(DomainEvent $event): void;
}
