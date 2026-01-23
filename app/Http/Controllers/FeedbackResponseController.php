<?php

namespace App\Http\Controllers;

use App\Mail\FeedbackSubmittedMail;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FeedbackResponseController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_id' => 'required|exists:feedback_forms,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:feedback_questions,id',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.option_id' => 'nullable|exists:feedback_question_options,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $form = FeedbackForm::with('questions.options')->find($request->form_id);

        if (! $form || ! $form->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'This feedback form is no longer active.',
            ], 400);
        }

        $answers = collect($request->answers)->keyBy('question_id');
        $questionMap = $form->questions->keyBy('id');

        foreach ($form->questions as $question) {
            $input = $answers[$question->id] ?? null;

            // -------------------------
            // CONDITIONAL LOGIC SUPPORT
            // -------------------------
            if ($question->is_conditional) {
                $parentQuestion = $questionMap[$question->parent_question_id];
                $parentAnswer = $answers[$parentQuestion->id] ?? null;

                // If parent not answered OR selected option doesn't match → skip this question
                if (
                    ! $parentAnswer ||
                    ($parentAnswer['option_id'] ?? null) != $question->parent_option_id
                ) {
                    continue;
                }
            }

            // If question is required but missing
            if ($question->is_required && ! $input) {
                return response()->json([
                    'status' => false,
                    'message' => "Missing required answer for: {$question->question_text}",
                ], 422);
            }

            if ($input) {
                // TEXT validation
                if ($question->type === 'text') {
                    if ($question->is_required && empty($input['answer_text'])) {
                        return response()->json([
                            'status' => false,
                            'message' => "Text answer required for: {$question->question_text}",
                        ], 422);
                    }
                }

                // OPTION validation
                if ($question->type !== 'text') {
                    $selectedOption = $input['option_id'] ?? null;

                    if ($question->is_required && ! $selectedOption) {
                        return response()->json([
                            'status' => false,
                            'message' => "You must select an option for: {$question->question_text}",
                        ], 422);
                    }

                    if ($selectedOption) {
                        $validOption = $question->options->where('id', $selectedOption)->first();
                        if (! $validOption) {
                            return response()->json([
                                'status' => false,
                                'message' => "Invalid option selected for question: {$question->question_text}",
                            ], 422);
                        }
                    }
                }
            }
        }

        // Store main response
        $response = FeedbackResponse::create([
            'form_id' => $form->id,
            'user_id' => auth('api')->id(),
        ]);

        // Store answers
        foreach ($form->questions as $question) {
            $input = $answers[$question->id] ?? null;

            // Skip conditional questions not triggered
            if ($question->is_conditional) {
                $parentAnswer = $answers[$question->parent_question_id] ?? null;

                if (
                    ! $parentAnswer ||
                    ($parentAnswer['option_id'] ?? null) != $question->parent_option_id
                ) {
                    continue;
                }
            }

            if ($input) {
                FeedbackAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                    'option_id' => $input['option_id'] ?? null,
                    'answer_text' => $input['answer_text'] ?? null,
                ]);
            }
        }
        $response = FeedbackResponse::with(['answers.question', 'answers.option', 'user'])
            ->find($response->id);
        Mail::send(new FeedbackSubmittedMail($response));

        return response()->json([
            'status' => true,
            'message' => 'Thank you! Your feedback has been submitted.',
            'response_id' => $response->id,
        ]);
    }

    public function index(Request $request)
    {
        try {
            // Fetch all submitted feedback responses with detailed relations
            $responses = FeedbackResponse::with([
                'form:id,title,description',
                'user:id,name,email',
                'answers' => function ($q) {
                    $q->select('id', 'response_id', 'question_id', 'option_id', 'answer_text');
                },
                'answers.question:id,question_text,type',
                'answers.option:id,option_value,option_label,emoji,order',
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'All feedback responses retrieved successfully.',
                'data' => $responses,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch feedback responses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index2(Request $request)
    {
        try {
            // Fetch all responses with full relations
            $responses = FeedbackResponse::with([
                'form:id,title,title_ar,description,description_ar',
                'user:id,name,email',
                'answers' => function ($q) {
                    $q->select('id', 'response_id', 'question_id', 'option_id', 'answer_text');
                },
                'answers.question:id,question_text,question_text_ar,type',
                'answers.option:id,option_value,option_label,option_label_ar,emoji,order',
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Build English & Arabic arrays
            $englishData = [];
            $arabicData = [];

            foreach ($responses as $response) {

                // ---------------- ENGLISH VERSION ----------------
                $englishData[] = [
                    'id' => $response->id,
                    'user' => $response->user,
                    'form' => [
                        'id' => $response->form->id,
                        'title' => $response->form->title,
                        'description' => $response->form->description,
                    ],
                    'answers' => $response->answers->map(function ($ans) {
                        return [
                            'id' => $ans->id,
                            'question_id' => $ans->question_id,
                            'question_text' => $ans->question->question_text,
                            'type' => $ans->question->type,
                            'option' => $ans->option ? [
                                'value' => $ans->option->option_value,
                                'label' => $ans->option->option_label,
                                'emoji' => $ans->option->emoji,
                            ] : null,
                            'answer_text' => $ans->answer_text,
                        ];
                    }),
                    'created_at' => $response->created_at,
                ];

                // ---------------- ARABIC VERSION ----------------
                $arabicData[] = [
                    'id' => $response->id,
                    'user' => $response->user,
                    'form' => [
                        'id' => $response->form->id,
                        'title' => $response->form->title_ar,
                        'description' => $response->form->description_ar,
                    ],
                    'answers' => $response->answers->map(function ($ans) {
                        return [
                            'id' => $ans->id,
                            'question_id' => $ans->question_id,
                            'question_text' => $ans->question->question_text_ar,
                            'type' => $ans->question->type,
                            'option' => $ans->option ? [
                                'value' => $ans->option->option_value,
                                'label' => $ans->option->option_label_ar,
                                'emoji' => $ans->option->emoji,
                            ] : null,
                            'answer_text' => $ans->answer_text,
                        ];
                    }),
                    'created_at' => $response->created_at,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'All feedback responses retrieved successfully.',
                'data_en' => $englishData,
                'data_ar' => $arabicData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch feedback responses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
