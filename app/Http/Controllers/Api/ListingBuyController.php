<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\BuyNowPurchase;
use Illuminate\Support\Facades\DB;
use App\Notifications\ListingBoughtNotification;

class ListingBuyController extends Controller
{
    public function buyNow($slug)
    {
        try {
            $listing = Listing::where('slug', $slug)
                ->where('status', 1)
                ->where('is_active', 1)
                ->whereNotNull('buy_now_price')
                ->first();

            if (!$listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not available for buy now',
                    'data' => null
                ], 404);
            }
            if($listing->listing_type == 'property'){
                return response()->json([
                    'status' =>true,
                    'message' => "You cannot buy properties",
                    'data' => [],
                ], 403);
            }

            if ($listing->created_by === auth('api')->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot buy your own listing',
                    'data' => null
                ], 403);
            }

            return DB::transaction(function () use ($listing) {
                $listing->update([
                    'status' => 3,
                    'sold_at' => now(),
                    'is_active' => 0,
                ]);

                BuyNowPurchase::create([
                    'listing_id' => $listing->id,
                    'buyer_id' => auth('api')->id(),
                    'amount' => $listing->buy_now_price,
                    'purchased_at' => now()
                ]);

                $listing->creator->notify(new ListingBoughtNotification($listing, 'seller'));
                auth('api')->user()->notify(new ListingBoughtNotification($listing, 'buyer'));

                return response()->json([
                    'status' => true,
                    'message' => 'Listing purchased successfully',
                    'data' => $listing
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Purchase failed',
                'data' => $e->getMessage()
            ], 500);
        }
    }
}