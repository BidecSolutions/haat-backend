<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'api';

    protected $appends = [
        'country_name',
        'region_name',
        'governorate_name',
        'city_name',
        'area_name',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'user_code',
        'first_name',
        'memberId',
        'is_verified',
        'verification_code',
        'verification_expires_at',
        'reset_p_code',
        'reset_p_code_expire_at',
        'last_name',
        'username',
        'gender',
        'email',
        'phone',
        'occupation',
        'about_me',
        'favourite_quote',
        'date_of_birth',
        'landline',
        'account_type',
        'business_name',
        'country',
        'country_id',
        'regions_id',
        'governorates_id',
        'city_id',
        'area_id',
        'address_finder',
        'address_1',
        'address_2',
        'suburb',
        'post_code',
        'closest_district',
        'billing_address',
        'street_address',
        'apartment',
        'state',
        'zip_code',
        'password',
        'profile_photo',
        'background_photo',
        'status',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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

    public function getCountryNameAttribute()
    {
        return $this->countries ? $this->countries->name : null;
    }

    public function getRegionNameAttribute()
    {
        return $this->regions ? $this->regions->name : null;
    }

    public function getGovernorateNameAttribute()
    {
        return $this->governorates ? $this->governorates->name : null;
    }

    public function getCityNameAttribute()
    {
        return $this->cities ? $this->cities->name : null;
    }
    public function getAreaNameAttribute()
    {
        return $this->area ? $this->area->name : null;
    }

    public function watchlist()
    {
        return $this->hasMany(Watchlist::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'created_by');
    }

    public function buyer_id()
    {
        return $this->hasMany(Appointment::class, 'buyer_id');
    }
    public function buyNow(){
        return $this->hasMany(BuyNowPurchase::class, 'buyer_id');
    }
    

    public function seller_id()
    {
        return $this->hasMany(Appointment::class, 'seller_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'user_id');
    }

    public function offers()
    {
        return $this->hasMany(ListingOffer::class, 'user_id');
    }

    public function reports()
    {
        return $this->hasMany(ListingReport::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(UserFeedback::class, 'reviewed_user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function listingViews()
    {
        return $this->hasMany(ListingView::class);
    }

    public function deliveryAddresses()
    {
        return $this->hasMany(\App\Models\DeliveryAddress::class);
    }

    public function favoriteCategories()
    {
        return $this->hasMany(FavoriteCategory::class);
    }

    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function favoriteSellers()
    {
        return $this->hasMany(FavoriteSeller::class);
    }

    public function getProfilePictureUrlAttribute()
    {
        return $this->profile_picture
            ? asset('storage/profile_photo/'.$this->profile_photo)
            : asset('images/default-avatar.png');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function searches()
    {
        return $this->hasMany(SearchHistory::class);
    }

  
}
