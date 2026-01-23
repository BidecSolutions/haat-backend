<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Listing;
use App\Models\ListingOffer;
use App\Notifications\AuctionSoldNotification;
use App\Notifications\AuctionWonNotification;
use App\Notifications\BidPlacedNotification;
use App\Notifications\OutbidNotification;
use App\Services\AutoBidService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BidController extends Controller
{
    const LAST_MINUTE_WINDOW = 1; // minutes before expiry

    const EXTENSION_TIME = 2;     // extend expiry by 2 minutes

    public function index($listingId)
    {
        try {
            $bids = Bid::where('listing_id', $listingId)
                ->with('user:id,name,email')
                ->latest()
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Bid history fetched',
                'data' => $bids,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching bids',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    // public function store(Request $request, $listingId)
    // {
    //     try {
    //         $listing = Listing::find($listingId);

    //         if (!$listing || $listing->status != 1) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Listing not found or inactive',
    //                 'data' => null
    //             ], 404);
    //         }

    //         if ($listing->expire_at && now()->greaterThanOrEqualTo($listing->expire_at)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Auction has expired',
    //                 'data' => null
    //             ], 403);
    //         }

    //         $validator = Validator::make($request->all(), [
    //             'amount' => 'required|numeric|min:0',
    //             'type' => 'required|in:manual,auto',
    //             'max_auto_bid_amount' => 'nullable|required_if:type,auto|numeric|min:0',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Validation failed',
    //                 'data' => $validator->errors()
    //             ], 422);
    //         }

    //         return DB::transaction(function () use ($request, $listing) {
    //             $userId = auth('api')->id();
    //             $amount = $request->amount;

    //             $highest = Bid::where('listing_id', $listing->id)
    //                 ->orderByDesc('amount')
    //                 ->first();

    //             // 🔁 Prevent self-overbidding
    //             if ($highest && $highest->user_id == $userId) {
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => 'You are already the highest bidder.',
    //                     'data' => null
    //                 ], 422);
    //             }

    //             // 🔒 Must beat highest bid
    //             if ($highest && $amount <= $highest->amount) {
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => 'Bid must be higher than current highest',
    //                     'data' => ['current_highest' => $highest->amount]
    //                 ], 422);
    //             }

    //             // 💾 Place bid
    //             $bid = Bid::create([
    //                 'listing_id' => $listing->id,
    //                 'user_id' => $userId,
    //                 'amount' => $amount,
    //                 'type' => $request->type,
    //                 'max_auto_bid_amount' => $request->type === 'auto' ? $request->max_auto_bid_amount : null,
    //             ]);

    //             // 🕒 Anti-sniping: Extend auction if bid in last 1 min
    //             if ($listing->expire_at && now()->diffInMinutes($listing->expire_at, false) <= self::LAST_MINUTE_WINDOW) {
    //                 $listing->expire_at = $listing->expire_at->copy()->addMinutes(self::EXTENSION_TIME);
    //                 $listing->save();
    //             }

    //             // 🤖 Auto-bid logic
    //             $autoBidService = new AutoBidService();
    //             $autoBidService->processAutoBids($listing);

    //             // Notify listing creator
    //             if ($listing->creator && $listing->creator->id !== $userId) {
    //                 $listing->creator->notify(new BidPlacedNotification($bid));
    //             }

    //             // Notify previous highest bidder
    //             if ($highest && $highest->user_id !== $userId) {
    //                 $highest->user->notify(new OutbidNotification($bid));
    //             }

    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'Bid placed successfully',
    //                 'data' => $bid
    //             ]);
    //         });

    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error placing bid',
    //             'data' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function store(Request $request, $listingId)
    {
        try {
            $listing = Listing::find($listingId);

            if (! $listing || $listing->status != 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found or inactive',
                    'data' => null,
                ], 404);
            }
            if ($listing->created_by == auth('api')->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot bid on your own listing',
                    'data' => null,
                ], 403);
            }
            if ($listing->start_price <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bidding not allowed on free listings',
                    'data' => null,
                ], 403);
            }

            // if ($listing->expire_at && now()->greaterThanOrEqualTo($listing->expire_at)) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Auction has expired',
            //         'data' => null
            //     ], 403);
            // }

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
                'type' => 'required|in:manual,auto',
                'max_auto_bid_amount' => 'nullable|required_if:type,auto|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            return DB::transaction(function () use ($request, $listing) {
                $userId = auth('api')->id();
                $amount = $request->amount;

                $highest = Bid::where('listing_id', $listing->id)
                    ->orderByDesc('amount')
                    ->first();

                // 🔁 Prevent self-overbidding
                if ($highest && $highest->user_id == $userId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You are already the highest bidder.',
                        'data' => null,
                    ], 422);
                }

                // 🔒 Must beat current highest bid if one exists
                if ($highest && $amount <= $highest->amount) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Bid must be higher than current highest',
                        'data' => ['current_highest' => $highest->amount],
                    ], 422);
                }

                // ✅ Must beat start_price if it's the first bid
                if (! $highest && $amount <= $listing->start_price) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Bid must be higher than the starting price',
                        'data' => ['start_price' => $listing->start_price],
                    ], 422);
                }

                $listingOffers = ListingOffer::where('listing_id', $listing->id)->get();
                if ($listingOffers->count() > 0) {
                    foreach ($listingOffers as $listingOffer) {
                        $listingOffer->status = 'rejected';
                        $listingOffer->save();
                    }
                }

                // 💾 Place bid
                $bid = Bid::create([
                    'listing_id' => $listing->id,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'type' => $request->type,
                    'max_auto_bid_amount' => $request->type === 'auto' ? $request->max_auto_bid_amount : null,
                ]);

                // 🕒 Anti-sniping: Extend auction if bid in last 1 min
                if ($listing->expire_at && now()->diffInMinutes($listing->expire_at, false) <= self::LAST_MINUTE_WINDOW) {
                    $listing->expire_at = $listing->expire_at->copy()->addMinutes(self::EXTENSION_TIME);
                    $listing->save();
                }

                // 🤖 Auto-bid logic
                $autoBidService = new AutoBidService;
                $autoBidService->processAutoBids($listing);

                // // 📬 Notify listing creator
                // if ($listing->creator && $listing->creator->id !== $userId) {
                //     $listing->creator->notify(new BidPlacedNotification($bid));
                // }

                // // 📬 Notify previous highest bidder
                // if ($highest && $highest->user_id !== $userId) {
                //     $highest->user->notify(new OutbidNotification($bid));
                // }

                return response()->json([
                    'status' => true,
                    'message' => 'Bid placed successfully',
                    'data' => $bid,
                ]);
            });

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error placing bid',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function acceptBid($listingID)
    {
        $listing = Listing::with('bids')->find($listingID);
        if(!$listing){
            return response()->json([
                'status' => true,
                'message' => "listing not found",
                'data' => []
            ], 404);
        }

        if ($listing->created_by != auth('api')->id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $highestBid = $listing->bids()->orderByDesc('amount')->first();

        if (! $highestBid) {
            return response()->json(['status' => false, 'message' => 'No bids to accept'], 404);
        }

        $listing->update([
            'status' => 3,
            'sold_at' => now(),
        ]);

        $highestBid->user->notify(new AuctionWonNotification($listing));
        $listing->creator->notify(new AuctionSoldNotification($listing));

        return response()->json(['status' => true, 'message' => 'Bid accepted. Listing marked as sold']);
    }

    public function rejectBid($listingID)
    {
        $listing = Listing::findOrFail($listingID);

        if ($listing->created_by != auth('api')->id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $listing->update(['status' => 4]); // Expired

        return response()->json(['status' => true, 'message' => 'Bid rejected. Listing expired']);
    }
}
