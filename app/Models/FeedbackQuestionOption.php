<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackQuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'option_label',
        'option_value',
        'option_label_ar',
        'emoji',
        'order',
    ];

    public function question()
    {
        return $this->belongsTo(FeedbackQuestion::class, 'question_id');
    }
}
