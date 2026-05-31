<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'name', 'quantity', 'unit_price_minor', 'currency',
        'category', 'station', 'is_drink', 'allergens', 'notes', 'summary', 'tickets',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_minor' => 'integer',
        'is_drink' => 'boolean',
        'allergens' => 'array',
        'notes' => 'array',
        'tickets' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
