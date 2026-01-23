<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Listing;
use App\Models\ListingOffer;
use App\Notifications\OfferApprovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListingOfferController extends Controller
{
    public function index()
    {
        try {
            $userId = auth('api')->id();

            // 🛒 Offers made by the user (Buying)
            $buyingOffers = ListingOffer::with(['listing', 'listing.images'])
                ->where('user_id', $userId)
                ->latest()
                ->get();

            // 🛍️ Offers received on listings created by the user (Selling)
            $sellingOffers = ListingOffer::with(['user', 'listing', 'listing.images'])
                ->whereHas('listing', function ($query) use ($userId) {
                    $query->where('created_by', $userId);
                })
                ->latest()
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Offers fetched successfully',
                'buying_count' => $buyingOffers->count(),
                'selling_count' => $sellingOffers->count(),
                'buying_offers' => $buyingOffers,
                'selling_offers' => $sellingOffers,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching offers',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function listingWiseOffer($listingId)
    {
        try {
            $offers = ListingOffer::where('listing_id', $listingId)->with(['user', 'listing'])->latest()->get();

            return response()->json([
                'status' => true,
                'message' => 'Offers fetched successfully',
                'data' => $offers,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching offers',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, $listingId)
    {
        try {
            $listing = Listing::find($listingId);

            if (! $listing || ! $listing->allow_offers) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing does not accept offers',
                    'data' => null,
                ], 403);
            }

            // 🔐 Check if user has already made 7 offers for this listing
            $userId = auth('api')->id();
            $offerCount = ListingOffer::where('listing_id', $listingId)
                ->where('user_id', $userId)
                ->count();

            if ($request->amount > $listing->buy_now_price) {
                return response()->json([
                    'status' => false,
                    'message' => 'Offer amount must be less than the Buy Now price.',
                    'data' => null,
                ], 403);
            }

            if ($offerCount >= 7) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already submitted the maximum number of offers (7) for this listing.',
                    'data' => null,
                ], 403);
            }
            $biding = Bid::where('listing_id', $listingId)
                ->first();

            if ($biding) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bidding is active on this listing. Offers cannot be made.',
                    'data' => null,
                ], 403);
            }

            // ✅ Validate input
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'message' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            // 💾 Create offer
            $offer = ListingOffer::create([
                'listing_id' => $listingId,
                'user_id' => $userId,
                'amount' => $request->amount,
                'message' => $request->message,
                'status' => 'pending',
                'expires_at' => now()->addHours(24), // ⏰ expire after 24 hrs
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Offer submitted',
                'data' => $offer,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error submitting offer',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $offer = ListingOffer::find($id);

            if (! $offer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Offer not found',
                    'data' => null,
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:accepted,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $offer->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Offer status updated',
                'data' => $offer,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating offer',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $offer = ListingOffer::find($id);

            if (! $offer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Offer not found',
                    'data' => null,
                ], 404);
            }

            $offer->delete();

            return response()->json([
                'status' => true,
                'message' => 'Offer deleted',
                'data' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting offer',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function approveOffer($id)
    {
        $offer = ListingOffer::with('listing')->find($id);

        if (! $offer || $offer->listing->created_by !== auth('api')->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or offer not found',
                'data' => null,
            ], 404);
        }
        $allOffers = ListingOffer::with('listing')->where('user_id', $offer->user_id)->where('listing_id', $offer->listing->id)->orderByDesc('created_at')->first();
        if($offer->id != $allOffers->id){
            return response([
                'status' => true,
                'message' => "You cannot accept the previous offers",
                'data' => []
            ], 403);
        }

        if ($offer->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Offer already responded to',
                'data' => $offer,
            ]);
        }

        // 🔁 Update offer
        $offer->status = 'approved';
        $offer->responded_at = now();
        $offer->save();

        // ✅ Mark listing as SOLD
        $listing = $offer->listing;
        $listing->status = 3; // 3 = Sold
        $listing->sold_at = now();
        $listing->save();

        // 🔔 Notify the offer sender (buyer)
        $offer->user->notify(new OfferApprovedNotification($offer));

        return response()->json([
            'status' => true,
            'message' => 'Offer approved successfully',
            'data' => $offer,
        ]);
    }

    public function rejectOffer($id)
    {
        $offer = ListingOffer::with('listing')->find($id);

        if (! $offer || $offer->listing->created_by !== auth('api')->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or offer not found',
                'data' => null,
            ], 404);
        }

        if ($offer->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Offer already responded to',
                'data' => $offer,
            ]);
        }

        $offer->status = 'rejected';
        $offer->responded_at = now();
        $offer->save();

        return response()->json([
            'status' => true,
            'message' => 'Offer rejected',
            'data' => $offer,
        ]);
    }
}
