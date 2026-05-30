<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\TableRepository;
use App\Domain\Shared\DomainException;
use App\Domain\Tables\Table;

/** Thin use-case layer over the table aggregate's State-pattern transitions. */
final class TableService
{
    public function __construct(private readonly TableRepository $tables)
    {
    }

    /** @return list<Table> */
    public function floor(): array
    {
        return $this->tables->all();
    }

    public function seat(int $number, int $partySize): Table
    {
        return $this->mutate($number, fn (Table $t) => $t->seat($partySize));
    }

    public function reserve(int $number): Table
    {
        return $this->mutate($number, fn (Table $t) => $t->reserve());
    }

    public function requestBill(int $number): Table
    {
        return $this->mutate($number, fn (Table $t) => $t->requestBill());
    }

    public function clear(int $number): Table
    {
        return $this->mutate($number, fn (Table $t) => $t->clear());
    }

    public function free(int $number): Table
    {
        return $this->mutate($number, fn (Table $t) => $t->free());
    }

    private function mutate(int $number, callable $action): Table
    {
        $table = $this->tables->find($number) ?? throw new DomainException("Table {$number} does not exist.");
        $action($table);
        $this->tables->save($table);

        return $table;
    }
}
