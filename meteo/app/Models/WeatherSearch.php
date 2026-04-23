<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherSearch extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'city',
        'country',
        'latitude',
        'longitude',
        'searched_at',
    ];

    protected $casts = [
        'searched_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
