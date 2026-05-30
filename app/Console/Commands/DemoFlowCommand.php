<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Services\AnalyticsService;
use App\Application\Services\BillingService;
use App\Application\Services\KitchenService;
use App\Application\Services\OrderService;
use App\Application\Services\TableService;
use App\Domain\Shared\NotificationChannel;
use App\Infrastructure\Notifications\CacheNotificationChannel;
use Illuminate\Console\Command;

/**
 * End-to-end demonstration of the patterns working together in one flow:
 * seat (State) → order with customisation (Factory + Decorator) → send to kitchen
 * (Observer + allergy alert + audit Singleton) → prepare/ready (Command) →
 * bill with a runtime strategy swap (Strategy + Facade) → analytics (Iterator).
 */
final class DemoFlowCommand extends Command
{
    protected $signature = 'biteplate:demo {--table=4} {--staff=EMP-22}';

    protected $description = 'Run an end-to-end BitePlate order lifecycle demonstration';

    public function handle(
        TableService $tables,
        OrderService $orders,
        KitchenService $kitchen,
        BillingService $billing,
        AnalyticsService $analytics,
        NotificationChannel $feed,
    ): int {
        $table = (int) $this->option('table');
        $staff = (string) $this->option('staff');

        $this->components->info("Seating party of 2 at table {$table}");
        $tables->seat($table, 2);

        $order = $orders->startDraft($table, $staff);
        $this->line("  Draft order <fg=cyan>{$order->id()}</> opened");

        $orders->addItem($order->id(), 'MN-BURG', 1, [
            'extras' => ['bacon', 'cheese'],
            'avoid' => ['gluten'],
            'special' => 'medium rare',
        ]);
        $orders->addItem($order->id(), 'BV-COLA', 2);
        $this->line('  Added customised burger (Decorator) + 2 colas');

        $this->components->info('Sending to kitchen (Observer + allergy alert + audit log)');
        $orders->sendToKitchen($order->id());

        if ($feed instanceof CacheNotificationChannel) {
            foreach (array_reverse($feed->recent(10)) as $n) {
                $this->line(sprintf('  <fg=yellow>[%s]</> %s', $n['audience'], $n['message']));
            }
        }

        $this->components->info('Kitchen board by station (Composite tickets → StationRouter)');
        foreach ($kitchen->board()['stations'] as $station => $tickets) {
            $this->line(sprintf('  %s: %d ticket(s)', strtoupper($station), count($tickets)));
        }

        $this->components->info('Working the queue (Command pattern)');
        $kitchen->prepare($order->id(), 'Marco');
        $kitchen->markReady($order->id());
        $this->line('  Prepared and marked ready');

        $this->components->info('Generating bill — Happy Hour strategy, 10% tip, split 2 ways (Strategy + Facade)');
        $bill = $billing->finalize($order->id(), 'happy_hour', tipPercent: 10.0, splitWays: 2);
        $this->line('');
        foreach (explode("\n", $bill->receipt()) as $line) {
            $this->line('  '.$line);
        }
        $kitchen->serve($order->id());
        $billing->close($table);

        $this->newLine();
        $this->components->info('Manager analytics (Iterator over the history Singleton)');
        $dash = $analytics->dashboard();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Orders recorded', $dash['orders']],
                ['Total revenue', $dash['revenue']->format()],
                ['Food / Drinks', $dash['food']->format().' / '.$dash['drinks']->format()],
                ['Avg spend / table', $dash['avg_spend_per_table']->format()],
                ['Peak hour', $dash['peak_hour'] ?? '—'],
                ['Wasteful cancels', $dash['waste']],
                ['Top item', array_key_first($dash['top_items']) ?? '—'],
            ],
        );

        $this->newLine();
        $this->components->info('Demonstration complete — all patterns exercised through the real application stack.');

        return self::SUCCESS;
    }
}
