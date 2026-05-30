<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'table_number', 'customer_name', 'phone', 'party_size',
        'starts_at', 'status', 'reminder_sent',
    ];

    protected $casts = [
        'table_number' => 'integer',
        'party_size' => 'integer',
        'starts_at' => 'datetime',
        'reminder_sent' => 'boolean',
    ];
}
