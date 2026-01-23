<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Listing;
use App\Models\Bid;
use App\Models\ListingOffer;

class TestEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-templates {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all email templates to ensure they are working correctly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address');
            return 1;
        }

        $this->info('Testing email templates...');
        
        // Create a test user
        $user = new User();
        $user->name = 'Test User';
        $user->email = $email;
        
        // Create a test listing
        $listing = new Listing();
        $listing->title = 'Test Listing for Email Template Testing';
        $listing->slug = 'test-listing';
        $listing->buy_now_price = 100.00;
        $listing->end_date = now()->addDays(7);
        
        // Create a test bid
        $bid = new Bid();
        $bid->amount = 50.00;
        $bid->user = $user;
        $bid->listing = $listing;
        
        // Create a test offer
        $offer = new ListingOffer();
        $offer->amount = 75.00;
        $offer->listing = $listing;
        
        try {
            // Test BidPlacedNotification
            $this->info('Testing BidPlacedNotification...');
            $user->notify(new \App\Notifications\BidPlacedNotification($bid));
            
            // Test AuctionWonNotification
            $this->info('Testing AuctionWonNotification...');
            $user->notify(new \App\Notifications\AuctionWonNotification($listing));
            
            // Test OutbidNotification
            $this->info('Testing OutbidNotification...');
            $user->notify(new \App\Notifications\OutbidNotification($bid));
            
            // Test OfferApprovedNotification
            $this->info('Testing OfferApprovedNotification...');
            $user->notify(new \App\Notifications\OfferApprovedNotification($offer));
            
            // Test OfferExpiredNotification
            $this->info('Testing OfferExpiredNotification...');
            $user->notify(new \App\Notifications\OfferExpiredNotification($offer));
            
            // Test ListingBoughtNotification (buyer)
            $this->info('Testing ListingBoughtNotification (buyer)...');
            $user->notify(new \App\Notifications\ListingBoughtNotification($listing, 'buyer'));
            
            // Test ListingBoughtNotification (seller)
            $this->info('Testing ListingBoughtNotification (seller)...');
            $user->notify(new \App\Notifications\ListingBoughtNotification($listing, 'seller'));
            
            // Test AuctionSoldNotification
            $this->info('Testing AuctionSoldNotification...');
            $user->notify(new \App\Notifications\AuctionSoldNotification($listing));
            
            // Test ManualBidApprovalRequiredNotification
            $this->info('Testing ManualBidApprovalRequiredNotification...');
            $user->notify(new \App\Notifications\ManualBidApprovalRequiredNotification($listing));
            
            // Test ListingEndingSoonNotification
            $this->info('Testing ListingEndingSoonNotification...');
            $user->notify(new \App\Notifications\ListingEndingSoonNotification($listing));
            
            $this->info('All email templates tested successfully!');
            $this->info("Check your email at: {$email}");
            
        } catch (\Exception $e) {
            $this->error('Error testing email templates: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

