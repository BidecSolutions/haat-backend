<?php

namespace App\Http\Controllers;

use App\Models\FeedbackForm;

class FeedbackFormController extends Controller
{
    //

    public function getActiveForm()
    {
        $form = FeedbackForm::with([
            'questions' => function ($q) {
                $q->orderBy('order');
            },
            'questions.options' => function ($q) {
                $q->orderBy('order');
            },
            'questions.subQuestions',
        ])
            ->where('is_active', true)
            ->first();

        if (! $form) {
            return response()->json([
                'status' => false,
                'message' => 'No active feedback form found.',
            ], 404);
        }

        // ==============================
        // BUILD ENGLISH RESPONSE OBJECT
        // ==============================
        $formEN = [
            'form_id' => $form->id,
            'title' => $form->title,
            'description' => $form->description,
            'questions' => $form->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'text' => $q->question_text,
                    'type' => $q->type,
                    'required' => $q->is_required,

                    'is_conditional' => (bool) $q->is_conditional,
                    'parent_question_id' => $q->parent_question_id,
                    'parent_option_id' => $q->parent_option_id,

                    'has_sub_questions' => $q->subQuestions->count() > 0,

                    'options' => $q->type !== 'text'
                        ? $q->options->map(function ($opt) {
                            return [
                                'id' => $opt->id,
                                'label' => $opt->option_label,
                                'value' => $opt->option_value,
                                'emoji' => $opt->emoji,
                            ];
                        })
                        : [],
                ];
            }),
        ];

        // ==============================
        // BUILD ARABIC RESPONSE OBJECT
        // ==============================
        $formAR = [
            'form_id' => $form->id,
            'title' => $form->title_ar,
            'description' => $form->description_ar,
            'questions' => $form->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'text' => $q->question_text_ar,
                    'type' => $q->type,
                    'required' => $q->is_required,

                    'is_conditional' => (bool) $q->is_conditional,
                    'parent_question_id' => $q->parent_question_id,
                    'parent_option_id' => $q->parent_option_id,

                    'has_sub_questions' => $q->subQuestions->count() > 0,

                    'options' => $q->type !== 'text'
                        ? $q->options->map(function ($opt) {
                            return [
                                'id' => $opt->id,
                                'label' => $opt->option_label_ar,
                                'value' => $opt->option_value,
                                'emoji' => $opt->emoji,
                            ];
                        })
                        : [],
                ];
            }),
        ];

        return response()->json([
            'status' => true,
            'form_en' => $formEN,
            'form_ar' => $formAR,
        ]);
    }
}
