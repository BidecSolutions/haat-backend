<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackQuestion extends Model
{
    protected $fillable = [
        'form_id',
        'question_text',
        'question_text_ar',
        'type',
        'is_required',
        'order',
    ];

    public function form()
    {
        return $this->belongsTo(FeedbackForm::class, 'form_id');
    }

    public function options()
    {
        return $this->hasMany(FeedbackQuestionOption::class, 'question_id');
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'question_id');
    }

    public function subQuestions()
    {
        return $this->hasMany(FeedbackQuestion::class, 'parent_question_id');
    }

    public function parentOption()
    {
        return $this->belongsTo(FeedbackQuestionOption::class, 'parent_option_id');
    }
}
