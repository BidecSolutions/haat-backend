<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\ListingReport;
use Illuminate\Support\Facades\Validator;

class ListingReportController extends Controller
{
  public function store(Request $request, $listingSlug)
    {
        try {
            $listing = Listing::where('slug', $listingSlug)->first();

            if (!$listing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Listing not found',
                    'data' => null
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $report = ListingReport::create([
                'listing_id' => $listing->id,
                'user_id' => auth('api')->id(), // can be null if anonymous reports allowed
                'reason' => $request->reason,
                'description' => $request->description,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Report submitted successfully',
                'data' => $report
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error submitting report',
                'data' => $e->getMessage()
            ], 500);
        }
    }


    // Admin can fetch reports (optional)
    public function index()
    {
        $reports = ListingReport::with(['listing', 'user'])->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Reports fetched successfully',
            'data' => $reports
        ]);
    }
}
