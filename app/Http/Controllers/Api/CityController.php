<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Defaults
        $limit = (int) $request->get('limit');
        $offset = (int) $request->get('offset');
        $search = $request->get('search');
        $regionId = $request->get('region_id');

        $query = City::with('region:id,name')
            ->when($regionId, function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%');
            });

        // Get total count (before limit/offset)
        $total = $query->count();

        // Apply pagination
        $cities = $query
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Cities retrieved successfully.',
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'data' => $cities,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'governorate_id' => $request->governorate_id ?? 1
        ]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'governorate_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $city = City::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'City created successfully.',
            'data' => $city->load('governorate'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city)
    {
        return response()->json([
            'status' => true,
            'message' => 'City retrieved successfully.',
            'data' => $city->load('region'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city)
    {
        $request->merge([
            'governorate_id' => $request->governorate_id ?? 1
        ]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'governorate_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $city->update($validator->validated());

        return response()->json(['status' => true, 'message' => 'City updated successfully.', 'data' => $city->load('region')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city)
    {
        $city->delete();

        return response()->json(['status' => true, 'message' => 'City deleted successfully.']);
    }
}
