<?php

namespace App\Services;

use App\Models\User;
use App\Models\Listing;
use App\Models\Bid;
use App\Models\ListingOffer;
use App\Notifications\BidPlacedNotification;
use App\Notifications\OutbidNotification;
use App\Notifications\AuctionWonNotification;
use App\Notifications\AuctionSoldNotification;
use App\Notifications\OfferApprovedNotification;
use App\Notifications\OfferExpiredNotification;
use App\Notifications\ListingBoughtNotification;
use App\Notifications\ManualBidApprovalRequiredNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send bid placed notification
     */
    public function sendBidPlacedNotification(Bid $bid): void
    {
        try {
            $listing = $bid->listing;
            $listing->creator->notify(new BidPlacedNotification($bid));
            
            Log::info('Bid placed notification sent', [
                'bid_id' => $bid->id,
                'listing_id' => $listing->id,
                'recipient' => $listing->creator->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send bid placed notification', [
                'bid_id' => $bid->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send outbid notification
     */
    public function sendOutbidNotification(Bid $bid): void
    {
        try {
            $listing = $bid->listing;
            $previousHighestBid = $listing->bids()
                ->where('id', '!=', $bid->id)
                ->orderByDesc('amount')
                ->first();

            if ($previousHighestBid && $previousHighestBid->user_id !== $bid->user_id) {
                $previousHighestBid->user->notify(new OutbidNotification($bid));
                
                Log::info('Outbid notification sent', [
                    'bid_id' => $bid->id,
                    'listing_id' => $listing->id,
                    'recipient' => $previousHighestBid->user->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send outbid notification', [
                'bid_id' => $bid->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send auction won notification
     */
    public function sendAuctionWonNotification(Listing $listing): void
    {
        try {
            $highestBid = $listing->bids()->orderByDesc('amount')->first();
            
            if ($highestBid) {
                $highestBid->user->notify(new AuctionWonNotification($listing));
                $listing->creator->notify(new AuctionSoldNotification($listing));
                
                Log::info('Auction won/sold notifications sent', [
                    'listing_id' => $listing->id,
                    'winner_email' => $highestBid->user->email,
                    'seller_email' => $listing->creator->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send auction won notification', [
                'listing_id' => $listing->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send offer approved notification
     */
    public function sendOfferApprovedNotification(ListingOffer $offer): void
    {
        try {
            $offer->user->notify(new OfferApprovedNotification($offer));
            
            Log::info('Offer approved notification sent', [
                'offer_id' => $offer->id,
                'listing_id' => $offer->listing_id,
                'recipient' => $offer->user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send offer approved notification', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send offer expired notification
     */
    public function sendOfferExpiredNotification(ListingOffer $offer): void
    {
        try {
            $offer->user->notify(new OfferExpiredNotification($offer));
            
            Log::info('Offer expired notification sent', [
                'offer_id' => $offer->id,
                'listing_id' => $offer->listing_id,
                'recipient' => $offer->user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send offer expired notification', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send listing bought notification
     */
    public function sendListingBoughtNotification(Listing $listing, User $buyer): void
    {
        try {
            $listing->creator->notify(new ListingBoughtNotification($listing, 'seller'));
            $buyer->notify(new ListingBoughtNotification($listing, 'buyer'));
            
            Log::info('Listing bought notifications sent', [
                'listing_id' => $listing->id,
                'buyer_email' => $buyer->email,
                'seller_email' => $listing->creator->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send listing bought notification', [
                'listing_id' => $listing->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send manual bid approval required notification
     */
    public function sendManualBidApprovalRequiredNotification(Listing $listing): void
    {
        try {
            $listing->creator->notify(new ManualBidApprovalRequiredNotification($listing));
            
            Log::info('Manual bid approval notification sent', [
                'listing_id' => $listing->id,
                'recipient' => $listing->creator->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send manual bid approval notification', [
                'listing_id' => $listing->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::send('emails.welcome', ['user' => $user], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                        ->subject('Welcome to ' . config('app.name') . '!');
            });
            
            Log::info('Welcome email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send listing ending soon notification
     */
    public function sendListingEndingSoonNotification(Listing $listing): void
    {
        try {
            $watchers = $listing->watchlist()->with('user')->get();
            
            foreach ($watchers as $watch) {
                $watch->user->notify(new \App\Notifications\ListingEndingSoonNotification($listing));
            }
            
            Log::info('Listing ending soon notifications sent', [
                'listing_id' => $listing->id,
                'watchers_count' => $watchers->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send listing ending soon notification', [
                'listing_id' => $listing->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration(string $testEmail): bool
    {
        try {
            Mail::raw('This is a test email from ' . config('app.name') . '. If you receive this, your email configuration is working correctly.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Email Configuration Test - ' . config('app.name'));
            });
            
            Log::info('Test email sent successfully', ['test_email' => $testEmail]);
            return true;
        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'test_email' => $testEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
