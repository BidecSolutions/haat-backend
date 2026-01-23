<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        try {
            $methods = PaymentMethod::where('status', 1)->get();

            return response()->json([
                'status' => true,
                'message' => 'Payment methods fetched successfully',
                'data' => $methods
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching payment methods',
                'data' => $e->getMessage()
            ], 500);
        }
    }
}

