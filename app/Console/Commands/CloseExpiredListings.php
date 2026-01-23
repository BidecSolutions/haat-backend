<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Listing;
use App\Notifications\AuctionWonNotification;
use App\Notifications\AuctionSoldNotification;
use App\Notifications\ManualBidApprovalRequiredNotification;

class CloseExpiredListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:close-expired-listings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("⏳ Closing expired listings...");

        $expiredListings = Listing::where('status', 1) // approved
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->get();

        foreach ($expiredListings as $listing) {
            $highestBid = $listing->bids()->orderByDesc('amount')->first();

            if ($highestBid && $highestBid->amount >= $listing->reserve_price) {
                // auto sell
                $listing->update([
                    'status' => 3, // Sold
                    'sold_at' => now(),
                ]);

                $highestBid->user->notify(new AuctionWonNotification($listing));
                $listing->creator->notify(new AuctionSoldNotification($listing));

                $this->info("✅ SOLD: {$listing->title} to {$highestBid->user->name}");
            } elseif ($highestBid) {
                // Reserve not met — wait for seller action
                $listing->update(['status' => 5]); // 5 = Awaiting Approval
                $listing->creator->notify(new ManualBidApprovalRequiredNotification($listing));
            } else {
                // No bids
                $listing->update(['status' => 4]); // Expired
                $this->info("❌ EXPIRED: {$listing->title}");
            }
        }

        $this->info("✅ Done.");
    }


}
