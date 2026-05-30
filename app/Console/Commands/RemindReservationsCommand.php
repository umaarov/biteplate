<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Services\ReservationService;
use Illuminate\Console\Command;

/** Sends the two-hours-before reservation reminder (Scenario B). Runs on a schedule. */
final class RemindReservationsCommand extends Command
{
    protected $signature = 'biteplate:reservations:remind';

    protected $description = 'Send reminder SMS for reservations starting in ~2 hours';

    public function handle(ReservationService $reservations): int
    {
        $sent = $reservations->sendDueReminders();
        $this->info("Sent {$sent} reservation reminder(s).");

        return self::SUCCESS;
    }
}
