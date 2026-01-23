<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackResponse extends Model
{
    protected $fillable = [
        'user_id',
        'form_id',
        'guest_identifier',
    ];

    public function form()
    {
        return $this->belongsTo(FeedbackForm::class, 'form_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // nullable
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'response_id');
    }
}
