<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'reviewed_user_id',
        'rating',
        'feedback_text',
        'feedback_type',
        'listing_id',
    ];

    public function listings()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
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

