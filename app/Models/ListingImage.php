<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingImage extends Model
{
    protected $fillable = [
        'listing_id',
        'image_path',
        'alt_text',
        'order',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}