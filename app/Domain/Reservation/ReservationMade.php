<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final readonly class ReservationMade implements DomainEvent
{
    public function __construct(
        public int $reservationId,
        public int $tableNumber,
        public string $customerName,
        public int $partySize,
        public DateTimeImmutable $startsAt,
        private DateTimeImmutable $at = new DateTimeImmutable(),
    ) {
    }

    public function name(): string
    {
        return 'reservation.made';
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }

    public function payload(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'table_number' => $this->tableNumber,
            'customer_name' => $this->customerName,
            'party_size' => $this->partySize,
            'starts_at' => $this->startsAt->format(DATE_ATOM),
        ];
    }
}
