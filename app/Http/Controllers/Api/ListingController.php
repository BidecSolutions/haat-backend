<?php

namespace App\Http\Controllers\Api;

use App\Enums\ListingCondition;
use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Category;
use App\Models\FeedbackResponse;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\ListingImage;
use App\Models\ListingOffer;
use App\Models\ListingView;
use App\Models\SearchHistory;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\Watchlist;
use App\Traits\RecordsViews;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class ListingController extends Controller
{
    use RecordsViews;

    /**
     * --------------------------------------------------------------------------
     * 🔹 Reusable Query: Cool Auctions
     * --------------------------------------------------------------------------
     * Fetches active auction listings that:
     * - Have bids
     * - Are not expired
     * - Ordered by highest bid count
     */
    private function getCoolAuctions($userId = null, $limit = 10, $offset = 0)
    {
        $limit = $limit ?? 10;

        $listings = Listing::with([
            'category',
            'creator',
            'images',
            'attributes',
            'bids.user',
        ])
            ->withCount('bids')
            ->withCount('views')
            ->where('is_active', 1)
            ->whereNotNull('start_price')
            ->where('start_price', '>', 0)
            ->where('status', 1)
            ->where('expire_at', '>=', now())
            ->has('bids')
            ->orderByDesc('bids_count')
            ->limit($limit)
            ->offset($offset)
            ->get();

        // Merge dynamic attributes into main listing response
        $listingsdata = $listings->map(function ($listing) {
            $mainlisting = $listing->toArray();
            unset($mainlisting['attributes']);

            $attributes = collect($listing->attributes)
                ->pluck('value', 'key')
                ->toArray();

            return array_merge($mainlisting, $attributes);
        });

        return $listingsdata;
    }

    /**
     * --------------------------------------------------------------------------
     * 🔹 Reusable Query: Hot Listings
     * --------------------------------------------------------------------------
     * Fetches listings ordered by:
     * - Most views
     * - Most watchers
     */
    private function getHotListings($userId = null, $limit = 10, $offset = 0)
    {
        $listings = Listing::with([
            'category',
            'creator',
            'images',
            'bids.user',
        ])
            ->withCount(['views', 'watchers'])
            ->where('is_active', 1)
            ->where('expire_at', '>', Carbon::now()->toDateTimeString())
            ->orderByDesc('views_count')
            ->orderByDesc('watchers_count')
            ->limit($limit)
            ->offset($offset)
            ->get();

        // Attach dynamic attributes
        $listingsdata = $listings->map(function ($listing) {
            $mainlisting = $listing->toArray();
            unset($mainlisting['attributes']);

            $attributes = collect($listing->attributes)
                ->pluck('value', 'key')
                ->toArray();

            return array_merge($mainlisting, $attributes);
        });

        return $listingsdata;
    }

    /**
     * --------------------------------------------------------------------------
     * 🔹 Reusable Query: Closing Soon Listings
     * --------------------------------------------------------------------------
     * Fetches auction listings ordered by soonest expiry
     */
    private function getClosingSoon($userId = null, $limit = 10, $offset = 0)
    {
        $listings = Listing::with([
            'category',
            'creator',
            'images',
            'attributes',
            'bids.user',
        ])
            ->withCount('views')
            ->whereNotNull('start_price')
            ->where('is_active', 1)
            ->where('expire_at', '>', Carbon::now()->toDateTimeString())
            ->orderBy('expire_at', 'asc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        // Format price values
        $listings->each(function ($listing) {
            $listing->start_price = number_format((int) ($listing->start_price ?? 0));
            $listing->reserve_price = number_format((int) ($listing->reserve_price ?? 0));
            $listing->buy_now_price = number_format((int) ($listing->buy_now_price ?? 0));
        });

        // Merge attributes
        $listingsdata = $listings->map(function ($listing) {
            $mainlisting = $listing->toArray();
            unset($mainlisting['attributes']);

            $attributes = collect($listing->attributes)
                ->pluck('value', 'key')
                ->toArray();

            return array_merge($mainlisting, $attributes);
        });

        return $listingsdata;
    }

    /**
     * --------------------------------------------------------------------------
     * 🔹 Reusable Query: Featured Listings
     * --------------------------------------------------------------------------
     * Fetches listings marked as featured
     */
    private function getIsFeatured($userId = null, $limit = 10, $offset = 0)
    {
        $listings = Listing::with([
            'category',
            'creator',
            'images',
            'bids.user',
        ])
            ->where('is_featured', 1)
            ->withCount('views')
            ->where('is_active', 1)
            ->where('expire_at', '>', Carbon::now()->toDateTimeString())
            ->limit($limit)
            ->offset($offset)
            ->get();

        // Price formatting
        foreach ($listings as $listing) {
            $listing->start_price = number_format((int) ($listing->start_price ?? 0));
            $listing->reserve_price = number_format((int) ($listing->reserve_price ?? 0));
            $listing->buy_now_price = number_format((int) ($listing->buy_now_price ?? 0));
        }

        // Attach attributes
        $listingsdata = $listings->map(function ($listing) {
            $mainlisting = $listing->toArray();
            unset($mainlisting['attributes']);

            $attributes = collect($listing->attributes)
                ->pluck('value', 'key')
                ->toArray();

            return array_merge($mainlisting, $attributes);
        });

        return $listingsdata;
    }

    /**
     * --------------------------------------------------------------------------
     * 🔹 Reusable Query: Recommended Listings
     * --------------------------------------------------------------------------
     * Recommendation logic based on:
     * - Search history
     * - Viewed categories
     * - Random fallback
     */
    private function getRecommendedListings(
        $userId = null,
        $guestId = null,
        $limit = 20,
        $offset = 0,
        $listingType = null
    ) {
        /**
         * STEP 1: Fetch recent search history
         */
        $searchHistories = SearchHistory::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(! $userId && $guestId, fn($q) => $q->where('guest_id', $guestId))
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $keywords = $searchHistories->pluck('keyword')->filter()->toArray();
        $categoryIds = $searchHistories->pluck('category_id')->filter()->toArray();

        /**
         * STEP 2: Fetch recently viewed listing categories
         */
        $viewedCategories = ListingView::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($guestId, fn($q) => $q->where('guest_id', $guestId))
            ->where('viewable_type', Listing::class)
            ->orderBy('created_at', 'desc')
            ->with(['viewable' => fn($q) => $q->select('id', 'category_id')])
            ->limit(5)
            ->get()
            ->pluck('viewable.category_id')
            ->filter()
            ->toArray();

        /**
         * STEP 3: Merge category sources
         */
        $allCategoryIds = array_unique(array_merge($categoryIds, $viewedCategories));

        /**
         * STEP 4: Main recommendation query
         */
        $recommendations = Listing::with([
            'images',
            'category',
            'creator',
            'attributes',
            'bids.user',
        ])
            ->withCount('views', 'watchers as watch_count', 'bids')
            ->where('status', 1)
            ->where('is_active', 1)
            ->where(fn($q) => $q->where('expire_at', '>=', now())->orWhereNull('expire_at'))
            ->where(function ($q) use ($keywords, $allCategoryIds) {
                if (count($keywords)) {
                    $q->where(function ($sub) use ($keywords) {
                        foreach ($keywords as $word) {
                            $sub->orWhere('title', 'LIKE', "%{$word}%")
                                ->orWhere('description', 'LIKE', "%{$word}%");
                        }
                    });
                }
                $q->orWhereIn('category_id', $allCategoryIds);
            })
            ->when($listingType, fn($q) => $q->where('listing_type', $listingType))
            ->limit($limit)
            ->offset($offset)
            ->get();

        /**
         * STEP 5: Random fallback if insufficient results
         */
        $random = collect();
        if ($recommendations->count() < $limit) {
            $random = Listing::with([
                'images',
                'category',
                'creator',
                'attributes',
                'bids.user',
            ])
                ->withCount('views', 'watchers as watch_count', 'bids')
                ->where('status', 1)
                ->where('is_active', 1)
                ->where(fn($q) => $q->where('expire_at', '>=', now())->orWhereNull('expire_at'))
                ->when($listingType, fn($q) => $q->where('listing_type', $listingType))
                ->inRandomOrder()
                ->limit($limit - $recommendations->count())
                ->get();
        }

        /**
         * STEP 6: Merge and format output
         */
        $merged = $recommendations->merge($random);

        $merged->each(function ($listing) {
            $listing->start_price = number_format((int) ($listing->start_price ?? 0));
            $listing->reserve_price = number_format((int) ($listing->reserve_price ?? 0));
            $listing->buy_now_price = number_format((int) ($listing->buy_now_price ?? 0));
        });

        return $merged->map(function ($listing) {
            $mainlisting = $listing->toArray();
            unset($mainlisting['attributes']);

            $attributes = collect($listing->attributes)
                ->pluck('value', 'key')
                ->toArray();

            return array_merge($mainlisting, $attributes);
        });
    }

    public function index(Request $request)
    {
        $feedback = false;
        try {
            $query = Listing::with(['category', 'creator', 'images', 'bids.user', 'winningBid.user', 'buyNowPurchases.buyer', 'attributes', 'paymentMethod:id,name', 'shippingMethod:id,name']); // removed WHERE('is_active, '1') from here added down in status logic for sold listings
            // $query = Listing::query();
            // return $query->get();

            // 🔒 Filter by creator if authenticated (user guard)
            $authUserId = auth('api')->check() ? auth('api')->id() : null;
            if ($authUserId) {
                $query->where('created_by', $authUserId);
            }
            if ($authUserId) {
                $feedbackCheck = FeedbackResponse::where('user_id', $authUserId)->get();
                if ($feedbackCheck) {
                    $feedback = true;
                }
            }
            if ($request->status != 3) {
                $query->where('is_active', 1);
            }
            // 🔎 Filter by search keyword
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                        ->orWhere('brand', 'like', '%' . $request->search . '%')
                        ->orWhere('color', 'like', '%' . $request->search . '%')
                        ->orWhere('size', 'like', '%' . $request->search . '%')
                        ->orWhere('style', 'like', '%' . $request->search . '%')
                        ->orWhere('memory', 'like', '%' . $request->search . '%')
                        ->orWhere('hard_drive_size', 'like', '%' . $request->search . '%')
                        ->orWhere('cores', 'like', '%' . $request->search . '%')
                        ->orWhere('storage', 'like', '%' . $request->search . '%')
                        ->orWhereHas('category', function ($q2) use ($request) {
                            $q2->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            if ($request->has('slug')) {
                $query->where('slug', $request->slug);
            }

            if ($request->has('city')) {
                $query->whereHas('creator', function ($q) use ($request) {
                    $q->where('city', $request->city);
                });
            }

            if ($request->has('listing_type')) {
                $query->where('listing_type', $request->listing_type);
            }
            if ($request->has('creator_id')) {
                $query->where('created_by', $request->creator_id);
            }

            // if ($request->has('pending_reserve_approval')) {
            //     $query->orWhere('status', $request->pending_reserve_approval);
            // }

            // commenting this "if status block" adding new logic for sold listings
            // if ($request->has('status')) {
            //     $status = $request->status;

            //     // If it is a JSON-like string "[4,5]"
            //     if (is_string($status) && str_contains($status, '[')) {
            //         $status = json_decode($status, true);
            //     }

            //     // If comma separated "4,5"
            //     if (is_string($status)) {
            //         $status = explode(',', $status);
            //     }

            //     $query->whereIn('status', $status);
            // }

            if ($request->has('status')) {
                $status = $request->status;

                if (is_string($status) && str_contains($status, '[')) {
                    $status = json_decode($status, true);
                }

                if (is_string($status)) {
                    $status = explode(',', $status);
                }

                // Sold listings ko show karne k liye //Abdullah
                if (count($status) === 1 && (int) $status[0] === 3) {
                    $query->where('status', 3)
                        ->where('is_active', 0);
                } else {
                    $query->whereIn('status', $status)
                        ->where('is_active', 1);
                }
            } else {
                // Default // Removed this WHERE condition from above added here  //Abdullah
                $query->where('is_active', 1);
            }

            if ($request->has('not_equal_status')) {
                $query->where('status', '!=', $request->not_equal_status);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('reserve_price')) {
                $query->where('reserve_price', $request->reserve_price);
            }
            if ($request->has('start_price')) {
                $query->where('start_price', $request->start_price)->whereNull('reserve_price')->where('listing_type', '!=', 'property');
            }

            // Filter by category and its children
            if ($request->filled('category_id')) {
                $categoryIds = $this->getAllCategoryIds($request->category_id);
                $query->whereIn('category_id', $categoryIds);
            }

            if ($request->filled('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->filled('price_from')) {
                $query->where('start_price', '>=', $request->price_from);
            }

            if ($request->filled('price_to')) {
                $query->where('start_price', '<=', $request->price_to);
            }

            $listings = $query->latest()->paginate(20);
            // $listings = $query->latest()->get();
            $listings->getCollection()->transform(function ($listing) use ($authUserId) {

                $listing->setAttribute('bids_count', $listing->bids()->count());
                $listing->setAttribute('view_count', $listing->views()->count());

                $listing->start_price = number_format((int) ($listing->start_price ?? 0));
                $listing->reserve_price = number_format((int) ($listing->reserve_price ?? 0));
                $listing->buy_now_price = number_format((int) ($listing->buy_now_price ?? 0));

                // highest bid
                $highestBid = $listing->bids()->orderByDesc('amount')->first();
                if ($highestBid) {
                    $listing->highest_bid_amount = $highestBid->amount;
                    $listing->highest_bid_id = $highestBid->id;
                    $listing->highest_bidder_name = $highestBid->user->name ?? $highestBid->user->username;
                    $listing->highest_bid_type = $highestBid->type;
                } else {
                    $listing->highest_bid_amount = null;
                    $listing->highest_bid_id = null;
                    $listing->highest_bidder_name = null;
                    $listing->highest_bid_type = null;
                }

                // 💼 Offers made by user
                $listing->buying_offers = $authUserId
                    ? ListingOffer::with(['user'])->where('listing_id', $listing->id)
                    ->where('user_id', $authUserId)
                    ->get()
                    : collect();

                // 🧾 Offers received by the user (as seller)
                $listing->selling_offers = $authUserId && $listing->created_by == $authUserId
                    ? ListingOffer::with(['user'])->where('listing_id', $listing->id)
                    ->get()
                    : collect();

                // ⛔ If listing is not sold (status != 3), hide winningBid
                if ($listing->status != 3) {
                    $listing->setRelation('winningBid', null);
                }

                return $listing;
            });
            $listingData = $listings->items();
            $pagination = [
                'current_page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
                'next_page_url' => $listings->nextPageUrl(),
                'prev_page_url' => $listings->previousPageUrl(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Listings fetched successfully',
                'feeback' => $feedback,
                'data' => $listings->items(),
                'pagination' => $pagination,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function coolAuctions(Request $request)
    {
        try {
            $limit = $request->input('limit');
            $offset = $request->input('offset');
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getCoolAuctions($userId, $limit, $offset);

            return response()->json([
                'status' => true,
                'message' => 'Cool auctions fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching cool auctions',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function hotListings(Request $request)
    {
        try {
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $limit = (int) $request->input('limit', 10);
            $offset = (int) $request->input('offset', 0);
            $listings = $this->getHotListings($userId, $limit, $offset);

            return response()->json([
                'status' => true,
                'message' => 'Hot listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching hot listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function closingSoon(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        try {
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getClosingSoon($userId, $limit, $offset);

            return response()->json([
                'status' => true,
                'message' => 'Closing soon listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching closing soon listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function isfeatured(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        try {
            $userId = auth('api')->check() ? auth('api')->id() : null;
            $listings = $this->getCoolAuctions($userId, $limit, $offset);

            return response()->json([
                'status' => true,
                'message' => 'Closing soon listings fetched successfully',
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching closing soon listings',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function recommendations(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        $listingType = $request->input('listing_type', null);

        $userId = auth('api')->id();
        $guestId = $request->header('X-Guest-ID');

        if (! $userId && ! $guestId) {
            return response()->json([
                'status' => false,
                'message' => 'User or Guest ID required',
                'data' => [],
            ], 400);
        }

        $recommendations = $this->getRecommendedListings($userId, $guestId, $limit, $offset, $listingType);

        return response()->json([
            'status' => true,
            'message' => 'Recommendations fetched successfully',
            'data' => $recommendations,
        ]);
    }

    /* FULL API OF HOME PAGE */
    public function mainapi(Request $request)
    {
        try {
            $cool_auctions = $this->getCoolAuctions(null, 10);
            $hot_listings = $this->getHotListings(null, 10);
            $closing_soon = $this->getClosingSoon(null, 10);
            $is_featured = $this->getIsFeatured(null, 10);

            if (auth('api')->check()) {
                $userId = auth('api')->id();
                $recommendations = $this->getRecommendedListings($userId, null, 10, 0);
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                $recommendations = $this->getRecommendedListings(null, $guestId, 10, 0);
            } else {
                $recommendations = [];
            }

            return response()->json([
                'status' => true,
                'message' => 'Home page data fetched successfully',
                'data' => [
                    'cool_auctions' => $cool_auctions,
                    'hot_listings' => $hot_listings,
                    'closing_soon' => $closing_soon,
                    'is_featured' => $is_featured,
                    'recommendations' => $recommendations,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching home page data',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function filterListings(Request $request)
    {
        $guestId = $request->header('X-Guest-ID');
        $listing_type = $request->listing_type ?? '';
        // dd($guestId);
        $query = Listing::query()
            ->with([
                'images',
                'category',
                'creator',
                'attributes',
                'watchers',
                'bids.user',
                'paymentMethod:id,name',
                'shippingMethod:id,name',
            ])->withCount('views', 'watchers', 'bids')
            ->when($listing_type, function ($q, $listing_type) {
                $q->where('listing_type', $listing_type);
            })
            ->where('status', 1)
            ->where('is_active', 1);
        // ✅ Filter by category_id
        $categoryTree = null;
        if ($request->filled('category_id')) {
            // for parent fetching
            $categoryTree = Category::select('id', 'name', 'slug', 'parent_id')
                ->with('parentRecursive:id,name,slug,parent_id')
                ->find($request->category_id);
            // for fetching all childeren listings
            $category = Category::with('children')->find($request->category_id);
            if ($category) {
                $categoryIds = $category->allchildrenIds();
                // dd($categoryIds);
                $query->whereIn('category_id', $categoryIds);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 1);
        }
        // if ($request->input('listing_type') != 'property') {
        //     $query->where(function ($q) {
        //         $q->whereNull('expire_at')
        //             ->orWhere('expire_at', '>=', now());
        //     });
        // }
        if ($request->filled('creator_id')) {
            // return $request->creator_id;
            $query->where('created_by', $request->creator_id);
        }

        // ✅ Location filter (Haversine formula)
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->input('radius', 10);

            $haversine = "(6371 * acos(cos(radians($latitude))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians($longitude))
                    + sin(radians($latitude))
                    * sin(radians(latitude))))";

            // ✅ Exclude listings with NULL coordinates to prevent SQL errors
            $query->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('*', DB::raw("$haversine AS distance"))
                ->having('distance', '<=', $radius)
                ->orderBy('distance', 'asc');
        }

        // // ✅ Filter by category_type (join with categories table)
        // if ($request->filled('type')) {
        //     $query->where('listing_type', $request->type);
        // }
        // ✅ Address filter (matches partial address text from Google)
        if (
            $request->filled('country') ||
            $request->filled('region') ||
            $request->filled('governorate') ||
            $request->filled('city') ||
            $request->filled('area')
        ) {
            $country = $request->input('country');
            $region = $request->input('region');
            $governorate = $request->input('governorate');
            $city = $request->input('city');
            $area = $request->input('area');

            $query->where(function ($q) use ($country, $region, $governorate, $city, $area) {
                if ($country) {
                    $q->orWhere('address', 'LIKE', "%{$country}%");
                }
                if ($region) {
                    $q->orWhere('address', 'LIKE', "%{$region}%");
                }
                if ($governorate) {
                    $q->orWhere('address', 'LIKE', "%{$governorate}%");
                }
                if ($city) {
                    $q->orWhere('address', 'LIKE', "%{$city}%");
                }
                if ($area) {
                    $q->orWhere('address', 'LIKE', "%{$area}%");
                }
            });
        }

        // ✅ Predefined keys that should use range logic (besides price)
        $rangeKeys = ['year', 'odometer', 'land_size', 'bedrooms', 'bathrooms'];

        // ✅ Dynamic attribute filters
        if ($request->filled('filters') && is_array($request->filters)) {
            foreach ($request->filters as $key => $value) {
                $query->whereHas('attributes', function ($q) use ($key, $value, $rangeKeys) {
                    // Multiple values -> IN
                    if (is_array($value) && ! isset($value['min']) && ! isset($value['max'])) {
                        $q->where('key', $key)->whereIn('value', $value);
                    }
                    // Range filter (for predefined keys)
                    elseif (is_array($value) && (isset($value['min']) || isset($value['max'])) && in_array($key, $rangeKeys)) {
                        $q->where('key', $key);
                        if (isset($value['min'])) {
                            $q->where('value', '>=', $value['min']);
                        }
                        if (isset($value['max'])) {
                            $q->where('value', '<=', $value['max']);
                        }
                    }
                    // Single value
                    else {
                        $q->where('key', $key)->where('value', $value);
                    }
                });
            }
        }

        // ✅ Price range
        if ($request->filled('min_price')) {
            $query->where('buy_now_price', '>=', $request->min_price);
        }
        if ($request->filled('country_id')) {
            $country_id = $request->country_id;
            $query->whereHas('creator', function ($q) use ($country_id) {
                $q->where('country_id', $country_id);
            });
        }
        if ($request->filled('regions_id')) {
            $regions_id = $request->regions_id;
            $query->whereHas('creator', function ($q) use ($regions_id) {
                $q->where('regions_id', $regions_id);
            });
        }
        if ($request->filled('governorates_id')) {
            $governorates_id = $request->governorates_id;
            $query->whereHas('creator', function ($q) use ($governorates_id) {
                $q->where('governorates_id', $governorates_id);
            });
        }
        if ($request->filled('city_id')) {
            $city_id = $request->city_id;
            $query->whereHas('creator', function ($q) use ($city_id) {
                $q->where('city_id', $city_id);
            });
        }
        if ($request->filled('area_id')) {
            $area_id = $request->area_id;
            $query->whereHas('creator', function ($q) use ($area_id) {
                $q->where('area_id', $area_id);
            });
        }

        if ($request->filled('max_price')) {
            $query->where('buy_now_price', '<=', $request->max_price);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('search')) {
            // $query->where('title', 'LIKE', '%'. $request->search. '%');

            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%");
                //  ->orWhere('subtitle', 'LIKE', "%{$search}%")
            });
        }
        $sortOrder = strtolower($request->input('sort', 'desc'));
        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        $query->orderBy('created_at', $sortOrder);

        // ✅ Pagination
        $perPage = $request->input('pagination.per_page', 20);
        $page = $request->input('pagination.page', 1);
        $listings = $query->paginate($perPage, ['*'], 'page', $page);

        $listingData = $listings->getCollection()->map(function ($listing) {
            $listing->start_price = number_format((int) ($listing->start_price ?? 0));
            $listing->reserve_price = number_format((int) ($listing->reserve_price ?? 0));
            $listing->buy_now_price = number_format((int) ($listing->buy_now_price ?? 0));
            $listingArray = $listing->toArray();

            if ($listing->status != 3) {
                $listing->setRelation('winningBid', null);
            }
            unset($listingArray['attributes']);
            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();
            // ✅ Handle bids safely if they exist
            if (! empty($listingArray['bids']) && is_array($listingArray['bids'])) {
                $listingArray['bids'] = collect($listingArray['bids'])->map(function ($bid) {
                    // Only format amount if it exists
                    if (isset($bid['amount'])) {
                        $bid['amount'] = number_format($bid['amount'] ?? 0, 2, '.', ',');
                    }

                    return $bid;
                })->toArray();
            }

            return array_merge($listingArray, $attributes);
        });

        // ✅ Keep pagination meta
        $pagination = [
            'current_page' => $listings->currentPage(),
            'per_page' => $listings->perPage(),
            'total' => $listings->total(),
            'last_page' => $listings->lastPage(),
            'next_page_url' => $listings->nextPageUrl(),
            'prev_page_url' => $listings->previousPageUrl(),
        ];

        // ✅ Build category path if category is selected
        $categoryPath = null;
        $categoryName = null;
        if ($request->filled('category_id')) {
            $category = Category::with('parentRecursive')->find($request->category_id);

            if ($category) {
                // Full breadcrumb path

                $path = [];
                $c = $category;
                while ($c) {
                    array_unshift($path, $c->name);
                    $c = $c->parent;
                }
                $categoryPath = implode(' > ', $path);
                $categoryName = $category->name;
            }
        }

        // ✅ Capture filters (if any)
        $filters = $request->filters ?? [];

        if ($request->filled('search') || $request->filled('category_id') || ! empty($filters)) {
            $keyword = $request->input('search', $categoryName ?? '');

            if (auth('api')->check()) {
                // Logged-in user
                $userId = auth('api')->id();

                SearchHistory::updateOrCreate(
                    ['user_id' => $userId, 'keyword' => strtolower(trim($keyword))],
                    [
                        'count' => DB::raw('count + 1'),
                        'category_id' => $request->input('category_id', null),
                        'category_path' => $categoryPath ?? null,
                        'filters' => ! empty($filters) ? json_encode($filters) : null,
                    ]
                );
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                // Guest user
                $history = SearchHistory::firstOrNew([
                    'guest_id' => $guestId,
                    'keyword' => strtolower(trim($keyword)),
                ]);

                $history->count = ($history->exists ? $history->count + 1 : 1);
                $history->category_id = $request->input('category_id', null);
                $history->category_path = $categoryPath ?? null;
                $history->filters = ! empty($filters) ? json_encode($filters) : null;
                $history->guest_id = $guestId;

                $history->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Filtered listings fetched successfully',
            'data' => $listingData,
            'pagination' => $pagination,
            'category_tree' => $categoryTree,
        ]);
    }

    public function locationfilering(Request $request)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->input('radius', 10); // Default 10 km

        $haversine = "(6371 * acos(cos(radians($latitude))
                * cos(radians(latitude))
                * cos(radians(longitude) - radians($longitude))
                + sin(radians($latitude))
                * sin(radians(latitude))))";

        $listings = Listing::with(
            'images',
            'category',
            'creator',
            'attributes',
        )->where('is_active', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('*', DB::raw("$haversine AS distance"))
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        $listingsData = $listings->getCollection()->map(function ($listing) {
            $listingArray = $listing->toArray();
            unset($listingArray['attributes']);
            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();

            return array_merge($listingArray, $attributes);
        });

        return response()->json([
            'status' => true,
            'message' => 'Listings fetched based on location successfully',
            'data' => $listingsData,
            'radius' => $radius,
        ]);
    }

    public function search(Request $request)
    {
        try {
            // 🔒 Validate
            $request->validate([
                'keyword' => 'nullable|string|max:255',
                'listing_type' => 'nullable|string',
                'limit' => 'nullable|integer|min:1',
                'offset' => 'nullable|integer|min:0',
                'selected_category' => 'nullable|integer|exists:categories,id',

                // Sorting parameters
                'sort_by' => 'nullable|string|in:start_price_lowest,start_price_highest,buy_now_lowest,buy_now_highest,most_bids,latest,closing_soon',

                // Filter parameters
                'condition' => 'nullable|string|in:new,used,refurbished',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'allow_offers' => 'nullable|boolean',
                'has_shipping' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'location' => 'nullable|string',
                'authenticated_only' => 'nullable|boolean',
            ]);

            $keyword = $request->filled('keyword')
                ? strtolower(trim($request->keyword))
                : null;

            $listingType = strtolower($request->listing_type ?? '');
            $limit = max(1, (int) $request->input('limit', 50));
            $offset = max(0, (int) $request->input('offset', 0));
            $currentPage = max(1, (int) ceil(($offset + 1) / $limit));
            $selectedCategoryId = (int) $request->input('selected_category', 0);
            $sortBy = $request->input('sort_by', 'latest');
            $listingType = strtolower($request->listing_type ?? '');
            $hasListingType = ! empty($listingType);

            $isMarketplaceListing = in_array($listingType, [
                'marketplace',
                // 'property',
                // 'motors',
            ]);


            /*
            |--------------------------------------------------------------------------
            | 1️⃣ COLLECT CATEGORY IDS
            |--------------------------------------------------------------------------
            */
            $selectedCategory = null;
            $selectedCategoryIds = collect();

            if ($selectedCategoryId > 0) {
                $selectedCategory = Category::find($selectedCategoryId);
                if ($selectedCategory) {
                    $selectedCategoryIds = collect($selectedCategory->getAllDescendantIds());
                }
            }
            // dd($selectedCategoryIds);
            $listingMatches = collect();

            if ($keyword) {

                if ($isMarketplaceListing || ! $hasListingType) {
                    $listingMatches = Listing::where('status', 1)
                        ->when(
                            $hasListingType,
                            fn($q) => $q->where('listing_type', $listingType)
                        )
                        ->where('is_active', 1)
                        ->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                        ->pluck('category_id');
                }
            } else {
                // 🔥 NO KEYWORD
                if ($isMarketplaceListing || ! $hasListingType) {
                    $listingMatches = Listing::where('status', 1)
                        ->when(
                            $hasListingType,
                            fn($q) => $q->where('listing_type', $listingType)
                        )
                        ->where('is_active', 1)
                        ->pluck('category_id');
                }
            }

            $allMatchingCategoryIds = collect()
                ->merge($listingMatches)
                ->filter()
                ->unique()
                ->values();
            $matchingCategoryIdsArray = $allMatchingCategoryIds->toArray();
            // dd($matchingCategoryIdsArray);

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ ROOT CATEGORIES
            |--------------------------------------------------------------------------
            */

            if ($allMatchingCategoryIds->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No results found',
                    'data' => [],
                    'categories' => [],
                    'main_category' => 'All Categories',
                    'total_record' => 0,
                    'current_page' => $currentPage,
                    'total_pages' => 0,
                    'limit' => $limit,
                    'offset' => $offset,
                    'applied_filters' => $this->getAppliedFilters($request),
                ]);
            }

            $matchingCategories = Category::whereIn('id', $allMatchingCategoryIds)
                ->with('parentRecursive')
                ->get();
            // dd($matchingCategories);
            $rootCategories = collect();
            foreach ($matchingCategories as $category) {
                $root = $category->getRootParent();
                if (! $rootCategories->contains('id', $root->id)) {
                    $rootCategories->push($root);
                }
            }

            // dd($rootCategories);
            /*
            |--------------------------------------------------------------------------
            | 3️⃣ MAIN CATEGORY LOGIC (UNCHANGED)
            |--------------------------------------------------------------------------
            */

            $mainCategoryName = 'All Categories';
            $mainCategory = null;
            $filterCategory = null;
            $filterCategoryIds = collect();

            if ($selectedCategoryId > 0) {
                $filterCategory = Category::find($selectedCategoryId);
                // dd($filterCategory);
                if ($filterCategory) {
                    $mainCategory = $filterCategory;
                    $mainCategoryName = $filterCategory->name;
                    $filterCategoryIds = collect($filterCategory->getAllDescendantIds());
                    // $rootCategories = $filterCategory;
                }
            } elseif ($rootCategories->count() === 1) {
                $mainCategory = $rootCategories->first();
                $mainCategoryName = $mainCategory->name;
            }
            $hasSelectedCategory = $filterCategory !== null;
            // dd($hasSelectedCategory);

            $categoriesResponse = collect();
            if ($hasSelectedCategory) {
                /**
                 * 🔥 CATEGORY SELECTED
                 * Always show CHILDREN of selected category
                 */
                $mainone = $filterCategory->first();
                $categoriesResponse = $filterCategory
                    ->getFilteredChildrenWithCounts($keyword);
            } elseif ($rootCategories->count() > 1) {
                // 🔥 MULTIPLE ROOT CATEGORIES
                // Show ONLY roots with total counts

                $categoriesResponse = $rootCategories->map(function ($root) use ($keyword) {
                    $count = $root->getTotalMatchingListingsCount($keyword);

                    return $count > 0 ? [
                        'id' => $root->id,
                        'name' => $root->name,
                        'slug' => $root->slug,
                        'category_type' => $root->category_type,
                        'listing_count' => $count,
                    ] : null;
                })->filter()->values();

                $mainCategoryName = 'All Categories';
            } elseif ($rootCategories->count() === 1) {
                // 🔥 SINGLE ROOT CATEGORY
                // Show DIRECT CHILDREN ONLY

                $mainCategory = $rootCategories->first();
                $mainCategoryName = $mainCategory->name;

                $categoriesResponse = $mainCategory
                    ->getFilteredChildrenWithCounts($keyword);
            }

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ FINAL QUERIES
            |--------------------------------------------------------------------------
            */
            $listingQuery = null;

            if ($isMarketplaceListing || ! $hasListingType) {
                $listingQuery = Listing::with(['images', 'category', 'creator', 'bids'])
                    ->withCount('views', 'bids')
                    ->where('status', 1)
                    ->where('is_active', 1)
                    ->when(
                        $hasListingType,
                        fn($q) => $q->where('listing_type', $listingType)
                    )
                    ->when(
                        $keyword,
                        fn($q) => $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    );

                $listingQuery = $this->applyListingFilters($listingQuery, $request);
                $listingQuery = $this->applyListingSorting($listingQuery, $sortBy);
            }

            // dd("Awdawd");
            if ($filterCategoryIds->isNotEmpty()) {

                if ($listingQuery) {
                    $listingQuery->whereIn('category_id', $filterCategoryIds);
                }
            }

            if ($filterCategory) {
                $mainCategoryName = $filterCategory->name;
                // dd($filterCategory);
            }

            $totalRecords = $listingQuery ? $listingQuery->count() : 0;

            $totalPages = (int) ceil($totalRecords / $limit);
            $finalResults = collect();
            if ($listingQuery) {
                $finalResults = collect()
                    ->merge(
                        $listingQuery->skip($offset)->take($limit)->get()
                            ->map(fn($l) => array_merge($l->toArray(), ['type' => 'listing']))
                    );
            }
            $finalResults = $finalResults->values();
            $categoryPath = '';

            if ($filterCategory) {
                // Selected category path
                $categoryPath = $filterCategory->getCategoryPath();
            } elseif ($mainCategory) {
                // Fallback when only root category is detected
                $categoryPath = $mainCategory->name;
            } else {
                $categoryPath = 'All Categories';
            }

            if ($keyword) {
                $this->saveSearchHistory($request, $keyword, $categoryPath);
            }
            return response()->json([
                'status' => true,
                'message' => 'Search results fetched successfully',
                'data' => $finalResults,
                'categories' => $categoriesResponse,
                'main_category' => $mainCategoryName,
                'total_record' => $totalRecords,
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'limit' => $limit,
                'offset' => $offset,
                'sort_by' => $sortBy,
                'applied_filters' => $this->getAppliedFilters($request),
            ]);
        } catch (\Throwable $e) {
            Log::error('Search error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Apply filters to listing query
     */
    private function applyListingFilters($query, $request)
    {
        // Condition filter
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        // Price range filter
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('start_price', [$request->min_price, $request->max_price])
                    ->orWhereBetween('buy_now_price', [$request->min_price, $request->max_price])
                    ->orWhereBetween('reserve_price', [$request->min_price, $request->max_price]);
            });
        } elseif ($request->filled('min_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('start_price', '>=', $request->min_price)
                    ->orWhere('buy_now_price', '>=', $request->min_price)
                    ->orWhere('reserve_price', '>=', $request->min_price);
            });
        } elseif ($request->filled('max_price')) {
            $query->where(function ($q) use ($request) {
                $q->where('start_price', '<=', $request->max_price)
                    ->orWhere('buy_now_price', '<=', $request->max_price)
                    ->orWhere('reserve_price', '<=', $request->max_price);
            });
        }

        // Allow offers filter
        if ($request->filled('allow_offers')) {
            $query->where('allow_offers', (bool) $request->allow_offers);
        }

        // Shipping filter
        if ($request->filled('has_shipping')) {
            $hasShipping = (bool) $request->has_shipping;
            if ($hasShipping) {
                $query->whereNotNull('shipping_method_id');
            } else {
                $query->whereNull('shipping_method_id');
            }
        }

        // Featured filter
        if ($request->filled('is_featured')) {
            $query->where('is_featured', (bool) $request->is_featured);
        }

        // Location filter
        if ($request->filled('location')) {
            $location = strtolower($request->location);
            $query->where(function ($q) use ($location) {
                $q->whereRaw('LOWER(address) LIKE ?', ["%{$location}%"])
                    ->orWhereHas('regions', function ($regionQuery) use ($location) {
                        $regionQuery->whereRaw('LOWER(name) LIKE ?', ["%{$location}%"]);
                    })
                    ->orWhereHas('governorates', function ($govQuery) use ($location) {
                        $govQuery->whereRaw('LOWER(name) LIKE ?', ["%{$location}%"]);
                    })
                    ->orWhereHas('cities', function ($cityQuery) use ($location) {
                        $cityQuery->whereRaw('LOWER(name) LIKE ?', ["%{$location}%"]);
                    })
                    ->orWhereHas('area', function ($areaQuery) use ($location) {
                        $areaQuery->whereRaw('LOWER(name) LIKE ?', ["%{$location}%"]);
                    });
            });
        }

        return $query;
    }

    /**
     * Apply sorting to listing query
     */
    private function applyListingSorting($query, $sortBy)
    {
        switch ($sortBy) {
            case 'start_price_lowest':
                return $query->orderBy('start_price', 'ASC')
                    ->orderByRaw('CASE WHEN start_price IS NULL THEN 1 ELSE 0 END');

            case 'start_price_highest':
                return $query->orderBy('start_price', 'DESC')
                    ->orderByRaw('CASE WHEN start_price IS NULL THEN 1 ELSE 0 END');

            case 'buy_now_lowest':
                return $query->orderBy('buy_now_price', 'ASC')
                    ->orderByRaw('CASE WHEN buy_now_price IS NULL THEN 1 ELSE 0 END');

            case 'buy_now_highest':
                return $query->orderBy('buy_now_price', 'DESC')
                    ->orderByRaw('CASE WHEN buy_now_price IS NULL THEN 1 ELSE 0 END');

            case 'most_bids':
                return $query->withCount('bids')->orderBy('bids_count', 'DESC');

            case 'closing_soon':
                return $query->whereNotNull('expire_at')
                    ->orderBy('expire_at', 'ASC')
                    ->orderBy('created_at', 'DESC');

            case 'latest':
            default:
                return $query->orderBy('created_at', 'DESC');
        }
    }

    /**
     * Get available filter options for frontend
     */
    private function getAvailableFilters($baseQuery, $allMatchingCategoryIds, $filterCategoryIds, $keyword)
    {
        // Get available conditions WITH keyword filter
        $conditions = Listing::whereIn('category_id', $allMatchingCategoryIds)
            ->where('status', 1)
            ->where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(brand) LIKE ?', ["%{$keyword}%"]);
            })
            ->select('condition')
            ->distinct()
            ->pluck('condition')
            ->filter()
            ->values();

        // Get price range WITH keyword filter
        $priceRange = Listing::whereIn('category_id', $allMatchingCategoryIds)
            ->where('status', 1)
            ->where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(brand) LIKE ?', ["%{$keyword}%"]);
            })
            ->selectRaw('MIN(COALESCE(start_price, buy_now_price, reserve_price)) as min_price,
                    MAX(COALESCE(start_price, buy_now_price, reserve_price)) as max_price')
            ->first();

        // Get counts for boolean filters WITH keyword filter
        $withOffersCount = Listing::whereIn('category_id', $allMatchingCategoryIds)
            ->where('status', 1)
            ->where('allow_offers', true)
            ->where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(brand) LIKE ?', ["%{$keyword}%"]);
            })
            ->count();

        $withShippingCount = Listing::whereIn('category_id', $allMatchingCategoryIds)
            ->where('status', 1)
            ->whereNotNull('shipping_method_id')
            ->where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(brand) LIKE ?', ["%{$keyword}%"]);
            })
            ->count();

        $featuredCount = Listing::whereIn('category_id', $allMatchingCategoryIds)
            ->where('status', 1)
            ->where('is_featured', true)
            ->where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(brand) LIKE ?', ["%{$keyword}%"]);
            })
            ->count();

        $authenticatedOnlyCount = Listing::whereIn('category_id', $allMatchingCategoryIds)
            ->where('status', 1)
            ->where('authenticated_bidders_only', true)
            ->where('is_active', 1)
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(subtitle) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(brand) LIKE ?', ["%{$keyword}%"]);
            })
            ->count();

        return [
            'conditions' => $conditions,
            'price_range' => [
                'min' => (float) ($priceRange->min_price ?? 0),
                'max' => (float) ($priceRange->max_price ?? 0),
            ],
            'counts' => [
                'with_offers' => $withOffersCount,
                'with_shipping' => $withShippingCount,
                'featured' => $featuredCount,
                'authenticated_only' => $authenticatedOnlyCount,
            ],
        ];
    }

    /**
     * Get applied filters for response
     */
    private function getAppliedFilters($request)
    {
        $applied = [];

        if ($request->filled('condition')) {
            $applied['condition'] = $request->condition;
        }

        if ($request->filled('min_price')) {
            $applied['min_price'] = (float) $request->min_price;
        }

        if ($request->filled('max_price')) {
            $applied['max_price'] = (float) $request->max_price;
        }

        if ($request->filled('allow_offers')) {
            $applied['allow_offers'] = (bool) $request->allow_offers;
        }

        if ($request->filled('has_shipping')) {
            $applied['has_shipping'] = (bool) $request->has_shipping;
        }

        if ($request->filled('is_featured')) {
            $applied['is_featured'] = (bool) $request->is_featured;
        }

        if ($request->filled('location')) {
            $applied['location'] = $request->location;
        }

        if ($request->filled('authenticated_only')) {
            $applied['authenticated_only'] = (bool) $request->authenticated_only;
        }

        return $applied;
    }

    /**
     * Build category path text.
     */
    private function buildCategoryPath($category)
    {
        $path = [];
        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Save search history
     */
    private function saveSearchHistory(Request $request, $keyword, $categoryPath)
    {
        if (auth('api')->check()) {

            SearchHistory::updateOrCreate(
                [
                    'user_id' => auth('api')->id(),
                    'keyword' => $keyword,
                ],
                [
                    'count' => DB::raw('count + 1'),
                    'category_path' => $categoryPath,
                ]
            );
        } elseif ($guestId = $request->header('X-Guest-ID')) {

            $searchHistory = SearchHistory::firstOrNew([
                'guest_id' => $guestId,
                'keyword' => $keyword,
            ]);

            $searchHistory->count = ($searchHistory->exists ? $searchHistory->count + 1 : 1);
            $searchHistory->keyword = $keyword;
            $searchHistory->guest_id = $guestId;
            $searchHistory->category_path = $categoryPath ?? '';
            $searchHistory->save();
        }
    }

    public function suggestions(Request $request)
    {
        $query = $request->query('query');
        $isApp = $request->has('app') ? $request->query('app') === 'true' : false;
        $listingType = $request->input('listing_type', '');
        $onlyListings = ! empty($listingType);

        $suggestions = collect();
        $webSuggestions = collect();

        if ($query) {
            $request->validate([
                'query' => 'required|string|max:255',
            ]);

            // ============================
            // 🔎 1️⃣ LISTINGS (Always included)
            // ============================
            $listingSuggestions = Listing::where('status', 1)
                ->where('title', 'LIKE', "%{$query}%")
                ->limit(10)
                ->when($listingType, function ($q) use ($listingType) {
                    $q->where('listing_type', $listingType);
                })
                ->where('is_active', 1)
                ->pluck('title');

            $listingWeb = Listing::where('status', 1)
                ->where('title', 'LIKE', "%{$query}%")
                ->where('is_active', 1)
                ->limit(10)
                ->with(['images:id,listing_id,image_path', 'category:id,parent_id,name,slug,category_type'])
                ->select('id', 'title', 'slug', 'buy_now_price', 'listing_type', 'category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'listing',
                        'id' => $item->id,
                        'title' => $item->title,
                        'slug' => $item->slug,
                        'listing_type' => $item->listing_type,
                        'category_id' => $item->category_id,
                        'buy_now_price' => $item->buy_now_price,
                        'images' => $item->images,
                        'category' => $item->category,
                    ];
                });


            if (! $onlyListings) {

                // ============================
                // 🔄 Merge suggestions
                // ============================
                $suggestions = collect($listingSuggestions)
                    ->unique()
                    ->take(10)
                    ->values();

                $webSuggestions = collect($listingWeb)
                    ->take(10)
                    ->values();
            }

            // ============================
            // 📌 Past Searches
            // ============================
            $pastSearches = [];
            if (auth('api')->check()) {
                $pastSearches = SearchHistory::where('user_id', auth('api')->id())
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->pluck('keyword');
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                $pastSearches = SearchHistory::where('guest_id', $guestId)
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->pluck('keyword');
            }

            return response()->json([
                'status' => true,
                'message' => 'Suggestions fetched successfully',
                'suggestions' => $suggestions,
                'past_searches' => $pastSearches,
                'web_suggestions' => $webSuggestions,
            ]);
        }
    }

    public function homePastSearches()
    {
        $searchResults = [];

        // Determine user/guest
        $isGuest = !auth('api')->check();

        if ($isGuest) {
            $guestId = request()->header('X-Guest-ID');
            if (!$guestId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Guest ID missing',
                    'data' => [],
                ], 400);
            }
            $identifier = ['guest_id' => $guestId];
        } else {
            $identifier = ['user_id' => auth('api')->id()];
        }

        // Fetch past searches (no type filter - we handle all types)
        $pastSearches = SearchHistory::where($identifier)
            ->orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10) // Get more since we'll group by keyword+path
            ->get()
            ->unique(fn($search) => $search->keyword . '|' . $search->category_path) // Remove duplicates
            ->take(5); // Take top 5 unique

        foreach ($pastSearches as $search) {
            $keyword = $search->keyword;
            $categoryPath = $search->category_path;

            // Determine category IDs if path exists
            $categoryIds = null;
            if ($categoryPath && $categoryPath !== 'All Categories') {
                // Extract category from path (get last segment or use category_id if stored)
                if ($search->category_id) {
                    $categoryIds = $this->getAllCategoryIds($search->category_id);
                }
            }

            $allResults = collect();

            // 1️⃣ Fetch Marketplace Listings (marketplace/property/motors)
            $listingQuery = Listing::with(['images', 'category', 'creator'])
                ->withCount('views')
                ->where('status', 1)
                ->where('is_active', 1);

            if ($keyword) {
                $listingQuery->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($keyword) . '%']);
            }

            if ($categoryIds) {
                $listingQuery->whereIn('category_id', $categoryIds);
            }

            $listings = $listingQuery->limit(3)->get()
                ->map(fn($l) => array_merge($l->toArray(), ['type' => 'listing']));

            $allResults = $allResults->merge($listings);
            // Only add to results if we have items
            $searchResults[] = [
                'id' => $search->id,
                'keyword' => $keyword ?: null,
                'path' => $categoryPath,
                'listings' => $allResults->take(5)->values(), // Limit to 5 total items
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Home suggestions fetched successfully',
            'data' => $searchResults,
        ]);
    }

    public function removePastSearch($searchId)
    {
        try {
            if (! auth('api')->check()) {
                $guestId = request()->header('X-Guest-ID');
                if (! $guestId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Guest ID missing',
                        'data' => [],
                    ], 400);
                }
                $search = SearchHistory::where('id', $searchId)->where('guest_id', $guestId)->first();
                if (! $search) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Search not found',
                        'data' => [],
                    ], 404);
                }
                $search->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Search removed successfully',
                    'data' => [],
                ]);
            } else {
                $userId = auth('api')->id();
                $search = SearchHistory::where('id', $searchId)->where('user_id', $userId)->first();
                if (! $search) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Search not found',
                        'data' => [],
                    ], 404);
                }
                $search->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Search removed successfully',
                    'data' => [],
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'somthing went wrong',
                'error' => $e,
            ]);
        }
    }

    public function filtersMetadata(Request $request)
    {
        $listingType = $request->input('listing_type');

        $filters = ListingAttribute::query()
            ->whereHas('listing', function ($q) use ($listingType) {
                $q->where('listing_type', $listingType);
            })
            ->select('key', 'value')
            ->distinct()
            ->get()
            ->groupBy('key')
            ->map(function ($items) {
                return $items->pluck('value')->unique()->values();
            });

        return response()->json([
            'status' => true,
            'listing_type' => $listingType,
            'filters' => $filters,
        ]);
    }

    public function searchById(Request $request, $id)
    {
        try {
            $listing = Listing::with(['images', 'category', 'creator', 'attributes', 'bids.user', 'winningBid.user', 'buyNowPurchases.buyer'])->withCount('views')->where('is_active', 1)->findOrFail($id);

            // Increment view count
            if (auth('api')->check()) {
                $userId = auth('api')->id();
                ListingView::firstOrCreate(
                    ['listing_id' => $listing->id, 'user_id' => $userId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            } elseif ($guestId = $request->header('X-Guest-ID')) {
                ListingView::firstOrCreate(
                    ['listing_id' => $listing->id, 'guest_id' => $guestId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing fetched successfully',
                'data' => $listing,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found',
                'data' => null,
            ], 404);
        }
    }

    // Get listings by type ( marketplace)
    public function indexByType($type)
    {
        try {
            $listings = Listing::with(['user', 'category'])->where('is_active', 1)->byType($type)->get();

            return response()->json(['status' => true, 'data' => $listings]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch listings by type', 'error' => $e->getMessage()], 500);
        }
    }

    private function getAllCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];

        $children = Category::where('parent_id', $categoryId)->pluck('id');

        foreach ($children as $childId) {
            $categoryIds = array_merge($categoryIds, $this->getAllCategoryIds($childId));
        }

        return $categoryIds;
    }

    // public function recentViewedListings() {}

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'required|string',
                'listing_type' => 'required|string',
                'condition' => ['required', new Enum(ListingCondition::class)],
                'country_id' => 'nullable|max:20|exists:countries,id',
                'regions_id' => 'nullable|max:20|exists:regions,id',
                'governorates_id' => 'nullable|max:20|exists:governorates,id',
                'city_id' => 'nullable|integer|exists:cities,id',
                'area_id' => 'nullable|integer|exists:area,id',
                'start_price' => 'nullable|numeric|min:0',
                'reserve_price' => 'nullable|numeric|min:0',
                'buy_now_price' => 'nullable|numeric|min:0',
                'allow_offers' => 'boolean',
                'quantity' => 'integer|min:1',
                'authenticated_bidders_only' => 'boolean',
                'pickup_option' => 'nullable|in:1',
                'shipping_method_id' => 'nullable|exists:shipping_methods,id',
                'payment_method_id' => 'nullable|exists:payment_methods,id',
                'longitude' => [
                    'nullable',
                    'numeric',
                    'between:-180,180',
                ],
                'latitude' => [
                    'nullable',
                    'numeric',
                    'between:-90,90',
                ],
                'address' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:100',
                'size' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'style' => 'nullable|string|max:100',
                'memory' => 'nullable|string|max:100',
                'hard_drive_size' => 'nullable|string|max:100',
                'cores' => 'nullable|string|max:100',
                'storage' => 'nullable|string|max:100',
                'category_id' => 'required|exists:categories,id',
                'meta_title' => 'nullable|string|max:255',
                'latitude' => 'nullable|string|max:50',
                'longitude' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'expire_at' => 'nullable|date|after:now',
                'images.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'attributes' => 'array',
                'attributes.*.key' => 'required|string',
                'attributes.*.value' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $category = Category::find($data['category_id']);
            // return $category;
            if ($data['listing_type'] != $category->category_type) {
                return response()->json([
                    'status' => false,
                    'message' => 'Must Select the same category as the listing type',
                    'data' => null,
                ], 422);
            }
            $data['slug'] = Str::slug($request->title . '-' . uniqid());
            $data['status'] = 1;
            $data['is_active'] = 1;
            $data['created_by'] = auth('api')->id(); // or auth('admin-api')->id()
            $creator = User::where('id', $data['created_by'])->first();
            if (! $creator) {
                return response()->json([
                    'status' => false,
                    'message' => 'User Not Found',
                    'data' => [],
                ]);
            }
            $listing = Listing::create($data);
            $emailListing = Listing::where('id', $listing->id)->where('is_active', 1)->with('category')->first();
            // Send email notification to the listing creator
            Mail::send('emails.notifications.listing_created', [
                'user' => $creator,
                'listing' => $emailListing,
            ], function ($message) use ($creator, $listing) {
                $message->to($creator->email)
                    ->subject('🎉 Your Listing "' . $listing->title . '" Has Been Created Successfully!');
            });

            // Save attributes
            if (! empty($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    $listing->attributes()->create([
                        'key' => $attr['key'],
                        'value' => $attr['value'],
                    ]);
                }
            }

            // 📁 Ensure directory exists
            $directory = 'listings/images';
            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0775, true);
            }

            // 🖼 Upload images (max 20)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    if ($index >= 20) {
                        break;
                    }

                    $path = $image->store($directory, 'public');

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'alt_text' => $request->input("alt_text.$index", null),
                        'order' => $index,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing created successfully',
                'data' => $listing->load(['images', 'attributes']),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        $item = Listing::find($id);

        if (! $item) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        $item->note = $request->note;
        $item->save();

        return response()->json(['status' => true, 'message' => 'Note updated successfully', 'data' => $item]);
    }

    public function deleteNote($id)
    {
        $item = Listing::find($id);

        if (! $item) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        $item->note = null;
        $item->save();

        return response()->json(['status' => true, 'message' => 'Note deleted successfully']);
    }

    public function show($slug)
    {
        try {
            // Build the query first
            $query = Listing::with([
                'category',
                'creator',
                'images',
                'bids.user',
                'attributes',
                'comments.user:id,username,profile_photo',
                'comments.replies.user:id,username,profile_photo',
            ])->withCount('views')->where('slug', $slug);
            // if (auth('api')->check()) {
            //     $userId = auth('api')->id();
            //     $query->where('created_by', $userId);
            // }
            // ✅ Get actual model instance
            $listing = $query->first();
            if (! $listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null,
                ], 404);
            }

            // $listing = $listing->first();

            $listing->setAttribute('bids_count', $listing->bids()->count());
            $listing->setAttribute('view_count', $listing->views()->count());
            $listing->start_price = number_format((int) ($listing->start_price ?? 0));
            // $listing->reserve_price = number_format((int) ($listing->reserve_price ?? 0));  //The value was in minus - thats why i commented it
            $listing->buy_now_price = number_format((int) ($listing->buy_now_price ?? 0));

            // ✅ Cache listing views
            $this->recordView($listing);

            $buyingOffers = collect();
            $sellingOffers = collect();

            if (auth('api')->check()) {
                $userId = auth('api')->id();

                $buyingOffers = ListingOffer::with('user')
                    ->where('listing_id', $listing->id)
                    ->where('user_id', $userId)
                    ->get();

                if ($listing->created_by == $userId) {
                    $sellingOffers = ListingOffer::with('user')
                        ->where('listing_id', $listing->id)
                        ->get();
                }
            }

            // ✅ Calculate feedback percentage
            $totalFeedbackCount = UserFeedback::where('reviewed_user_id', $listing->created_by)->count();
            $positiveFeedbackCount = UserFeedback::where('reviewed_user_id', $listing->created_by)
                ->whereIn('rating', [4, 5])
                ->count();

            $positiveFeedbackPercentage = $totalFeedbackCount > 0
                ? round(($positiveFeedbackCount / $totalFeedbackCount) * 100, 1)
                : 0;

            $attributes = collect($listing->attributes)->pluck('value', 'key')->toArray();
            $listingData = array_merge($listing->toArray(), $attributes);
            unset($listingData['attributes']);

            // ✅ Dealer's other listings
            $dealersListing = Listing::with('images:id,listing_id,image_path')
                ->when($listingData['created_by'] ?? null, fn($q, $created_by) => $q->where('created_by', $created_by))
                ->when($listingData['listing_type'] ?? null, fn($q, $listingType) => $q->where('listing_type', $listingType))
                ->when($listingData['is_active'] ?? null, fn($q, $IsActive) => $q->where('is_active', $IsActive))
                ->select('id', 'title', 'slug', 'description', 'listing_type', 'condition', 'start_price', 'buy_now_price', 'created_by')
                ->limit(4)
                ->get();

            // ✅ Nearby listings (only for property type)
            $nearbyListings = collect();

            if (
                strtolower($listing->listing_type) === 'property' &&
                ! is_null($listing->latitude) &&
                ! is_null($listing->longitude)
            ) {
                $latitude = $listing->latitude;
                $longitude = $listing->longitude;
                $radius = request()->input('radius', 1000); // Default 10 km

                $haversine = "(6371 * acos(cos(radians($latitude))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians($longitude))
                        + sin(radians($latitude))
                        * sin(radians(latitude))))";

                $nearbyListings = Listing::select('id', 'title', 'slug', 'latitude', 'longitude', 'buy_now_price', 'listing_type', DB::raw("$haversine AS distance"))
                    ->with('images:id,listing_id,image_path', 'attributes')
                    ->where('id', '!=', $listing->id)
                    ->where('listing_type', 'property')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->having('distance', '<=', $radius)
                    ->orderBy('distance', 'asc')
                    ->limit(10)
                    ->get();

                $nearbyListings = $nearbyListings->map(function ($tem) {
                    $listingsArray = $tem->toArray();
                    unset($listingsArray['attributes']);
                    $attributes = $tem->attributes->pluck('value', 'key')->toArray();

                    return array_merge($listingsArray, $attributes);
                });
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing fetched successfully',
                'data' => [
                    'listing' => $listingData,
                    'buying_offers' => $buyingOffers,
                    'selling_offers' => $sellingOffers,
                    'creator_feedback_percentage' => $positiveFeedbackPercentage,
                    'dealers_other_listings' => $dealersListing,
                    'nearby_listings' => $nearbyListings, // ✅ Added nearby listings
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listing',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $slug)
    {
        // echo $request->all();
        try {
            $listing = Listing::where('slug', $slug)->with('images')->first();

            if (! $listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null,
                ], 404);
            }
            if ($request->has('expire_at')) {
                $expire_at = $request->input('expire_at');

                $data = ['expire_at' => $expire_at, 'status' => 1];
                $listing->update($data);

                // return response()->json([
                //     'status' => true,
                //     'message' => 'Listing expiration updated successfully',
                //     'data' => $listing,
                // ]);
            }
            $bidCheck = Bid::where('listing_id', $listing->id)->first();
            if ($bidCheck) {
                return response()->json([
                    'status' => false,
                    'message' => 'you cannot update this listing becasue the bidding is started on this product',
                    'data' => [],
                ]);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'required|string',
                'listing_type' => 'sometimes|string',
                'country_id' => 'nullable|max:20|exists:countries,id',
                'regions_id' => 'nullable|max:20|exists:regions,id',
                'governorates_id' => 'nullable|max:20|exists:governorates,id',
                'city_id' => 'nullable|integer|exists:cities,id',
                'area_id' => 'nullable|integer|exists:area,id',
                'condition' => ['required', new Enum(ListingCondition::class)],
                'start_price' => 'nullable|numeric|min:0',
                'reserve_price' => 'nullable|numeric|min:0',
                'buy_now_price' => 'nullable|numeric|min:0',
                'allow_offers' => 'boolean',
                'quantity' => 'integer|min:1',
                'authenticated_bidders_only' => 'boolean',
                'pickup_option' => 'required|in:1',
                'shipping_method_id' => 'nullable|exists:shipping_methods,id',
                'payment_method_id' => 'nullable|exists:payment_methods,id',
                'color' => 'nullable|string|max:100',
                'size' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'style' => 'nullable|string|max:100',
                'memory' => 'nullable|string|max:100',
                'hard_drive_size' => 'nullable|string|max:100',
                'cores' => 'nullable|string|max:100',
                'storage' => 'nullable|string|max:100',
                'category_id' => 'required|exists:categories,id',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'expire_at' => 'nullable|date|after:now',
                'images.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'attributes' => 'array',
                'attributes.*.key' => 'required|string',
                'attributes.*.value' => 'nullable|string',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            Log::info('Validated data:', $data);
            $listing->update($data);

            //  Sync attributes
            if (! empty($data['attributes'])) {
                $listing->attributes()->delete();
                foreach ($data['attributes'] as $attr) {
                    $listing->attributes()->create($attr);
                }
            }

            // ✅ Only process images if provided
            if ($request->hasFile('images')) {
                $directory = 'listings/images';
                if (! Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0775, true);
                }

                $existingCount = $listing->images->count();

                foreach ($request->file('images') as $index => $image) {
                    if (($existingCount + $index) >= 20) {
                        break;
                    }

                    $path = $image->store($directory, 'public');

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'alt_text' => $request->input("alt_text.$index", null),
                        'order' => $existingCount + $index,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Listing updated successfully',
                'data' => $listing->load('images', 'attributes'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    // Deleting images
    public function deleteImage($id)
    {
        try {
            $image = ListingImage::find($id);

            if (! $image) {
                return response()->json([
                    'status' => false,
                    'message' => 'Image not found',
                ], 404);
            }

            // Delete image file from storage
            Storage::disk('public')->delete($image->image_path);

            // Delete record from database
            $image->delete();

            return response()->json([
                'status' => true,
                'message' => 'Image deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function filters()
    {
        try {
            $conditions = Listing::where('is_active', 1)->select('condition')
                ->distinct()
                ->pluck('condition');

            $categories = Category::whereHas('listings')
                ->withCount('listings')
                ->get();

            $priceRange = [
                'min' => Listing::min('start_price'),
                'max' => Listing::max('start_price'),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Filters fetched successfully',
                'data' => [
                    'conditions' => $conditions,
                    'categories' => $categories,
                    'price_range' => $priceRange,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching filters',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($slug)
    {
        $listing = Listing::where('slug', $slug)
            // ->where('created_by', auth('api')->id())
            ->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or unauthorized',
                'data' => null,
            ], 404);
        }

        // Toggle between 1 and 0 for integer type
        $listing->is_active = $listing->is_active == 1 ? 0 : 1;
        // $listing->status = $listing->status == 0 ? 1 : 0;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing activation toggled successfully',
            'data' => [
                'listing_id' => $listing->id,
                'is_active' => $listing->is_active,
            ],
        ]);
    }

    public function destroy($slug)
    {
        try {
            $listing = Listing::where('slug', $slug)->first();

            if (! $listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null,
                ], 404);
            }

            // Delete associated images
            foreach ($listing->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }

            $listing->delete();

            return response()->json([
                'status' => true,
                'message' => 'Listing deleted successfully',
                'data' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting listing',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function withdraw($slug)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->whereIn('status', [0, 1]) // Only withdraw if pending/approved
            ->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or cannot be withdrawn',
                'data' => null,
            ], 404);
        }

        $listing->status = 5; // Withdrawn
        $listing->save();

        // Optional: notify bidders, log event

        return response()->json([
            'status' => true,
            'message' => 'Listing withdrawn successfully',
            'data' => $listing,
        ]);
    }

    public function relist($slug, Request $request)
    {
        $listing = Listing::where('slug', $slug)
            ->where('created_by', auth('api')->id())
            ->whereIn('status', [2, 4, 5]) // rejected, expired, withdrawn
            ->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found or cannot be re-listed',
                'data' => null,
            ], 404);
        }

        $listing->status = 1;
        $listing->is_active = 1;
        $listing->expire_at = $request->input('expire_at', now()->addDays(7)); // optional
        $listing->save();

        Bid::where('listing_id', $listing->id)->delete();
        Watchlist::where('listing_id', $listing->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Listing re-listed successfully',
            'data' => $listing,
        ]);
    }

    public function views(Request $request)
    {
        try {
            $query = ListingView::with(['listing', 'user'])
                ->orderBy('created_at', 'desc');

            // 🔒 Filter by authenticated user
            if (auth('api')->check()) {
                $query->where('user_id', auth('api')->id());
            }

            // 📌 Filter by listing ID
            if ($request->filled('listing_id')) {
                $query->where('listing_id', $request->listing_id);
            }

            $views = $query->paginate(20);

            return response()->json([
                'status' => true,
                'message' => 'Listing views fetched successfully',
                'data' => $views,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching listing views',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function approve($slug)
    {
        $listing = Listing::where('slug', $slug)->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid listing or already processed',
            ], 422);
        }

        $listing->status = 1;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing approved successfully',
        ]);
    }

    public function reject($slug)
    {
        $listing = Listing::where('slug', $slug)->first();

        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid listing or already processed',
            ], 422);
        }

        $listing->status = 2;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing rejected successfully',
        ]);
    }

    public function extendExpiry(Request $request, $listingSlug)
    {
        $validator = Validator::make($request->all(), [
            'expire_at' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 433);
        }
        $data = $validator->validated();
        $listing = Listing::where('slug', $listingSlug)->first();
        if (! $listing) {
            return response()->json([
                'status' => false,
                'message' => 'Listing not found',
            ], 402);
        }
        if ($listing->status == 3) {
            return response()->json([
                'status' => false,
                'message' => 'Listing already sold',
            ], 403);
        }
        if ($listing->created_by != auth('api')->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Only the owner will extend the expiration',
            ], 403);
        }
        $listing->expire_at = $data['expire_at'];
        $listing->status = 1;
        $listing->save();

        return response()->json([
            'status' => true,
            'message' => 'Listing expiration is increased successfully',
            'data' => $listing,
        ]);
    }

    // Tabulo apis

    public function marketplaceListing()
    {
        $base = DB::table('listings')
            ->where('listing_type', 'marketplace');

        // Listings
        $total = (clone $base)->count();

        $active = (clone $base)
            ->where('status', 1)
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            })
            ->count();

        $sold = (clone $base)
            ->where('status', 3)
            ->count();

        $expired = (clone $base)
            ->where('status', '!=', 3)
            ->where('expire_at', '<', now())
            ->count();

        // Transactions (mutually exclusive)
        $direct_buy = (clone $base)
            ->where('status', 3)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('buy_now_purchases')
                    ->whereColumn('buy_now_purchases.listing_id', 'listings.id');
            })
            ->count();

        $offers_accepted = (clone $base)
            ->where('status', 3)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('buy_now_purchases')
                    ->whereColumn('buy_now_purchases.listing_id', 'listings.id');
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('listing_offers')
                    ->whereColumn('listing_offers.listing_id', 'listings.id')
                    ->where('listing_offers.status', 'approved');
            })
            ->count();

        $bids_won = (clone $base)
            ->where('status', 3)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('buy_now_purchases')
                    ->whereColumn('buy_now_purchases.listing_id', 'listings.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('listing_offers')
                    ->whereColumn('listing_offers.listing_id', 'listings.id')
                    ->where('listing_offers.status', 'approved');
            })
            ->count();

        return response()->json([
            'listings' => [
                'total' => $total,
                'active' => $active,
                'sold' => $sold,
                'expired' => $expired,
            ],
            'transactions' => [
                'direct_buy' => $direct_buy,
                'offers_accepted' => $offers_accepted,
                'bids_won' => $bids_won,
            ],
        ]);
    }

    public function motorsListing()
    {
        $totalListings = Listing::where('listing_type', 'motors')->count();
        $totalActiveListings = Listing::where('listing_type', 'motors')
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            })
            ->count();
        $totalSoldListings = Listing::where('listing_type', 'motors')->where('status', 3)->count();
        $totalExpired = Listing::where('listing_type', 'motors')->where(function ($q) {
            $q->where('status', 4)
                ->orWhere('expire_at', '<', now());
        })->count();

        $direct_buynow = Listing::whereHas('buyNowPurchases')->where('status', 3)->count();
        $offers_accepting = Listing::whereHas('offers', function ($q) {
            $q->where('status', 'approved');
        })->where('status', 3)->count();
        $won_bids = Listing::whereHas('winningBid')->where('status', 3)->where('expire_at', '<', now())->count();

        return [
            'listings' => [
                'total' => $totalListings,
                'active' => $totalActiveListings,
                'sold' => $totalSoldListings,
                'expired' => $totalExpired,
            ],
            'transactions' => [
                'direct_buy' => $direct_buynow,
                'offers_accepted' => $offers_accepting,
                'bids_won' => $won_bids,
            ],
        ];
    }
}
