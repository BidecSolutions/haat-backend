<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingReview extends Model
{
    protected $fillable = [
        'listing_id',
        'reviewer_id',
        'reviewed_user_id',
        'rating',
        'review_text',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_user_id');
    }
}

