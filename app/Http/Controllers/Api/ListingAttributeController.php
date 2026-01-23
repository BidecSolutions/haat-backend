<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListingAttribute;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Exception;

class ListingAttributeController extends Controller
{
    public function index()
    {
        try {
            $attributes = ListingAttribute::with('listing')->get();
            return response()->json([
                'success' => true,
                'message' => 'Attributes retrieved successfully',
                'data' => $attributes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'key'        => 'required|string|max:255',
            'value'      => 'nullable|string|max:255',
        ]);

        try {
            $attribute = ListingAttribute::create($request->all());
            return response()->json([
                'success' => true,
                'data' => $attribute
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $attribute = ListingAttribute::with('listing')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $attribute
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'key'   => 'sometimes|string|max:255',
            'value' => 'nullable|string|max:255',
        ]);

        try {
            $attribute = ListingAttribute::findOrFail($id);
            $attribute->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $attribute
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $attribute = ListingAttribute::findOrFail($id);
            $attribute->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attribute deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
