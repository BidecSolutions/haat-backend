<?php

/**
 * Email Configuration Test Script
 * 
 * This script tests the email configuration and verifies that all notification types
 * can send emails properly. Run this script to test your email setup.
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Listing;

echo "=== Email Configuration Test Script ===\n\n";

// Test 1: Check email configuration
echo "1. Checking email configuration...\n";
$mailConfig = config('mail');
echo "   - Default mailer: " . $mailConfig['default'] . "\n";
echo "   - From address: " . $mailConfig['from']['address'] . "\n";
echo "   - From name: " . $mailConfig['from']['name'] . "\n";

if ($mailConfig['default'] === 'smtp') {
    echo "   - SMTP Host: " . $mailConfig['mailers']['smtp']['host'] . "\n";
    echo "   - SMTP Port: " . $mailConfig['mailers']['smtp']['port'] . "\n";
    echo "   - SMTP Username: " . ($mailConfig['mailers']['smtp']['username'] ?: 'Not set') . "\n";
}

echo "\n";

// Test 2: Basic email test
echo "2. Testing basic email functionality...\n";
try {
    Mail::raw('This is a test email from the TradeMe backend system. If you receive this, your email configuration is working correctly.', function ($message) {
        $message->to('test@example.com')
                ->subject('Email Configuration Test - TradeMe Backend');
    });
    echo "   ✓ Basic email test passed\n";
} catch (Exception $e) {
    echo "   ✗ Basic email test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check if users exist
echo "3. Checking for test users...\n";
$user = User::first();
if ($user) {
    echo "   ✓ Found user: " . $user->email . " (ID: " . $user->id . ")\n";
} else {
    echo "   ✗ No users found in database\n";
    echo "   Please create a user first to test notifications\n";
    exit(1);
}

echo "\n";

// Test 4: Check if listings exist
echo "4. Checking for test listings...\n";
$listing = Listing::first();
if ($listing) {
    echo "   ✓ Found listing: " . $listing->title . " (ID: " . $listing->id . ")\n";
} else {
    echo "   ⚠ No listings found - some notification tests will be skipped\n";
}

echo "\n";

// Test 5: Test all notification types
echo "5. Testing all notification types...\n";

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
    echo "   Testing $notificationName... ";
    try {
        $result = $testFunction();
        if ($result === true) {
            echo "✓ PASSED\n";
            $successfulTests++;
            $totalTests++;
        } elseif ($result === 'skipped_no_listing') {
            echo "⚠ SKIPPED (no listing)\n";
        } else {
            echo "✗ FAILED\n";
            $totalTests++;
        }
    } catch (Exception $e) {
        echo "✗ FAILED: " . $e->getMessage() . "\n";
        $totalTests++;
    }
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
$successRate = $totalTests > 0 ? round(($successfulTests / $totalTests) * 100, 2) : 0;
echo "Successful tests: $successfulTests/$totalTests ($successRate%)\n";

if ($successRate >= 80) {
    echo "✓ Email configuration is working well!\n";
} elseif ($successRate >= 50) {
    echo "⚠ Email configuration has some issues. Check the failed tests above.\n";
} else {
    echo "✗ Email configuration has significant issues. Please check your mail settings.\n";
}

echo "\n=== Recommendations ===\n";
echo "1. Check your .env file for proper mail configuration\n";
echo "2. Verify SMTP credentials if using SMTP\n";
echo "3. Check mail logs in storage/logs/laravel.log\n";
echo "4. Test with a real email address instead of test@example.com\n";
echo "5. Ensure your mail provider allows sending from your configured address\n";

echo "\nTest completed at: " . now()->toDateTimeString() . "\n";
