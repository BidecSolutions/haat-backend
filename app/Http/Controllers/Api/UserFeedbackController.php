<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserFeedback;

class UserFeedbackController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'reviewed_user_id' => 'required|exists:users,id|different:' . auth('api')->id(),
            'rating' => 'required|integer|min:1|max:5',
            'feedback_text' => 'nullable|string',
            'feedback_type' => 'required|string|in:buying,selling',
            'listing_id'    => 'nullable|exists:listings,id',
        ]);

        // Prevent duplicate feedback
        $existing = UserFeedback::where([
            'reviewer_id' => auth('api')->id(),
            'reviewed_user_id' => $request->reviewed_user_id,
            'listing_id' => $request->listing_id,
        ])->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'You have already submitted feedback for this user.'
            ], 409);
        }

        $feedback = UserFeedback::create([
            'reviewer_id' => auth('api')->id(),
            'reviewed_user_id' => $request->reviewed_user_id,
            'rating' => $request->rating,
            'feedback_text' => $request->feedback_text,
            'feedback_type' => $request->feedback_type,
            'listing_id' => $request->listing_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => $feedback
        ]);
    }

    /**
     * Show feedback stats + list for a user's profile
     */
    public function stats($userId)
    {
        $query = UserFeedback::with(['reviewer', 'listings'])
            ->where('reviewed_user_id', $userId);

        // Flip the type filter
        if (request()->filled('type')) {
            if (request()->type === 'buying') {
                // User wants to see feedback they received AS A BUYER (so reviewer was a seller)
                $query->where('feedback_type', 'selling');
            } elseif (request()->type === 'selling') {
                // User wants to see feedback they received AS A SELLER (so reviewer was a buyer)
                $query->where('feedback_type', 'buying');
            }
        }

        $feedbacks = $query->latest()->paginate(8);

        $total = $feedbacks->count();

        if ($total === 0) {
            return response()->json([
                'status' => true,
                'message' => 'No feedback found.',
                'data' => [
                    'total_feedback' => 0,
                    'average_rating' => 0,
                    'positive_percent' => 0,
                    'negative_percent' => 0,
                    'neutral_percent' => 0,
                    'feedbacks' => [],
                ]
            ]);
        }

        $averageRating = round($feedbacks->avg('rating'), 2);
        $positive = $feedbacks->whereIn('rating', [4, 5])->count();
        $neutral = $feedbacks->where('rating', 3)->count();
        $negative = $feedbacks->whereIn('rating', [1, 2])->count();

        return response()->json([
            'status' => true,
            'message' =>'Feedback stats fetched successfully.',
            'data' => [
                'total_feedback' => $total,
                'average_rating' => $averageRating,
                'positive_percent' => round(($positive / $total) * 100, 1),
                'negative_percent' => round(($negative / $total) * 100, 1),
                'neutral_percent' => round(($neutral / $total) * 100, 1),
                'feedbacks' => $feedbacks->map(function ($item) {
                    return [
                        'rating' => $item->rating,
                        'review' => $item->feedback_text,
                        // Flip the type for receiver’s perspective
                        'type' => $item->feedback_type === 'buying' ? 'selling' : 'buying',
                        'date' => $item->created_at->format('Y-m-d'),
                        'reviewer' => [
                            'id' => $item->reviewer->id,
                            'name' => $item->reviewer->name,
                            'username' => $item->reviewer->username,
                            'email' => $item->reviewer->email,
                            'profile_picture' => $item->reviewer->profile_photo,
                        ],
                        'listing' => $item->listings,
                    ];
                }),
            ]
        ]);
    }

     public function update(Request $request, $id)
    {
        $feedback = UserFeedback::find($id);
        // dd($request->all());
        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Feedback not found.'
            ], 404);
        }

        if ($feedback->reviewer_id !== auth('api')->id()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to update this feedback.'
            ], 403);
        }

        $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'feedback_text' => 'sometimes|nullable|string',
        ]);

        if ($request->has('rating')) {
            $feedback->rating = $request->rating;
        }
        if ($request->has('feedback_text')) {
            $feedback->feedback_text = $request->feedback_text;
        }

        $feedback->save();

        return response()->json([
            'status' => true,
            'message' => 'Feedback updated successfully.',
            'data' => $feedback
        ]);
    }


}
