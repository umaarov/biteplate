<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Contracts\TableRepository;
use App\Domain\Tables\State\TableStateFactory;
use App\Domain\Tables\Table;
use App\Domain\Tables\TableStatus;
use App\Models\RestaurantTable;

final class EloquentTableRepository implements TableRepository
{
    public function all(): array
    {
        return RestaurantTable::orderBy('number')
            ->get()
            ->map(fn (RestaurantTable $m) => $this->toDomain($m))
            ->all();
    }

    public function find(int $number): ?Table
    {
        $model = RestaurantTable::where('number', $number)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function save(Table $table): void
    {
        RestaurantTable::updateOrCreate(
            ['number' => $table->number()],
            [
                'capacity' => $table->capacity(),
                'status' => $table->status()->value,
                'party_size' => $table->partySize(),
            ],
        );
    }

    private function toDomain(RestaurantTable $model): Table
    {
        $status = TableStatus::from($model->status);
        $table = new Table($model->number, $model->capacity, TableStateFactory::for($status), $model->section);

        if ($model->party_size !== null) {
            $table->assignParty($model->party_size);
        }

        return $table;
    }
}
