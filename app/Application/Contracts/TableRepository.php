<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Tables\Table;

interface TableRepository
{
    /** @return list<Table> */
    public function all(): array;

    public function find(int $number): ?Table;

    public function save(Table $table): void;
}
