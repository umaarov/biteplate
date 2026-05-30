<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'sku', 'name', 'description', 'category', 'station',
        'price_minor', 'currency', 'allergens', 'branch', 'active',
    ];

    protected $casts = [
        'price_minor' => 'integer',
        'allergens' => 'array',
        'active' => 'boolean',
    ];
}
