<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Regions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = (int) $request->get('limit');
        $offset = (int) $request->get('offset');
        $search = $request->get('search');
        $countryId = $request->get('country_id');

        $limit = $limit > 0 ? $limit : 500;
        $countryName = $request->get('country');
        if ($countryName && !$countryId) {
            $country = \App\Models\Country::where('name', 'like', $countryName)->first();
            if ($country) {
                $countryId = $country->id;
            }
        }
        $query = Regions::latest()
            ->when($countryId, function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%');
            });
        $total = $query->count();
        $regions = $query->with('country')->limit($limit)->offset($offset)->get();
        return response()->json([
            'status' => true,
            'message' => 'Regions retrieved successfully.',
            'data' => $regions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $region = Regions::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Region created successfully.',
            'data' => $region,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Regions $region)
    {
        return response()->json([
            'status' => true,
            'message' => 'Region retrieved successfully.',
            'data' => $region->load('governorates'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Regions $region)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $region->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Region updated successfully.',
            'data' => $region,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Regions $region)
    {
        // Note: This will fail if governorates are linked.
        // You might want to add logic to delete/reassign governorates first.
        try {
            $region->delete();
            return response()->json(['status' => true, 'message' => 'Region deleted successfully.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete region. It may have associated governorates.',
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}
