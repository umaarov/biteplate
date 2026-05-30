<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\Shared\NotificationChannel;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * A {@see NotificationChannel} that pushes messages onto a capped, audience-keyed
 * list in the cache (Redis in production, the database cache locally). This backs
 * the live notification feed in the POS UI: the Observer pattern's notifiers
 * write here, and the Livewire feed polls it — surviving across requests, which
 * an in-memory channel could not.
 */
final class CacheNotificationChannel implements NotificationChannel
{
    private const KEY = 'biteplate:notifications';

    private const MAX = 50;

    public function __construct(private readonly Cache $cache)
    {
    }

    public function send(string $audience, string $message): void
    {
        $feed = $this->cache->get(self::KEY, []);

        array_unshift($feed, [
            'audience' => $audience,
            'message' => $message,
            'at' => now()->toIso8601String(),
        ]);

        $this->cache->put(self::KEY, array_slice($feed, 0, self::MAX), now()->addHours(12));
    }

    /** @return list<array{audience: string, message: string, at: string}> */
    public function recent(int $limit = 20): array
    {
        return array_slice($this->cache->get(self::KEY, []), 0, $limit);
    }

    public function clear(): void
    {
        $this->cache->forget(self::KEY);
    }
}
