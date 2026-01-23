<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'listings';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'description',
        'listing_type',
        'country_id',
        'regions_id',
        'governorates_id',
        'city_id',
        'area_id',
        'condition',
        'start_price',
        'reserve_price',
        'buy_now_price',
        'allow_offers',
        'quantity',
        'authenticated_bidders_only',
        'pickup_option',
        'shipping_method_id',
        'payment_method_id',
        'longitude',
        'latitude',
        'address',
        'color',
        'size',
        'brand',
        'style',
        'memory',
        'hard_drive_size',
        'cores',
        'storage',
        'category_id',
        'created_by',
        'meta_title',
        'meta_description',
        'is_featured',
        'status',
        'is_active',
        'expire_at',
        'sold_at',
        'note',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'allow_offers' => 'boolean',
        'authenticated_bidders_only' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'integer',
        'expire_at' => 'datetime',
        'sold_at' => 'datetime',
    ];

    /**
     * Appended attributes
     */
    protected $appends = [
        'buyer',
        'country_name',
        'region_name',
        'governorate_name',
        'city_name',
        'area_name',
        'final_buyer',
    ];

    /* -----------------------------------------------------------------
     | Location Relationships
     |-----------------------------------------------------------------*/

    public function countries()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function regions()
    {
        return $this->belongsTo(Regions::class, 'regions_id');
    }

    public function governorates()
    {
        return $this->belongsTo(Governorates::class, 'governorates_id');
    }

    public function cities()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /* -----------------------------------------------------------------
     | Location Accessors
     |-----------------------------------------------------------------*/

    public function getCountryNameAttribute()
    {
        return $this->countries?->name;
    }

    public function getRegionNameAttribute()
    {
        return $this->regions?->name;
    }

    public function getGovernorateNameAttribute()
    {
        return $this->governorates?->name;
    }

    public function getCityNameAttribute()
    {
        return $this->cities?->name;
    }

    public function getAreaNameAttribute()
    {
        return $this->area?->name;
    }

    /* -----------------------------------------------------------------
     | Core Relationships
     |-----------------------------------------------------------------*/

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }

    public function offers()
    {
        return $this->hasMany(ListingOffer::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class)
            ->orderByDesc('id')
            ->with('user');
    }

    public function views()
    {
        return $this->morphMany(ListingView::class, 'viewable');
    }

    public function reports()
    {
        return $this->hasMany(ListingReport::class);
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'watchlists');
    }

    public function attributes()
    {
        return $this->hasMany(ListingAttribute::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(UserFeedback::class, 'listing_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /* -----------------------------------------------------------------
     | Auction / Selling Logic
     |-----------------------------------------------------------------*/

    public function winningBid()
    {
        return $this->hasOne(Bid::class)
            ->orderByDesc('amount')
            ->with('user');
    }

    public function winningBidForUser($userId)
    {
        return $this->hasOne(Bid::class)
            ->where('user_id', $userId)
            ->orderByDesc('amount')
            ->with('user');
    }

    public function winningOffer()
    {
        return $this->hasOne(ListingOffer::class)->orderByDesc('amount');
    }

    public function buyNowPurchases()
    {
        return $this->hasMany(BuyNowPurchase::class);
    }

    /**
     * Returns buyer from latest buy-now purchase
     */
    public function getBuyerAttribute()
    {
        return $this->buyNowPurchases()
            ->with('buyer')
            ->latest()
            ->first()?->buyer;
    }

    /**
     * Auction winning bid (hidden until auction expires)
     */
    public function getAuctionWinningBidAttribute()
    {
        if ($this->expire_at && now()->lessThan($this->expire_at)) {
            return null;
        }

        return $this->winningBid()->first();
    }

    /**
     * Final buyer resolution after listing is sold
     */
    public function getFinalBuyerAttribute()
    {
        if (! $this->expire_at || now()->lessThan($this->expire_at)) {
            return null;
        }

        if ($this->status != 3) {
            return null;
        }

        if ($bid = $this->winningBid()->first()) {
            return [
                'type' => 'bid',
                'user' => $bid->user,
                'data' => $bid,
            ];
        }

        if ($buy = $this->buyNowPurchases()->with('buyer')->latest()->first()) {
            return [
                'type' => 'buy',
                'user' => $buy->buyer,
                'data' => $buy,
            ];
        }

        if ($offer = $this->winningOffer()
            ->where('status', 'approved')
            ->with('user')
            ->first()) {
            return [
                'type' => 'offer',
                'user' => $offer->user,
                'data' => $offer,
            ];
        }

        return null;
    }

    /* -----------------------------------------------------------------
     | Model Events
     |-----------------------------------------------------------------*/

    
}
