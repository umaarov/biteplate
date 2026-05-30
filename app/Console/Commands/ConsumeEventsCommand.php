<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\NotificationChannel;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

/**
 * Long-running Kafka consumer (Docker service) — the out-of-process half of the
 * Observer pattern. It subscribes to the domain-event topic the
 * {@see \App\Infrastructure\Messaging\KafkaEventBus} publishes to and reacts to
 * each event (here: surfacing it on the live feed and logging it). New reactors
 * are new consumers; the publisher never changes.
 */
final class ConsumeEventsCommand extends Command
{
    protected $signature = 'biteplate:kafka:consume {--timeout=120000}';

    protected $description = 'Consume BitePlate domain events from Kafka and react to them';

    public function handle(NotificationChannel $channel): int
    {
        if (! extension_loaded('rdkafka')) {
            $this->error('The rdkafka PHP extension is not installed. This consumer runs inside the Docker stack.');

            return self::FAILURE;
        }

        $kafka = config('biteplate.kafka');

        $conf = new Conf();
        $conf->set('group.id', $kafka['group']);
        $conf->set('metadata.broker.list', $kafka['brokers']);
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.partition.eof', 'false');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([$kafka['topic']]);

        $this->info("Listening on topic '{$kafka['topic']}' (brokers: {$kafka['brokers']})…");

        while (true) {
            $message = $consumer->consume((int) $this->option('timeout'));

            match ($message->err) {
                RD_KAFKA_RESP_ERR_NO_ERROR => $this->react($message, $channel),
                RD_KAFKA_RESP_ERR__PARTITION_EOF, RD_KAFKA_RESP_ERR__TIMED_OUT => null,
                default => $this->warn('Kafka error: '.$message->errstr()),
            };
        }
    }

    private function react(Message $message, NotificationChannel $channel): void
    {
        $key = $message->key ?? 'event';
        $payload = json_decode((string) $message->payload, true) ?: [];

        $this->line(sprintf('[%s] %s', $key, json_encode($payload)));
        logger()->info('[kafka-consumer] '.$key, $payload);

        // Example reactor: mirror order-ready events to an external kitchen screen.
        if ($key === 'order.ready') {
            $channel->send('external-display', sprintf(
                'Order %s for table %s is ready.',
                $payload['order_id'] ?? '?',
                $payload['table_number'] ?? '?',
            ));
        }
    }
}
