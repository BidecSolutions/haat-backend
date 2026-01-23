<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackAnswer extends Model
{
    protected $fillable = [
        'response_id',
        'question_id',
        'option_id',
        'answer_text',
    ];

    public function response()
    {
        return $this->belongsTo(FeedbackResponse::class, 'response_id');
    }

    public function question()
    {
        return $this->belongsTo(FeedbackQuestion::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(FeedbackQuestionOption::class, 'option_id');
    }
}
