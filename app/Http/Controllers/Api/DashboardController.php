<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\FeedbackResponse;
use App\Models\Listing;
use App\Models\Module;
use App\Models\Regions;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $now = Carbon::now();
            $sixMonthsAgo = $now->copy()->subMonths(6)->startOfMonth();

            $totalListings = Listing::count();
            $totalUsers = User::count();
            $totalRegions = Regions::count();
            $totalBlogs = Blog::count();
            $totalFeedbacks = FeedbackResponse::count();

            $moduleCounts = [];
            $modules = Module::orderBy('sort_order')->get();
            foreach ($modules as $module) {
                $categoryIds = Category::where('category_type', $module->slug)->pluck('id');
                $moduleCounts[$module->slug] = [
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'count' => Listing::whereIn('category_id', $categoryIds)->count(),
                    'is_active' => $module->is_active,
                    'icon' => $module->icon,
                ];
            }

            $reserveAuctionsCount = Listing::where('listing_type', 'reserve_auction')->count();
            $coolAuctionsCount = Listing::where('listing_type', 'cool_auction')->count();

            $recentListings = Listing::where('created_at', '>=', $now->copy()->subDays(7))->count();
            $recentUsers = User::where('created_at', '>=', $now->copy()->subDays(7))->count();

            $isSqlite = DB::connection()->getDriverName() === 'sqlite';
            $monthExpr = $isSqlite
                ? "strftime('%Y-%m', created_at)"
                : "DATE_FORMAT(created_at, '%Y-%m')";

            $listingsByMonth = Listing::select(
                DB::raw("{$monthExpr} as month"),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $sixMonthsAgo)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $usersByMonth = User::select(
                DB::raw("{$monthExpr} as month"),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $sixMonthsAgo)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $listingsByStatus = Listing::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn($item) => [$item->status => $item->count]);

            $latestListings = Listing::with(['category:id,name,slug', 'creator:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'title', 'slug', 'listing_type', 'status', 'category_id', 'created_by', 'created_at']);

            return response()->json([
                'status' => true,
                'data' => [
                    'total_listings' => $totalListings,
                    'total_users' => $totalUsers,
                    'total_regions' => $totalRegions,
                    'total_blogs' => $totalBlogs,
                    'total_feedbacks' => $totalFeedbacks,
                    'reserve_auctions_count' => $reserveAuctionsCount,
                    'cool_auctions_count' => $coolAuctionsCount,
                    'recent_listings' => $recentListings,
                    'recent_users' => $recentUsers,
                    'module_counts' => $moduleCounts,
                    'listings_by_month' => $listingsByMonth,
                    'users_by_month' => $usersByMonth,
                    'listings_by_status' => $listingsByStatus,
                    'latest_listings' => $latestListings,
                    'modules' => $modules,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
