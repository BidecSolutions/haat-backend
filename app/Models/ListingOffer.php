<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingOffer extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'amount',
        'message',
        'status',
        'expires_at',
        'responded_at',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class)->with('images');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}