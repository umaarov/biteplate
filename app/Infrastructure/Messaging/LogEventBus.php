<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\EventBus;
use Psr\Log\LoggerInterface;

/**
 * The default {@see EventBus} for local development and tests: it simply logs the
 * event. Because the rest of the system depends on the EventBus interface, it
 * cannot tell whether events are going to a log file or to a Kafka cluster — the
 * point of the abstraction.
 */
final class LogEventBus implements EventBus
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function publish(DomainEvent $event): void
    {
        $this->logger->info('[event] '.$event->name(), $event->payload());
    }
}
