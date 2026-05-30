<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderHistoryEntry extends Model
{
    protected $table = 'order_history';

    public const UPDATED_AT = null; // append-only: rows are never updated

    protected $fillable = [
        'order_id', 'table_number', 'staff_id', 'lines', 'total_minor',
        'currency', 'pricing_strategy', 'covers', 'cancelled', 'wasteful', 'placed_at',
    ];

    protected $casts = [
        'table_number' => 'integer',
        'lines' => 'array',
        'total_minor' => 'integer',
        'covers' => 'integer',
        'cancelled' => 'boolean',
        'wasteful' => 'boolean',
        'placed_at' => 'datetime',
    ];
}
