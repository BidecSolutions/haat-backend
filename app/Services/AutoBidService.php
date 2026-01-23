<?php

namespace App\Services;

use App\Notifications\OutbidNotification;
use App\Models\Bid;
use App\Models\Listing;

class AutoBidService
{
    const BID_INCREMENT = 1.00;

    public function processAutoBids(Listing $listing)
    {
        $currentHighest = $listing->bids()->orderByDesc('amount')->first();

        // Get active auto-bidders except current highest
        $autoBidders = Bid::where('listing_id', $listing->id)
            ->where('type', 'auto')
            ->whereNotNull('max_auto_bid_amount')
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id');

        foreach ($autoBidders as $autoBid) {
            $userId = $autoBid->user_id;

            // Already highest? Skip
            if ($currentHighest && $currentHighest->user_id == $userId) {
                continue;
            }

            $nextBidAmount = $currentHighest ? $currentHighest->amount + self::BID_INCREMENT : $listing->start_price;

            // 💡 Don't exceed max auto bid
            if ($nextBidAmount > $autoBid->max_auto_bid_amount) {
                continue;
            }

            // Create new bid
            $newBid = Bid::create([
                'listing_id' => $listing->id,
                'user_id' => $userId,
                'amount' => $nextBidAmount,
                'type' => 'auto',
                'max_auto_bid_amount' => $autoBid->max_auto_bid_amount,
            ]);

            // 🔔 Notify previous highest bidder
            if ($currentHighest && $currentHighest->user_id !== $userId) {
                $currentHighest->user->notify(new OutbidNotification($newBid));
            }

            $currentHighest = $newBid;

            break; // only one auto-bid triggered per round
        }
    }
}

