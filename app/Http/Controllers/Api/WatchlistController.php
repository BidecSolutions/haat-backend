<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Watchlist;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    /**
     * ⭐ Add to Watchlist (Universal)
     *
     * @param  string  $slug
     */
    public function store($slug, Request $request)
    {
        $type = $request->input('type', 'listing');
        try {
            $userId = auth('api')->id();

           
                $item = Listing::where('slug', $slug)->first();
                if (! $item) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Listing not found',
                    ], 404);
                }

                $watch = Watchlist::firstOrCreate([
                    'user_id' => $userId,
                    'listing_id' => $item->id,
                    'type' => 'listing',
                ]);
            

            return response()->json([
                'status' => true,
                'message' => 'Added to watchlist',
                'data' => $watch,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error adding to watchlist',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ❌ Remove from Watchlist
     */
    public function destroy($slug, Request $request)
    {
        $type = $request->input('type', 'listing');
        try {
            $userId = auth('api')->id();

            
                $item = Listing::where('slug', $slug)->first();
                if (! $item) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Listing not found',
                    ], 404);
                }

                $deleted = Watchlist::where([
                    'user_id' => $userId,
                    'listing_id' => $item->id,
                    'type' => 'listing',
                ])->delete();
            

            return response()->json([
                'status' => true,
                'message' => $deleted ? 'Removed from watchlist' : 'Item not found in watchlist',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error removing from watchlist',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $userId = auth('api')->id();
            $perPage = $request->input('per_page');
            $page = $request->input('page');
            $watchlist = Watchlist::with('listing.images', 'listing.category')
                ->where('user_id', $userId);
            if($request->input('type')){
                $watchlist = $watchlist->where('type', $request->input('type'));
            }
            $watchlist = $watchlist
                ->latest()
                ->paginate(20);

            return response()->json([
                'status' => true,
                'message' => 'Watchlist fetched successfully',
                'data' => $watchlist,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching watchlist',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
}
