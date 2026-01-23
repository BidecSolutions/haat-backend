<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Listing;
use Illuminate\Support\Facades\Validator;

class AdminAnalyticsController extends Controller
{
    public function topBidders()
    {
        $users = User::withCount('bids')->orderByDesc('bids_count')->take(10)->get();
        return response()->json(['status' => true, 'data' => $users]);
    }

   
    public function hotListings()
{
    $listings = Listing::with(['images']) // Eager load images
        ->withCount(['bids', 'views'])
        ->orderByDesc('bids_count')
        ->take(10)
        ->get();

    return response()->json([
        'status' => true,
        'data' => $listings
    ]);
}


}
