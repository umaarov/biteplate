<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Persistence record for a dining table. The behaviour-rich
 * {@see \App\Domain\Tables\Table} aggregate is reconstituted from this row by
 * the table repository — this model is deliberately dumb.
 */
class RestaurantTable extends Model
{
    protected $fillable = ['number', 'capacity', 'status', 'party_size', 'section'];

    protected $casts = [
        'number' => 'integer',
        'capacity' => 'integer',
        'party_size' => 'integer',
    ];
}
