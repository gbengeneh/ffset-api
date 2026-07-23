<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionRegistration extends Model
{
    protected $fillable = [
        'competition_id',
        'player_id',
        'name',
        'phone',
        'email',
        'gamertag',
        'game',
        'state',
        'reference_code',
        'payment_status',
        'sale_id',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
