<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    //
    protected $table = 'guest';
    protected $fillable = [
        'ip_address',
        'guest_id',
        'user_agent',
        'session_id',
    ];
    public $timestamps = true;
}
