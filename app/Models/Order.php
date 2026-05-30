<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'table_number', 'staff_id', 'status', 'pricing_strategy',
        'subtotal_minor', 'currency', 'cancelled', 'wasteful',
        'cancellation_reason', 'placed_at',
    ];

    protected $casts = [
        'table_number' => 'integer',
        'subtotal_minor' => 'integer',
        'cancelled' => 'boolean',
        'wasteful' => 'boolean',
        'placed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }
}
