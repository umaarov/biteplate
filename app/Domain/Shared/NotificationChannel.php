<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Outbound port the Observer pattern's notifiers push messages through.
 *
 * The domain observers (WaiterNotifier, AllergyAlert, …) depend only on this
 * interface; the infrastructure decides whether a message becomes an SMS, a
 * Kafka record, a websocket push or an in-memory row for the test suite.
 */
interface NotificationChannel
{
    public function send(string $audience, string $message): void;
}
