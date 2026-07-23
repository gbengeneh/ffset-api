<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'date',
        'frequency',
        'description',
        'icon',
        'image_url',
        'image_position',
    ];
}
