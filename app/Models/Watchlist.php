<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    protected $fillable = ['user_id', 'listing_id', 'type'];

    public function listing() {
        return $this->belongsTo(Listing::class, 'listing_id');
    }
}
