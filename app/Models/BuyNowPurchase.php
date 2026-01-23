<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuyNowPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'buyer_id',
        'amount',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    // 🔗 Relationships

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
