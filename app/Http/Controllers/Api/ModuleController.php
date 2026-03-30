<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Module;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Module::query()->orderBy('sort_order');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('slug', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $modules = $query->get()->map(function ($module) {
                $categoryIds = Category::where('category_type', $module->slug)->pluck('id');
                $module->listings_count = Listing::whereIn('category_id', $categoryIds)->count();
                return $module;
            });

            return response()->json([
                'status' => true,
                'message' => 'Modules fetched successfully',
                'data' => $modules,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch modules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:modules,slug',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['slug'] = Str::slug($data['slug']);
            $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

            if ($request->hasFile('image')) {
                Storage::disk('public')->makeDirectory('modules/images', 0775, true);
                $data['image'] = $request->file('image')->store('modules/images', 'public');
            }

            $module = Module::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Module created successfully',
                'data' => $module,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create module',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $module = Module::findOrFail($id);
            $categoryIds = Category::where('category_type', $module->slug)->pluck('id');
            $module->listings_count = Listing::whereIn('category_id', $categoryIds)->count();

            return response()->json([
                'status' => true,
                'data' => $module,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Module not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $module = Module::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|required|string|max:255|unique:modules,slug,' . $id,
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            if (isset($data['slug'])) {
                $data['slug'] = Str::slug($data['slug']);
            }
            if (isset($data['is_active'])) {
                $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            if ($request->hasFile('image')) {
                if ($module->image) {
                    Storage::disk('public')->delete($module->image);
                }
                Storage::disk('public')->makeDirectory('modules/images', 0775, true);
                $data['image'] = $request->file('image')->store('modules/images', 'public');
            }

            if ($request->has('remove_image') && $request->boolean('remove_image')) {
                if ($module->image) {
                    Storage::disk('public')->delete($module->image);
                }
                $data['image'] = null;
            }

            unset($data['remove_image']);
            $module->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Module updated successfully',
                'data' => $module->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update module',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $module = Module::findOrFail($id);
            $module->is_active = !$module->is_active;
            $module->save();

            return response()->json([
                'status' => true,
                'message' => 'Module status toggled successfully',
                'data' => $module,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to toggle module status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $module = Module::findOrFail($id);

            if ($module->image) {
                Storage::disk('public')->delete($module->image);
            }

            $module->delete();

            return response()->json([
                'status' => true,
                'message' => 'Module deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete module',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function active()
    {
        try {
            $modules = Module::active()->orderBy('sort_order')->get();

            return response()->json([
                'status' => true,
                'data' => $modules,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch active modules',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
