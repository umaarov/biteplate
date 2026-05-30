<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\EventBus;
use RdKafka\Conf;
use RdKafka\Producer;

/**
 * Production {@see EventBus} backed by Apache Kafka via the php-rdkafka
 * extension. The event name becomes the message key (so all events for one order
 * land on the same partition, preserving per-order ordering) and the payload is
 * JSON. Consumers (the kitchen display worker, the analytics sink) subscribe to
 * the topic independently — classic publish/subscribe, the Observer pattern at
 * infrastructure scale.
 *
 * Only instantiated when ext-rdkafka is present (see AppServiceProvider); the
 * {@see LogEventBus} is used otherwise, so the app runs with or without a broker.
 */
final class KafkaEventBus implements EventBus
{
    private Producer $producer;

    public function __construct(
        private readonly string $brokers,
        private readonly string $topic = 'biteplate.events',
    ) {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('socket.timeout.ms', '1000');
        $this->producer = new Producer($conf);
    }

    public function publish(DomainEvent $event): void
    {
        $topic = $this->producer->newTopic($this->topic);
        $topic->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            json_encode($event->payload(), JSON_THROW_ON_ERROR),
            $event->name(),
        );
        $this->producer->poll(0);
        $this->producer->flush(1000);
    }
}
