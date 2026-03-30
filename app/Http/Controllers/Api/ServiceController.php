<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ListingController;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Create a service listing (stored as listing with listing_type=services).
     * Merges required fields and delegates to ListingController::store.
     */
    public function store(Request $request)
    {
        $request->merge([
            'listing_type' => 'services',
            'condition' => 'not_applicable',
            'pickup_option' => 1,
        ]);

        return app(ListingController::class)->store($request);
    }
}
