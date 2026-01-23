<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingAttribute extends Model
{
    use HasFactory;
    protected $table = 'listing_attributes';
    protected $fillable = [
        'listing_id',
        'key',
        'value',
    ];

    /**
     * Relationship: Attribute belongs to a listing.
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
