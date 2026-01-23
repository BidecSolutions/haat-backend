<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAddress;
use Illuminate\Http\Request;

class DeliveryAddressController extends Controller
{
    // List all delivery addresses
    public function index(Request $request)
    {
        try {
            $addresses = $request->user()->deliveryAddresses;

            return response()->json([
                'success' => true,
                'message' => 'Delivery addresses fetched successfully',
                'data' => $addresses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery addresses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Create delivery address
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:100',
                'delivery_address' => 'required|string|max:500',
            ]);

            $address = $request->user()->deliveryAddresses()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Delivery address created successfully',
                'data' => $address,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //  Update delivery address
    public function update(Request $request, $id)
    {
        try {
            $address = $request->user()->deliveryAddresses()->findOrFail($id);

            $validated = $request->validate([
                'name' => 'nullable|string|max:100',
                'delivery_address' => 'required|string|max:500',
            ]);

            $address->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Delivery address updated successfully',
                'data' => $address,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery address not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //  Delete delivery address
    public function destroy(Request $request, $id)
    {
        try {
            $address = $request->user()->deliveryAddresses()->findOrFail($id);
            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Delivery address deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery address not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete delivery address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
