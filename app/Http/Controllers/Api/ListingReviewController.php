<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ListingReview;
use App\Models\Listing;

class ListingReviewController extends Controller
{
    /**
     * Submit a review for a listing.
     */
   public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'reviewed_user_id' => 'required|exists:users,id|different:auth_user',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ], [
            'listing_id.required' => 'Listing is required.',
            'listing_id.exists' => 'The selected listing does not exist.',
            'reviewed_user_id.required' => 'The user being reviewed is required.',
            'reviewed_user_id.exists' => 'The selected user does not exist.',
            'reviewed_user_id.different' => 'You cannot review yourself.',
            'rating.required' => 'Rating is required.',
            'rating.integer' => 'Rating must be an integer.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot be more than 5 stars.',
        ]);

        $reviewerId = auth()->id();
        $listingId = $validated['listing_id'];
        $reviewedUserId = $validated['reviewed_user_id'];

        // Check for existing review
        $existing = ListingReview::where([
            'listing_id' => $listingId,
            'reviewer_id' => $reviewerId,
            'reviewed_user_id' => $reviewedUserId,
        ])->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'You have already reviewed this listing.',
            ], 409);
        }

        $review = ListingReview::create([
            'listing_id' => $listingId,
            'reviewer_id' => $reviewerId,
            'reviewed_user_id' => $reviewedUserId,
            'rating' => $validated['rating'],
            'review_text' => $validated['review_text'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong while submitting the review.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Get all reviews for a specific listing.
     */
public function index()
{
    try {
        $userId = auth()->id(); // Logged-in user

        $allReviews = ListingReview::with(['reviewer:id,name,profile_photo'])
            ->where('reviewed_user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        // 🧾 Map reviews
        $reviews = $allReviews->map(function ($review) {
            return [
                'rating' => $review->rating,
                'review' => $review->review_text,
                'date' => $review->created_at->toDateString(),
                'reviewer' => [
                    'id' => $review->reviewer->id,
                    'name' => $review->reviewer->name,
                    'profile_photo' => $review->reviewer->profile_photo
                        ? asset('storage/' . $review->reviewer->profile_photo)
                        : null,
                ],
            ];
        });

        // 📊 Stats
        $total = $allReviews->count();
        $average = $total > 0 ? round($allReviews->avg('rating'), 1) : 0;
        $positive = $allReviews->where('rating', '>=', 4)->count();
        $negative = $allReviews->where('rating', '<', 4)->count();
        $positivePercent = $total > 0 ? round(($positive / $total) * 100, 1) : 0;
        $negativePercent = $total > 0 ? round(($negative / $total) * 100, 1) : 0;

        return response()->json([
            'status' => true,
            'data' => [
                'total_reviews' => $total,
                'average_rating' => $average,
                'positive_percent' => $positivePercent,
                'negative_percent' => $negativePercent,
                'reviews' => $reviews,
            ]
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch reviews.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Show stats for a listing's reviews.
     */
    public function showStats($listing_id)
    {
        $reviews = ListingReview::where('listing_id', $listing_id)->get();

        $total = $reviews->count();
        $average = $total > 0 ? round($reviews->avg('rating'), 1) : 0;

        $positive = $reviews->where('rating', '>=', 4)->count();
        $negative = $reviews->where('rating', '<', 4)->count();

        $positivePercent = $total > 0 ? round(($positive / $total) * 100, 1) : 0;
        $negativePercent = $total > 0 ? round(($negative / $total) * 100, 1) : 0;

        return response()->json([
            'status' => true,
            'data' => [
                'total_reviews' => $total,
                'average_rating' => $average,
                'positive_percent' => $positivePercent,
                'negative_percent' => $negativePercent
            ]
        ]);
    }
}
