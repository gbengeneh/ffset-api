<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    protected $fillable = [
        'title',
        'entry_fee_product_id',
        'entry_fee',
        'first_prize',
        'second_prize',
        'third_prize',
        'rules',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'entry_fee' => 'decimal:2',
            'first_prize' => 'decimal:2',
            'second_prize' => 'decimal:2',
            'third_prize' => 'decimal:2',
            'rules' => 'array',
        ];
    }

    public function entryFeeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'entry_fee_product_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CompetitionRegistration::class);
    }
}
