<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        Reservation::updateOrCreate(
            ['table_number' => 6, 'customer_name' => 'Priya Patel'],
            ['phone' => '+447700900111', 'party_size' => 5, 'starts_at' => Carbon::now()->addHours(2), 'status' => 'confirmed'],
        );

        Reservation::updateOrCreate(
            ['table_number' => 10, 'customer_name' => 'The Okafor Party'],
            ['phone' => '+447700900222', 'party_size' => 8, 'starts_at' => Carbon::now()->addDay()->setTime(19, 30), 'status' => 'confirmed'],
        );
    }
}
