<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Listing;
use App\Models\ListingOffer;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::all();

            return response()->json([
                'success' => true,
                'message' => 'Successfully Fetched',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function overview()
    {
        $tottal_users = User::count();
        $sellers_count = User::WhereHas('listings')->distinct()->count();
        $buyer = User::where(function ($q) {
            $q->whereHas('buyNow')
                ->orWhereHas('offers')
                ->orWherehas('bids');
        })
            ->distinct()->count();
        $activeListings = Listing::where('is_active', 1)
            ->whereIn('status', [1]) // active
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            })
            ->count();
        $soldListings = Listing::where('status', 3)->count();
        $tottalOffers = ListingOffer::count();
        $tottalBides = Bid::count();

        return [
            'total_users' => $tottal_users,
            'users_with_listings' => $sellers_count,
            'buyers' => $buyer,
            'active_listings' => $activeListings,
            'completed_sales' => $soldListings,
            'total_offers' => $tottalOffers,
            'total_bids' => $tottalBides,
        ];
    }

    public function users()
    {
        $total_users = User::count();

        $users_with_listings = User::whereHas('listings')
            ->count();

        $users_with_sales = User::whereHas('listings', function ($q) {
            $q->where('status', 3);
        })
            ->count();

        $users_with_offers = User::whereHas('offers')
            ->count();

        $users_with_bids = User::whereHas('bids')
            ->count();

        $daily_new_users = DB::table('users')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->limit(30) // last 30 days (important for Tableau)
            ->get();

        return response()->json([
            'total_users' => $total_users,
            'users_with_listings' => $users_with_listings,
            'users_with_sales' => $users_with_sales,
            'users_with_offers' => $users_with_offers,
            'users_with_bids' => $users_with_bids,
            'daily_new_users' => $daily_new_users,
        ]);
    }

    public function allUsers()
    {
        $users = User::all()->count();

        return $users;
    }

    public function allSellers()
    {
        $sellers = User::whereHas('listings')->select('id', 'email')->count();

        return $sellers;
    }

    public function allBuyers()
    {
        $sellers = User::whereHas('buyNow')->count();
        $sellers2 = User::whereHas('offers')->count();
        $mainSeller = $sellers + $sellers2;

        return $mainSeller;
    }

    public function allBidders()
    {
        $bidders = User::whereHas('bids')->count();

        return $bidders;
    }

    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'User fetched successfully',
                'data' => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: '.$e->getMessage(),
            ], 500);

        }
    }

    public function userSummary($userId)
    {
        $user = User::with(['listings.category', 'listings.views', 'listings.images', 'watchlist', 'feedbacks'])->find($userId);

        $allListings = $user->listings()
            ->with(['category', 'views', 'images'])
            ->latest()
            ->get();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        if ($user->status == 3) {
            return response()->json([
                'success' => false,
                'message' => 'User Is deleted',
            ], 404);
        }

        // Listings stats
        $totalListings = $user->listings()->count();
        $activeListings = $user->listings()->where('status', 1)->count();
        $soldListings = $user->listings()->whereNotNull('sold_at')->count();
        $totalViews = $user->listings()->withCount('views')->get()->sum('views_count');

        // Watchlist
        $watchlistCount = $user->watchlist()->count();

        // Feedback summary
        $feedbacks = UserFeedback::where('reviewed_user_id', $user->id)->get();
        $totalFeedback = $feedbacks->count();
        $positive = $feedbacks->whereIn('rating', [4, 5])->count();
        $neutral = $feedbacks->where('rating', 3)->count();
        $negative = $feedbacks->whereIn('rating', [1, 2])->count();

        return response()->json([
            'status' => true,
            'data' => [
                'personal' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_photo' => $user->profile_photo,
                    'about_me' => $user->about_me,
                    'member_number' => $user->memberId,
                    'member_since' => $user->created_at->format('Y-m-d'),
                    'last_login' => $user->last_login_at,
                ],
                'listings' => [
                    'total' => $totalListings,
                    'active' => $activeListings,
                    'sold' => $soldListings,
                    'view_count' => $totalViews,
                ],
                'watchlist' => [
                    'count' => $watchlistCount,
                ],
                'feedback' => [
                    'total' => $totalFeedback,
                    'positive' => $positive,
                    'neutral' => $neutral,
                    'negative' => $negative,
                    'recent' => $feedbacks->take(5)->map(function ($fb) {
                        return [
                            'rating' => $fb->rating,
                            'review' => $fb->feedback_text,
                            'type' => $fb->feedback_type,
                            'date' => $fb->created_at->format('Y-m-d'),
                            'reviewer' => [
                                'id' => $fb->reviewer->id,
                                'name' => $fb->reviewer->name,
                                'username' => $fb->reviewer->username,
                            ],
                        ];
                    }),
                ],
                'recent_activity' => [
                    'last_search' => $user->searchHistories()->latest()->first()?->keyword ?? null,
                    'last_viewed_listing' => $user->listings()->with('views')->latest()->first()?->title ?? null,
                ],
                'all_listings' => $allListings,

            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'billing_address' => 'nullable|string|max:500',
                'gender' => 'nullable|string|max:100',
                'street_address' => 'nullable|string|max:100',
                'apartment' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
            ]);

            $data = $request->only([
                'name', 'email', 'phone', 'billing_address',
                'gender', 'street_address', 'apartment', 'city', 'state', 'zip_code',
            ]);

            // Always assign default hashed password if not provided
            $data['password'] = bcrypt($request->input('password', '123456'));

            $user = DB::transaction(function () use ($data) {
                // Get latest customer_number and extract numeric part
                $lastUser = User::orderByDesc('id')->first();
                $lastNumber = 1000;

                if ($lastUser && preg_match('/CN-(\d+)/', $lastUser->customer_number, $matches)) {
                    $lastNumber = (int) $matches[1];
                }

                $nextNumber = $lastNumber + 1;
                $customerNumber = 'CN-'.$nextNumber;

                // Check uniqueness in case of concurrent inserts
                while (User::where('customer_number', $customerNumber)->exists()) {
                    $nextNumber++;
                    $customerNumber = 'CN-'.$nextNumber;
                }

                $data['customer_number'] = $customerNumber;
                $data['created_by'] = auth('admin-api')->id() ?? auth('api')->id();

                return User::create($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,'.$id,
                'phone' => 'nullable|string|max:20',
                'billing_address' => 'nullable|string|max:500',
                'gender' => 'nullable|string|max:100',
                'street_address' => 'nullable|string|max:100',
                'apartment' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
            ]);

            $data = $request->only([
                'name', 'email', 'phone', 'billing_address', 'gender',
                'street_address', 'apartment', 'city', 'state', 'zip_code',
            ]);

            // Always override with default password
            $data['password'] = bcrypt('123456');

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully with default password',
                'data' => $user->fresh(),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function changeactiveInactive($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->status = $user->status == 1 ? 2 : 1; // Toggle status
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [
                    'id' => $user->id,
                    'status' => $user->status == 1 ? 'active' : 'inactive',
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function restoreUser($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->status != 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not marked as deleted.',
                ], 400);
            }

            $user->status = 1; // 1 = Active
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User account has been restored successfully.',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }
}
