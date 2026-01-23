<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    //
    protected $table = 'vehicle_data';
    protected $fillable = [
        'make',
        'make_slug',
        'model',
        'model_slug',
        'year',
        'body_style',
        'vehicle_type',
    ];
}
