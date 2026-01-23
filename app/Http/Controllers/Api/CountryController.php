<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Governorates;
use App\Models\Regions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Country::with('regions')->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Countries retrieved successfully.',
            'data' => $countries,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $country = Country::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Country created successfully.',
            'data' => $country,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return response()->json([
            'status' => true,
            'message' => 'Country retrieved successfully.',
            'data' => $country->load('regions'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name,' . $country->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $country->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Country updated successfully.',
            'data' => $country,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $country->delete();
        return response()->json([
            'status' => true,
            'message' => 'Country deleted successfully.'
        ]);
    }

    public function list(Request $request)
    {
        $country_id = $request->input('country_id');
        $region_id = $request->input('regions_id');
        $governorate_id = $request->input('governorates_id');

        // CASE 1: If governorate_id is provided → fetch governorate + cities
        if ($governorate_id) {
            $governorates = Governorates::with('cities', 'region.country')
                ->where('id', $governorate_id)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Governorate data retrieved successfully.',
                'data' => [
                    'governorates' => $governorates,
                ],
            ]);
        }

        // CASE 2: If region_id is provided → fetch region + governorates + cities
        if ($region_id) {
            $regions = Regions::with('governorates.cities', 'country')
                ->where('id', $region_id)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Region data retrieved successfully.',
                'data' => [
                    'regions' => $regions,
                ],
            ]);
        }

        // CASE 3: If country_id is provided → fetch that country + regions + governorates + cities
        if ($country_id) {
            $countries = Country::with('regions.governorates.cities')
                ->where('id', $country_id)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Country data retrieved successfully.',
                'data' => [
                    'countries' => $countries,
                ],
            ]);
        }

        // CASE 4: If no filter is provided → fetch all countries + regions + governorates + cities
        $countries = Country::with('regions.governorates.cities')->get();

        return response()->json([
            'status' => true,
            'message' => 'All countries retrieved successfully.',
            'data' => [
                'countries' => $countries,
            ],
        ]);
    }
}
