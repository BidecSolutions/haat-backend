<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Exceptions;

class ListingView extends Model
{
    protected $fillable = [
        'viewable_id',
        'viewable_type',
        'user_id',
        'guest_id',
        'ip_address',
    ];

    public function viewable()
    {
        return $this->morphTo();
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function booted()
    {
        static::saving(function ($model) {
            if (is_null($model->user_id) == is_null($model->guest_id)) {
                throw new Exceptions('Either user_id or guest_id must be set, but not both.');
            }
        });
    }

    // other model methods/relationships...
}
