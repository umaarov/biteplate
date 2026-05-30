<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Domain\Shared\NotificationChannel;
use App\Infrastructure\Notifications\CacheNotificationChannel;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The live notification bell. Polls the {@see CacheNotificationChannel} that the
 * Observer pattern's notifiers write to, so allergen alerts, "order ready" pings
 * and waste warnings surface to staff in near real time.
 */
final class NotificationFeed extends Component
{
    /** @var list<array{audience: string, message: string, at: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->refreshFeed();
    }

    public function refreshFeed(): void
    {
        $channel = app(NotificationChannel::class);
        $this->items = $channel instanceof CacheNotificationChannel ? $channel->recent(15) : [];
    }

    public function render(): View
    {
        return view('livewire.shared.notification-feed');
    }
}
