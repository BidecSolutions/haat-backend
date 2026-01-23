<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\FeedbackResponse;
use App\Models\Listing;

class AuctionResultController extends Controller
{
    public function wonListings()
    {
        $userId = auth('api')->id();
        $listings = Listing::where('status', 3)
            ->with([
                'bids.user',
                'images',
                'buyNowPurchases.buyer',
                'winningOffer.user' => fn($q) =>$q->where('status', 'approved'),
                'feedbacks.reviewedUser',
                'category',
                'creator',
            ])
            ->orderByDesc('sold_at')
            ->get()
            ->filter(function ($listing) use ($userId) {
                $winner = $listing->finalWinner();

                return $winner && $winner['user_id'] == $userId;
            })
            ->values(); // reindex

        $feedback = false;
        if ($userId) {
            $feedbackCheck = FeedbackResponse::where('user_id', $userId)->get();
            if ($feedbackCheck) {
                $feedbackCheck = true;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Won listings fetched successfully',
            'feedback' => $feedback,
            'data' => $listings,
        ]);
    }

    public function lostListings()
    {
        $user = auth('api')->user();

        $bidListingIds = Bid::where('user_id', $user->id)
            ->pluck('listing_id')
            ->unique();

        $lostListings = Listing::whereIn('id', $bidListingIds)
            ->where('status', 3) // sold
            ->with(['winningBid', 'images', 'category', 'creator'])
            ->orderByDesc('sold_at')
            ->get()
            ->filter(fn ($listing) => $listing->winningBid && $listing->winningBid->user_id !== $user->id);
        $feedback = false;
        if ($user) {
            $feedbackCheck = FeedbackResponse::where('user_id', $user->id)->get();
            if ($feedbackCheck) {
                $feedbackCheck = true;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Lost listings fetched',
            'feedback' => $feedback,
            'data' => $lostListings->values(),
        ]);
    }
}
