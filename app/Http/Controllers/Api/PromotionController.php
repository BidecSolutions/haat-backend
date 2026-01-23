<?php

namespace App\Http\Controllers\Api;

use App\Enums\PromotionType;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    
    public function list()
    {
        $promotions = Promotion::select('id', 'title', 'description', 'image', 'redirect_type', 'redirect_url', 'internal_route', 'target')
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Promotions retrieved successfully',
            'data' => $promotions,
        ]);
    }

   
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
            'redirect_url' => 'nullable',
            'redirect_type' => 'nullable|string|in:url,app', 
            'internal_route' => 'nullable|string', 
            'target' => 'nullable|in:_self,_blank',
            'type' => 'nullable|string|in:'.implode(',', PromotionType::values()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('image');
        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active', false);

        if ($request->hasFile('image')) {
            $directory = 'promotions/images';
            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $data['image'] = $request->file('image')->store($directory, 'public');
        }

        $promotion = Promotion::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Promotion created successfully',
            'data' => $promotion,
        ], 201);
    }

    
    public function show($id)
    {
        $promotion = Promotion::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $promotion,
        ]);
    }

   
    public function update(Request $request, $id)
    {
        $promotion = Promotion::find($id);
        if (! $promotion) {
            return response()->json([
                'status' => false,
                'message' => 'Promotion not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp',
            'redirect_type' => 'nullable|string|in:url,app',
            'redirect_url' => 'nullable|string',
            'internal_route' => 'nullable|string',
            'target' => 'nullable|in:_self,_blank',
            'type' => 'nullable|string|in:'.implode(',', PromotionType::values()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($promotion->image) {
                Storage::disk('public')->delete($promotion->image);
            }
            $directory = 'promotions/images';
            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $data['image'] = $request->file('image')->store($directory, 'public');
        }

        $promotion->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Promotion updated successfully',
            'data' => $promotion,
        ]);
    }

    
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);

        // Delete the image from storage
        if ($promotion->image) {
            Storage::disk('public')->delete($promotion->image);
        }
        $promotion->delete();

        return response()->json([
            'status' => true,
            'message' => 'Promotion deleted successfully',
        ]);
    }
}
