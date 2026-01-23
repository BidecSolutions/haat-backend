<?php

namespace App\Http\Controllers;

use App\Models\Educations;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    public function index()
    {
        $educations = Educations::where('user_id', auth('api')->id())->get();
        if($educations->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No education records found.',
                'data' => [],
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'All Educations fetched.',
            'data' => $educations
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'education_provider' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'currently_studying' => 'nullable|in:0,1',
            'status' => 'nullable|in:0,1',
        ]);

        $education = Educations::create(array_merge(
            $validated,
            ['user_id' => auth('api')->id(), 'status' => $request->status ?? 1]
        ));

        return response()->json([
            'status' => true,
            'message' => 'Education added successfully.',
            'data' => $education
        ]);
    }

    public function update(Request $request, $id)
    {
        $education = Educations::where('user_id', auth('api')->id())->findOrFail($id);

        $validated = $request->validate([
            'education_provider' => 'sometimes|string|max:255',
            'qualification' => 'sometimes|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'currently_studying' => 'boolean',
            'status' => 'nullable|in:0,1',
        ]);

        $education->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Education updated successfully.',
            'data' => $education
        ]);
    }

    public function destroy($id)
    {
        $education = Educations::where('user_id', auth('api')->id())->findOrFail($id);
        $education->delete();

        return response()->json(['success' => true, 'message' => 'Education deleted.']);
    }
}
