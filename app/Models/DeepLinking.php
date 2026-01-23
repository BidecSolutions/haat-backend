<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeepLinking extends Model
{
    //
    protected $table = 'deep_linking';
    protected $fillable = [
        'link',
        'link2',
    ];
}
