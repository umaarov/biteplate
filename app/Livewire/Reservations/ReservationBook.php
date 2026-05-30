<?php

declare(strict_types=1);

namespace App\Livewire\Reservations;

use App\Application\Services\ReservationService;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use DateTimeImmutable;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Booking desk. Creating a reservation fires the multi-step pipeline in
 * {@see ReservationService} (confirmation SMS, manager calendar, availability
 * hold, Kafka event) — new steps are added there without touching this screen.
 */
#[Layout('layouts.app')]
final class ReservationBook extends Component
{
    #[Validate('required|integer')]
    public ?int $tableNumber = null;

    #[Validate('required|string|min:2|max:80')]
    public string $customerName = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('required|integer|min:1|max:20')]
    public int $partySize = 2;

    #[Validate('required|date')]
    public string $date = '';

    #[Validate('required')]
    public string $time = '19:00';

    public ?string $message = null;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function book(ReservationService $reservations): void
    {
        $this->validate([
            'tableNumber' => ['required', Rule::exists('restaurant_tables', 'number')],
        ] + $this->rulesFromAttributes());

        $startsAt = new DateTimeImmutable($this->date.' '.$this->time);

        $reservation = $reservations->reserve(
            $this->tableNumber,
            $this->customerName,
            $this->phone ?: null,
            $this->partySize,
            $startsAt,
        );

        $this->message = "Reservation #{$reservation->id} confirmed — confirmation SMS, calendar entry and availability hold all dispatched.";
        $this->reset(['customerName', 'phone', 'tableNumber']);
        $this->partySize = 2;
    }

    /** @return array<string, mixed> */
    private function rulesFromAttributes(): array
    {
        return [
            'customerName' => ['required', 'string', 'min:2', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'partySize' => ['required', 'integer', 'min:1', 'max:20'],
            'date' => ['required', 'date'],
            'time' => ['required'],
        ];
    }

    public function render(): View
    {
        return view('livewire.reservations.reservation-book', [
            'tables' => RestaurantTable::orderBy('number')->get(),
            'upcoming' => Reservation::where('starts_at', '>=', now()->subHours(1))
                ->orderBy('starts_at')->limit(25)->get(),
        ]);
    }
}
