<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Auth\CurrentStaff;
use App\Application\Contracts\MenuRepository;
use App\Application\Contracts\OrderHistoryRepository;
use App\Application\Contracts\OrderRepository;
use App\Application\Contracts\TableRepository;
use App\Domain\Billing\BillingFacade;
use App\Domain\Billing\TaxCalculator;
use App\Domain\History\OrderHistoryLog;
use App\Domain\Shared\EventBus;
use App\Domain\Shared\NotificationChannel;
use App\Infrastructure\Messaging\KafkaEventBus;
use App\Infrastructure\Messaging\LogEventBus;
use App\Infrastructure\Notifications\CacheNotificationChannel;
use App\Infrastructure\Persistence\EloquentMenuRepository;
use App\Infrastructure\Persistence\EloquentOrderHistoryRepository;
use App\Infrastructure\Persistence\EloquentOrderRepository;
use App\Infrastructure\Persistence\EloquentTableRepository;
use Illuminate\Support\ServiceProvider;

/**
 * The composition root. Binds every domain/application port to a concrete
 * infrastructure implementation, so the rest of the app programs to interfaces
 * and never news up infrastructure directly. Swapping Kafka for the log bus, or
 * Eloquent for another store, happens entirely here.
 */
final class DomainServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        MenuRepository::class => EloquentMenuRepository::class,
        OrderRepository::class => EloquentOrderRepository::class,
        TableRepository::class => EloquentTableRepository::class,
        OrderHistoryRepository::class => EloquentOrderHistoryRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(NotificationChannel::class, function ($app): NotificationChannel {
            return new CacheNotificationChannel($app->make('cache.store'));
        });

        // Kafka in production (when the extension and broker are configured),
        // a logging bus everywhere else — same interface either way.
        $this->app->singleton(EventBus::class, function ($app): EventBus {
            $kafka = config('biteplate.kafka');

            if ($kafka['enabled'] && extension_loaded('rdkafka')) {
                return new KafkaEventBus($kafka['brokers'], $kafka['topic']);
            }

            return new LogEventBus($app->make('log'));
        });

        $this->app->singleton(BillingFacade::class, function (): BillingFacade {
            return new BillingFacade(new TaxCalculator((float) config('biteplate.tax_rate')));
        });

        // Expose the history singleton through the container for injection,
        // while preserving its classic getInstance() identity.
        $this->app->bind(OrderHistoryLog::class, static fn (): OrderHistoryLog => OrderHistoryLog::instance());

        // Request-scoped: caches the resolved staff for the lifetime of one request.
        $this->app->scoped(CurrentStaff::class, static fn ($app): CurrentStaff => new CurrentStaff($app->make('session.store')));
    }

    public function boot(): void
    {
        // Under Octane/FrankenPHP the worker is long-lived, so the in-memory
        // history singleton would otherwise bleed between requests. Reset it at
        // the start of each request for clean per-request isolation.
        if (class_exists(\Laravel\Octane\Events\RequestReceived::class)) {
            $this->app['events']->listen(
                \Laravel\Octane\Events\RequestReceived::class,
                static fn () => OrderHistoryLog::reset(),
            );
        }
    }
}
