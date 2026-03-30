<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackForm extends Model
{
    protected $fillable = [
        'title',
        'title_ar',
        'title_bn',
        'description',
        'description_ar',
        'description_bn',
        'is_active',
    ];

    public function questions()
    {
        return $this->hasMany(FeedbackQuestion::class, 'form_id');
    }

    public function responses()
    {
        return $this->hasMany(FeedbackResponse::class, 'form_id');
    }
}
