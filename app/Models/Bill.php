<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        'order_id', 'subtotal_minor', 'discount_minor', 'tax_minor', 'tax_rate',
        'tip_minor', 'total_minor', 'currency', 'split_ways', 'split_shares',
        'pricing_strategy', 'notes', 'issued_at',
    ];

    protected $casts = [
        'subtotal_minor' => 'integer',
        'discount_minor' => 'integer',
        'tax_minor' => 'integer',
        'tip_minor' => 'integer',
        'total_minor' => 'integer',
        'tax_rate' => 'float',
        'split_ways' => 'integer',
        'split_shares' => 'array',
        'notes' => 'array',
        'issued_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
