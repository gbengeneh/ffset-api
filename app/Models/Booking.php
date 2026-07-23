<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'player_id',
        'name',
        'phone',
        'date',
        'time',
        'guests',
        'occasion',
        'special_request',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'guests' => 'integer',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
