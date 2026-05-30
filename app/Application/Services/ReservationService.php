<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\TableRepository;
use App\Domain\Reservation\ReservationMade;
use App\Domain\Shared\EventBus;
use App\Domain\Shared\NotificationChannel;
use App\Domain\Tables\TableStatus;
use App\Models\Reservation;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

/**
 * The reservation pipeline (Scenario B).
 *
 * Booking fans out to several independent actions — confirmation SMS, manager
 * calendar entry, table-availability update, and a Kafka event — none of which
 * the others know about. A new action (WhatsApp, loyalty points) is added by
 * registering another observer/listener, not by editing this method.
 */
final class ReservationService
{
    public function __construct(
        private readonly TableRepository $tables,
        private readonly NotificationChannel $channel,
        private readonly EventBus $bus,
    ) {
    }

    public function reserve(
        int $tableNumber,
        string $customerName,
        ?string $phone,
        int $partySize,
        DateTimeImmutable $startsAt,
    ): Reservation {
        $reservation = Reservation::create([
            'table_number' => $tableNumber,
            'customer_name' => $customerName,
            'phone' => $phone,
            'party_size' => $partySize,
            'starts_at' => $startsAt,
            'status' => 'confirmed',
        ]);

        // 1) Availability: hold the table if it is free right now.
        $table = $this->tables->find($tableNumber);
        if ($table !== null && $table->status() === TableStatus::Free) {
            $table->reserve();
            $this->tables->save($table);
        }

        // 2) Confirmation SMS.
        if ($phone !== null) {
            $this->channel->send(
                'sms:'.$phone,
                sprintf('BitePlate: booking confirmed for %d on %s. Table %d.', $partySize, $startsAt->format('D j M H:i'), $tableNumber),
            );
        }

        // 3) Manager calendar entry.
        $this->channel->send(
            'manager',
            sprintf('Calendar: reservation #%d — %s, party of %d, %s', $reservation->id, $customerName, $partySize, $startsAt->format('D j M H:i')),
        );

        // 4) Broadcast for any out-of-process subscriber.
        $this->bus->publish(new ReservationMade($reservation->id, $tableNumber, $customerName, $partySize, $startsAt));

        return $reservation;
    }

    /** Sends the 2-hours-before reminder for any due bookings. Called on a schedule. */
    public function sendDueReminders(): int
    {
        $window = Carbon::now()->addHours(2);

        $due = Reservation::query()
            ->where('status', 'confirmed')
            ->where('reminder_sent', false)
            ->whereBetween('starts_at', [$window->copy()->subMinutes(5), $window->copy()->addMinutes(5)])
            ->get();

        foreach ($due as $reservation) {
            if ($reservation->phone !== null) {
                $this->channel->send(
                    'sms:'.$reservation->phone,
                    sprintf('BitePlate reminder: your table %d is booked for %s.', $reservation->table_number, $reservation->starts_at->format('H:i')),
                );
            }
            $reservation->update(['reminder_sent' => true]);
        }

        return $due->count();
    }
}
