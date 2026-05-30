<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;

/**
 * A simple in-process notification sink. Invaluable for tests and the demo
 * console, and used by the live notification feed in the POS UI.
 */
final class InMemoryNotificationChannel implements NotificationChannel
{
    /** @var list<array{audience: string, message: string, at: DateTimeImmutable}> */
    private array $messages = [];

    public function send(string $audience, string $message): void
    {
        $this->messages[] = [
            'audience' => $audience,
            'message' => $message,
            'at' => new DateTimeImmutable(),
        ];
    }

    /** @return list<array{audience: string, message: string, at: DateTimeImmutable}> */
    public function all(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
