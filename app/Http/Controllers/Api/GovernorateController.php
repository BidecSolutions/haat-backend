<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Governorates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GovernorateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Governorates::with('region')->latest();

        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $governorates = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Governorates retrieved successfully.',
            'data' => $governorates,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $governorate = Governorates::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Governorate created successfully.',
            'data' => $governorate->load('region'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Governorates $governorate)
    {
        return response()->json([
            'status' => true,
            'message' => 'Governorate retrieved successfully.',
            'data' => $governorate->load('region'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Governorates $governorate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $governorate->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Governorate updated successfully.',
            'data' => $governorate->load('region'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Governorates $governorate)
    {
        $governorate->delete();
        return response()->json(['status' => true, 'message' => 'Governorate deleted successfully.']);
    }
}
