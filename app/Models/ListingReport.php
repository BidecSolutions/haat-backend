<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingReport extends Model
{
    protected $fillable = ['listing_id', 'user_id', 'reason', 'description'];

    public function listing() {
        return $this->belongsTo(Listing::class);
    }
}

