<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Defaults
        $limit = (int) $request->get('limit') ?: 500;
        $offset = (int) $request->get('offset');
        $search = $request->get('search');
        $cityId = $request->get('city_id');
        $countryId = $request->get('country_id');
        $cityIds = $countryId && !$cityId
            ? \App\Models\City::whereIn('region_id', \App\Models\Regions::where('country_id', $countryId)->pluck('id'))->pluck('id')->toArray()
            : [];

        $query = Area::with('city')
            ->when($cityId, function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            })
            ->when(!empty($cityIds), function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'LIKE', '%'.$search.'%');
            });

        // Total count before pagination
        $total = $query->count();

        // Apply pagination
        $areas = $query
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Areas retrieved successfully.',
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'data' => $areas,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $area = Area::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Area created successfully.',
            'data' => $area->load('city'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Area $area)
    {
        return response()->json([
            'status' => true,
            'message' => 'City retrieved successfully.',
            'data' => $area->load('city'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Area $area)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $area->update($validator->validated());

        return response()->json(['status' => true, 'message' => 'Area updated successfully.', 'data' => $area->load('city')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        $area->delete();

        return response()->json(['status' => true, 'message' => 'Area deleted successfully.']);
    }
}
