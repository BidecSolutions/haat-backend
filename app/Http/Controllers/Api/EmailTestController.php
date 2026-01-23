<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailTestController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $this->emailService->testEmailConfiguration($request->email);
            
            if ($success) {
                return response()->json([
                    'status' => true,
                    'message' => 'Test email sent successfully! Check your inbox.',
                    'data' => [
                        'email' => $request->email,
                        'sent_at' => now()->toISOString()
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send test email. Check your email configuration.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error occurred while sending test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email configuration status
     */
    public function getEmailConfig()
    {
        $config = [
            'mail_driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'frontend_url' => config('app.frontend_url'),
        ];

        return response()->json([
            'status' => true,
            'message' => 'Email configuration retrieved successfully',
            'data' => $config
        ]);
    }

    /**
     * Send welcome email to a user
     */
    public function sendWelcomeEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $this->emailService->sendWelcomeEmail($user);
            
            return response()->json([
                'status' => true,
                'message' => 'Welcome email sent successfully!',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'sent_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error occurred while sending welcome email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send listing ending soon notifications
     */
    public function sendEndingSoonNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hours' => 'integer|min:1|max:168', // Max 1 week
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $hours = $request->get('hours', 24);
        
        try {
            // This would typically be done via a command, but we can call it here for testing
            $command = new \App\Console\Commands\SendListingEndingSoonNotifications();
            $command->setLaravel(app());
            
            // We'll simulate the command logic here
            $endTime = \Carbon\Carbon::now()->addHours($hours);
            $listings = \App\Models\Listing::where('status', 'active')
                ->where('end_date', '<=', $endTime)
                ->where('end_date', '>', \Carbon\Carbon::now())
                ->get();
            
            $notificationsSent = 0;
            foreach ($listings as $listing) {
                $this->emailService->sendListingEndingSoonNotification($listing);
                $notificationsSent++;
            }
            
            return response()->json([
                'status' => true,
                'message' => "Ending soon notifications sent successfully!",
                'data' => [
                    'listings_found' => $listings->count(),
                    'notifications_sent' => $notificationsSent,
                    'hours_ahead' => $hours,
                    'sent_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error occurred while sending ending soon notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test all notification types and verify email functionality
     */
    public function testAllNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $testEmail = $request->test_email;
            $results = [];

            // Test 1: Basic email configuration
            $results['basic_email_test'] = $this->emailService->testEmailConfiguration($testEmail);

            // Test 2: Welcome email
            try {
                $this->emailService->sendWelcomeEmail($user);
                $results['welcome_email'] = true;
            } catch (\Exception $e) {
                $results['welcome_email'] = false;
                $results['welcome_email_error'] = $e->getMessage();
            }

            // Test 3: Password reset notification
            try {
                $token = \Illuminate\Support\Str::random(60);
                $user->notify(new \App\Notifications\ResetPasswordNotification($token));
                $results['password_reset_notification'] = true;
            } catch (\Exception $e) {
                $results['password_reset_notification'] = false;
                $results['password_reset_error'] = $e->getMessage();
            }

            // Test 4: Bid placed notification (requires a bid)
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $bid = new \App\Models\Bid([
                        'user_id' => $user->id,
                        'listing_id' => $listing->id,
                        'amount' => 100.00,
                    ]);
                    $bid->user = $user;
                    $bid->listing = $listing;
                    
                    $user->notify(new \App\Notifications\BidPlacedNotification($bid));
                    $results['bid_placed_notification'] = true;
                } else {
                    $results['bid_placed_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['bid_placed_notification'] = false;
                $results['bid_placed_error'] = $e->getMessage();
            }

            // Test 5: Outbid notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $bid = new \App\Models\Bid([
                        'user_id' => $user->id,
                        'listing_id' => $listing->id,
                        'amount' => 150.00,
                    ]);
                    $bid->user = $user;
                    $bid->listing = $listing;
                    
                    $user->notify(new \App\Notifications\OutbidNotification($bid));
                    $results['outbid_notification'] = true;
                } else {
                    $results['outbid_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['outbid_notification'] = false;
                $results['outbid_error'] = $e->getMessage();
            }

            // Test 6: Auction won notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $user->notify(new \App\Notifications\AuctionWonNotification($listing));
                    $results['auction_won_notification'] = true;
                } else {
                    $results['auction_won_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['auction_won_notification'] = false;
                $results['auction_won_error'] = $e->getMessage();
            }

            // Test 7: Auction sold notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $user->notify(new \App\Notifications\AuctionSoldNotification($listing));
                    $results['auction_sold_notification'] = true;
                } else {
                    $results['auction_sold_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['auction_sold_notification'] = false;
                $results['auction_sold_error'] = $e->getMessage();
            }

            // Test 8: Offer approved notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $offer = new \App\Models\ListingOffer([
                        'user_id' => $user->id,
                        'listing_id' => $listing->id,
                        'amount' => 200.00,
                        'status' => 'approved',
                    ]);
                    $offer->user = $user;
                    $offer->listing = $listing;
                    
                    $user->notify(new \App\Notifications\OfferApprovedNotification($offer));
                    $results['offer_approved_notification'] = true;
                } else {
                    $results['offer_approved_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['offer_approved_notification'] = false;
                $results['offer_approved_error'] = $e->getMessage();
            }

            // Test 9: Offer expired notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $offer = new \App\Models\ListingOffer([
                        'user_id' => $user->id,
                        'listing_id' => $listing->id,
                        'amount' => 200.00,
                        'status' => 'expired',
                    ]);
                    $offer->user = $user;
                    $offer->listing = $listing;
                    
                    $user->notify(new \App\Notifications\OfferExpiredNotification($offer));
                    $results['offer_expired_notification'] = true;
                } else {
                    $results['offer_expired_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['offer_expired_notification'] = false;
                $results['offer_expired_error'] = $e->getMessage();
            }

            // Test 10: Listing bought notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $user->notify(new \App\Notifications\ListingBoughtNotification($listing, 'buyer'));
                    $results['listing_bought_notification'] = true;
                } else {
                    $results['listing_bought_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['listing_bought_notification'] = false;
                $results['listing_bought_error'] = $e->getMessage();
            }

            // Test 11: Manual bid approval required notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $user->notify(new \App\Notifications\ManualBidApprovalRequiredNotification($listing));
                    $results['manual_bid_approval_notification'] = true;
                } else {
                    $results['manual_bid_approval_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['manual_bid_approval_notification'] = false;
                $results['manual_bid_approval_error'] = $e->getMessage();
            }

            // Test 12: Listing ending soon notification
            try {
                $listing = \App\Models\Listing::first();
                if ($listing) {
                    $user->notify(new \App\Notifications\ListingEndingSoonNotification($listing));
                    $results['listing_ending_soon_notification'] = true;
                } else {
                    $results['listing_ending_soon_notification'] = 'skipped_no_listing';
                }
            } catch (\Exception $e) {
                $results['listing_ending_soon_notification'] = false;
                $results['listing_ending_soon_error'] = $e->getMessage();
            }

            // Calculate success rate
            $successfulTests = 0;
            $totalTests = 0;
            foreach ($results as $key => $value) {
                if (is_bool($value)) {
                    $totalTests++;
                    if ($value === true) {
                        $successfulTests++;
                    }
                }
            }

            $successRate = $totalTests > 0 ? round(($successfulTests / $totalTests) * 100, 2) : 0;

            return response()->json([
                'status' => true,
                'message' => 'All notification tests completed',
                'data' => [
                    'test_email' => $testEmail,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'success_rate' => $successRate . '%',
                    'successful_tests' => $successfulTests,
                    'total_tests' => $totalTests,
                    'results' => $results,
                    'tested_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error occurred while testing notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
