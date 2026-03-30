<?php

namespace App\Http\Controllers;

use App\Models\FeedbackForm;

class FeedbackFormController extends Controller
{
    /** Bangla translations for feedback form (fallback when DB has no title_bn, etc.) */
    private const BN_TRANSLATIONS = [
        'form' => [
            'boli bazar — Quick Feedback Form' => 'হাত — দ্রুত মতামত ফর্ম',
            'Haat Feedback Form' => 'হাত মতামত ফর্ম',
            'Collecting user feedback to improve boli bazar.' => 'হাত প্ল্যাটফর্ম উন্নত করতে ব্যবহারকারীর মতামত সংগ্রহ করা হচ্ছে।',
            'Collecting user feedback to improve Haat.' => 'হাত প্ল্যাটফর্ম উন্নত করতে ব্যবহারকারীর মতামত সংগ্রহ করা হচ্ছে।',
        ],
        'questions' => [
            'How was your experience on the boli website?' => 'হাত ওয়েবসাইটে আপনার অভিজ্ঞতা কেমন ছিল?',
            'Was it easy to browse and find items?' => 'আইটেম ব্রাউজ এবং খুঁজে পেতে কি সহজ ছিল?',
            'Did you face any issues or bugs?' => 'আপনি কি কোনো সমস্যা বা বাগের সম্মুখীন হয়েছেন?',
            'Please describe the issue you faced.' => 'আপনার সম্মুখীন সমস্যার বর্ণনা দিন।',
            'What is one thing we should improve first?' => 'আমাদের প্রথমে কী একটি জিনিস উন্নতি করা উচিত?',
            'Which feature would you like us to add next?' => 'পরবর্তীতে কোন বৈশিষ্ট্য যোগ করতে চান?',
            'Would you use boli when we fully launch?' => 'পুরোপুরি চালু হলে আপনি কি হাত ব্যবহার করবেন?',
            'Any other comments?' => 'অন্যান্য মন্তব্য?',
        ],
        'options' => [
            'Good' => 'ভালো',
            'Okay' => 'ঠিক আছে',
            'Needs Improvement' => 'উন্নতির প্রয়োজন',
            'Yes' => 'হ্যাঁ',
            'Somewhat' => 'কিছুটা',
            'No' => 'না',
            'Yes (please mention)' => 'হ্যাঁ (দয়া করে উল্লেখ করুন)',
            'Maybe' => 'হয়তো',
        ],
    ];

    private function translateToBangla(string $type, string $en): string
    {
        $map = self::BN_TRANSLATIONS[$type] ?? [];
        return $map[$en] ?? $en;
    }

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
            'title' => $form->title_ar ?? $form->title,
            'description' => $form->description_ar ?? $form->description,
            'questions' => $form->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'text' => $q->question_text_ar ?? $q->question_text,
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
                                'label' => $opt->option_label_ar ?? $opt->option_label,
                                'value' => $opt->option_value,
                                'emoji' => $opt->emoji,
                            ];
                        })
                        : [],
                ];
            }),
        ];

        // ==============================
        // BUILD BANGLA RESPONSE OBJECT
        // ==============================
        $formBN = [
            'form_id' => $form->id,
            'title' => $form->title_bn ?? $this->translateToBangla('form', $form->title),
            'description' => $form->description_bn ?? $this->translateToBangla('form', $form->description ?? ''),
            'questions' => $form->questions->map(function ($q) {
                $qText = $q->question_text_bn ?? $this->translateToBangla('questions', $q->question_text);
                return [
                    'id' => $q->id,
                    'text' => $qText,
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
                                'label' => $opt->option_label_bn ?? $this->translateToBangla('options', $opt->option_label),
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
            'form_bn' => $formBN,
        ]);
    }
}
