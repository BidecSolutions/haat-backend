<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public function index()
    {
        try {
            $methods = ShippingMethod::where('status', 1)->get();

            return response()->json([
                'status' => true,
                'message' => 'Shipping methods fetched successfully',
                'data' => $methods
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching shipping methods',
                'data' => $e->getMessage()
            ], 500);
        }
    }
}

