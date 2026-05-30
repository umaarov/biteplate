<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $layout = [
            [1, 2, 'Window'], [2, 2, 'Window'], [3, 4, 'Window'],
            [4, 4, 'Main'], [5, 4, 'Main'], [6, 6, 'Main'],
            [7, 6, 'Main'], [8, 2, 'Bar'], [9, 2, 'Bar'],
            [10, 8, 'Garden'], [11, 4, 'Garden'], [12, 4, 'Garden'],
        ];

        foreach ($layout as [$number, $capacity, $section]) {
            RestaurantTable::updateOrCreate(
                ['number' => $number],
                ['capacity' => $capacity, 'section' => $section, 'status' => 'free', 'party_size' => null],
            );
        }
    }
}
