<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ListingOffer;
use App\Notifications\OfferExpiredNotification;

class ExpireOffers extends Command
{
    protected $signature = 'offers:expire';
    protected $description = 'Mark expired offers after 24 hours';

    public function handle()
    {
        $expiredOffers = ListingOffer::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->with(['user', 'listing'])
            ->get();

        foreach ($expiredOffers as $offer) {
            $offer->update(['status' => 'expired']);

            // Notify the user
            if ($offer->user) {
                $offer->user->notify(new OfferExpiredNotification($offer));
            }
        }

        $this->info("✅ " . $expiredOffers->count() . " offers expired and users notified.");
    }
}
