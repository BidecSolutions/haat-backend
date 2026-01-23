<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Listing;
use App\Services\EmailNotificationService;

class TestEmailConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-config 
                            {email : The email address to test with}
                            {--user-id= : Specific user ID to test with (optional)}
                            {--all : Test all notification types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration and notification types';

    protected $emailService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailNotificationService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testEmail = $this->argument('email');
        $userId = $this->option('user-id');
        $testAll = $this->option('all');

        $this->info('=== Email Configuration Test ===');
        $this->newLine();

        // Test 1: Check email configuration
        $this->info('1. Checking email configuration...');
        $mailConfig = config('mail');
        $this->line("   - Default mailer: " . $mailConfig['default']);
        $this->line("   - From address: " . $mailConfig['from']['address']);
        $this->line("   - From name: " . $mailConfig['from']['name']);

        if ($mailConfig['default'] === 'smtp') {
            $this->line("   - SMTP Host: " . $mailConfig['mailers']['smtp']['host']);
            $this->line("   - SMTP Port: " . $mailConfig['mailers']['smtp']['port']);
            $this->line("   - SMTP Username: " . ($mailConfig['mailers']['smtp']['username'] ?: 'Not set'));
        }
        $this->newLine();

        // Test 2: Basic email test
        $this->info('2. Testing basic email functionality...');
        try {
            Mail::raw('This is a test email from the TradeMe backend system. If you receive this, your email configuration is working correctly.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Email Configuration Test - TradeMe Backend');
            });
            $this->info('   ✓ Basic email test passed');
        } catch (\Exception $e) {
            $this->error('   ✗ Basic email test failed: ' . $e->getMessage());
            return 1;
        }
        $this->newLine();

        if (!$testAll) {
            $this->info('✓ Email configuration is working!');
            $this->info('Use --all flag to test all notification types.');
            return 0;
        }

        // Test 3: Check if users exist
        $this->info('3. Checking for test users...');
        $user = $userId ? User::find($userId) : User::first();
        if ($user) {
            $this->info("   ✓ Found user: " . $user->email . " (ID: " . $user->id . ")");
        } else {
            $this->error('   ✗ No users found in database');
            $this->error('   Please create a user first to test notifications');
            return 1;
        }
        $this->newLine();

        // Test 4: Check if listings exist
        $this->info('4. Checking for test listings...');
        $listing = Listing::first();
        if ($listing) {
            $this->info("   ✓ Found listing: " . $listing->title . " (ID: " . $listing->id . ")");
        } else {
            $this->warn("   ⚠ No listings found - some notification tests will be skipped");
        }
        $this->newLine();

        // Test 5: Test all notification types
        $this->info('5. Testing all notification types...');

        $notificationTests = [
            'ResetPasswordNotification' => function() use ($user) {
                $token = \Illuminate\Support\Str::random(60);
                $user->notify(new \App\Notifications\ResetPasswordNotification($token));
                return true;
            },
            'BidPlacedNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $bid = new \App\Models\Bid([
                    'user_id' => $user->id,
                    'listing_id' => $listing->id,
                    'amount' => 100.00,
                ]);
                $bid->user = $user;
                $bid->listing = $listing;
                $user->notify(new \App\Notifications\BidPlacedNotification($bid));
                return true;
            },
            'OutbidNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $bid = new \App\Models\Bid([
                    'user_id' => $user->id,
                    'listing_id' => $listing->id,
                    'amount' => 150.00,
                ]);
                $bid->user = $user;
                $bid->listing = $listing;
                $user->notify(new \App\Notifications\OutbidNotification($bid));
                return true;
            },
            'AuctionWonNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $user->notify(new \App\Notifications\AuctionWonNotification($listing));
                return true;
            },
            'AuctionSoldNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $user->notify(new \App\Notifications\AuctionSoldNotification($listing));
                return true;
            },
            'OfferApprovedNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $offer = new \App\Models\ListingOffer([
                    'user_id' => $user->id,
                    'listing_id' => $listing->id,
                    'amount' => 200.00,
                    'status' => 'approved',
                ]);
                $offer->user = $user;
                $offer->listing = $listing;
                $user->notify(new \App\Notifications\OfferApprovedNotification($offer));
                return true;
            },
            'OfferExpiredNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $offer = new \App\Models\ListingOffer([
                    'user_id' => $user->id,
                    'listing_id' => $listing->id,
                    'amount' => 200.00,
                    'status' => 'expired',
                ]);
                $offer->user = $user;
                $offer->listing = $listing;
                $user->notify(new \App\Notifications\OfferExpiredNotification($offer));
                return true;
            },
            'ListingBoughtNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $user->notify(new \App\Notifications\ListingBoughtNotification($listing, 'buyer'));
                return true;
            },
            'ManualBidApprovalRequiredNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $user->notify(new \App\Notifications\ManualBidApprovalRequiredNotification($listing));
                return true;
            },
            'ListingEndingSoonNotification' => function() use ($user, $listing) {
                if (!$listing) return 'skipped_no_listing';
                $user->notify(new \App\Notifications\ListingEndingSoonNotification($listing));
                return true;
            }
        ];

        $successfulTests = 0;
        $totalTests = 0;

        foreach ($notificationTests as $notificationName => $testFunction) {
            $this->line("   Testing $notificationName... ");
            try {
                $result = $testFunction();
                if ($result === true) {
                    $this->info("   ✓ PASSED");
                    $successfulTests++;
                    $totalTests++;
                } elseif ($result === 'skipped_no_listing') {
                    $this->warn("   ⚠ SKIPPED (no listing)");
                } else {
                    $this->error("   ✗ FAILED");
                    $totalTests++;
                }
            } catch (\Exception $e) {
                $this->error("   ✗ FAILED: " . $e->getMessage());
                $totalTests++;
            }
        }

        $this->newLine();

        // Summary
        $this->info('=== Test Summary ===');
        $successRate = $totalTests > 0 ? round(($successfulTests / $totalTests) * 100, 2) : 0;
        $this->line("Successful tests: $successfulTests/$totalTests ($successRate%)");

        if ($successRate >= 80) {
            $this->info('✓ Email configuration is working well!');
        } elseif ($successRate >= 50) {
            $this->warn('⚠ Email configuration has some issues. Check the failed tests above.');
        } else {
            $this->error('✗ Email configuration has significant issues. Please check your mail settings.');
        }

        $this->newLine();
        $this->info('=== Recommendations ===');
        $this->line('1. Check your .env file for proper mail configuration');
        $this->line('2. Verify SMTP credentials if using SMTP');
        $this->line('3. Check mail logs in storage/logs/laravel.log');
        $this->line('4. Test with a real email address instead of test@example.com');
        $this->line('5. Ensure your mail provider allows sending from your configured address');

        $this->newLine();
        $this->line('Test completed at: ' . now()->toDateTimeString());

        return 0;
    }
}
