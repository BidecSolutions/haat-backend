<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function adminIndex(Request $request)
    {
        // dd("Awdawd");
        try {
            // initialize so meta always has values
            $limit = null;
            $offset = null;

            $query = Category::with(['parent:id,name,slug,parent_id', 'listings'])->withCount('children as child_count', 'listings as listing_count');

            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            if ($request->filled('category_type')) {
                $query->where('category_type', $request->category_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
            }

            // clone for count before applying limit/offset
            $total = $query->count();

            // apply offset & limit only when limit is provided
            $limit = (int) $request->input('per_page', 20);

            $categories = $query->orderBy('name', 'asc')->paginate($limit);

            $categories->transform(function ($category) {
                $category->child = $category->child_count > 0;

                return $category;
            });

            return response()->json([
                'status' => true,
                'message' => 'Categories fetched successfully',
                'data' => $categories->items(),
                'pagination' => [               // <-- Your separate meta array
                    'total' => $categories->total(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            // initialize so meta always has values
            $limit = null;
            $offset = null;
            $parentCategory = null;
            $categoryPath = null;
            $categoryName = null;
            if ($request->input('name')) {
                $keyword = $request->input('name');
                $categories = Category::select('id', 'name', 'slug', 'parent_id', 'status')
                    ->where(function ($q) use ($keyword) {
                        $q->where('name', 'LIKE', "%{$keyword}%");
                    })
                    ->get();

                // Batch process to avoid memory issues
                $this->addTotalListingCounts($categories);

                return response()->json([
                    'status' => true,
                    'message' => 'Categories filtered by name',
                    'data' => $categories,
                ]);
            }

            if ($request->input('id')) {
                $id = $request->input('id');
                $category = Category::where('id', $id)->first(); // Use first() instead of get()

                if (! $category) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Category not found',
                    ], 404);
                }

                $category->total_listing_count = $category->getTotalListingsCount();

                return response()->json([
                    'status' => true,
                    'message' => 'Category fetched by ID',
                    'data' => $category,
                ]);
            }

            $query = Category::with(['parent:id,name,slug,parent_id'])
                ->withCount('children as child_count', 'listings as listing_count');

            if ($request->filled('parent_id')) {
                $parentCategory = Category::where('id', $request->parent_id)->first();
                $query->where('parent_id', $request->parent_id);
                if ($parentCategory) {
                    // Full breadcrumb path

                    $path = [];
                    $c = $parentCategory;
                    while ($c) {
                        array_unshift($path, $c->name);
                        $c = $c->parent;
                    }
                    $categoryPath = implode(' > ', $path);
                    $categoryName = $parentCategory->name;
                }
            } else {
                $query->whereNull('parent_id');
            }

            if ($request->filled('category_type')) {
                $query->where('category_type', $request->category_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // clone for count before applying limit/offset
            $total = $query->count();

            // apply offset & limit only when limit is provided
            if ($request->filled('limit')) {
                $limit = (int) $request->get('limit', 20);
                $offset = (int) $request->get('offset', 0);
                $query->skip($offset)->take($limit);
            }

            $categories = $query->orderBy('name', 'asc')->get();

            // Transform categories
            $categories->transform(function ($category) {
                $category->child = $category->child_count > 0;

                // We'll calculate total listings in batch
                return $category;
            });

            // Batch calculate total listing counts
            $this->addTotalListingCounts($categories);

            return response()->json([
                'status' => true,
                'message' => 'Categories fetched successfully',
                'data' => $categories,
                'meta' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                ],
                'parent_category' => $parentCategory,
                'categoryPath' => $categoryPath,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    // Helper method to batch calculate listing counts
    protected function addTotalListingCounts($categories)
    {
        // Get all category IDs
        $categoryIds = $categories->pluck('id')->toArray();

        // Pre-calculate descendant IDs for all categories
        $descendantMap = [];
        foreach ($categories as $category) {
            // Use the iterative query approach
            $descendantMap[$category->id] = $category->getAllDescendantIdsUsingQuery();
        }

        // Flatten all descendant IDs
        $allDescendantIds = collect($descendantMap)->flatten()->unique()->toArray();

        // Get listing counts for all descendant categories at once
        $listingCounts = Listing::whereIn('category_id', $allDescendantIds)
            ->where('status', 1)
            ->where('expire_at', '>', now())
            // ->where('status', '!=', 4)
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->pluck('count', 'category_id');
        // Calculate total counts for each category
        foreach ($categories as $category) {
            $totalCount = 0;
            foreach ($descendantMap[$category->id] as $descendantId) {
                $totalCount += $listingCounts[$descendantId] ?? 0;
            }
            $category->total_listing_count = $totalCount;
        }
    }

    public function tree(Request $request)
    {
        try {
            if ($request->input('category_type')) {
                $categories = Category::with('childrenRecursive', 'parent:id,name,slug,parent_id')
                    ->where('category_type', $request->input('category_type'))
                    ->orderBy('order')
                    ->get();
            } else {
                $categories = Category::with('childrenRecursive')
                    ->whereNull('parent_id')
                    ->orderBy('order')
                    ->get();
            }

            return response()->json([
                'status' => true,
                'message' => 'Category tree fetched successfully',
                'data' => $categories,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function all()
    {
        try {
            $categories = Category::with('childrenRecursive')
                ->orderBy('order')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'All Category fetched successfully',
                'data' => $categories,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $category = Category::with('parent:id,name,slug,parent_id', 'listings')->where('slug', $slug)->first();

            if (! $category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Category fetched',
                'data' => $category,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories',
                'description' => 'nullable|string',
                'category_type' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'icon' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
                'order' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'schema' => 'nullable|json',
                'canonical_url' => 'nullable|url',
                'focus_keywords' => 'nullable|string',
                'redirect_301' => 'nullable|url',
                'redirect_302' => 'nullable|url',
                'image_path_alt_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('image')) {
                $path = 'categories/images';
                if (! Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->makeDirectory($path, 0775, true);
                }
                $data['image_path'] = $request->file('image')->store($path, 'public');
                $data['image_path_name'] = $request->file('image')->getClientOriginalName();
            }

            if ($request->hasFile('icon')) {
                $path = 'categories/icons';
                if (! Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->makeDirectory($path, 0775, true);
                }
                $data['icon'] = $request->file('icon')->store($path, 'public');
            }

            $data['schema'] = $request->input('schema'); // Store as JSON string
            $data['created_by'] = auth('admin-api')->id();

            $category = Category::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully',
                'data' => $category,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories,slug,'.$id,
                'description' => 'nullable|string',
                'category_type' => 'sometimes|required|string|max:255',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'icon' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:1024',
                'order' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'schema' => 'nullable|json',
                'canonical_url' => 'nullable|url',
                'focus_keywords' => 'nullable|string',
                'redirect_301' => 'nullable|url',
                'redirect_302' => 'nullable|url',
                'image_path_alt_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Replace old image
            if ($request->hasFile('image')) {
                if ($category->image_path) {
                    Storage::disk('public')->delete($category->image_path);
                }
                $path = 'categories/images';
                Storage::disk('public')->makeDirectory($path, 0775, true, true);
                $data['image_path'] = $request->file('image')->store($path, 'public');
                $data['image_path_name'] = $request->file('image')->getClientOriginalName();
            }

            // Replace old icon
            if ($request->hasFile('icon')) {
                if ($category->icon) {
                    Storage::disk('public')->delete($category->icon);
                }
                $path = 'categories/icons';
                Storage::disk('public')->makeDirectory($path, 0775, true, true);
                $data['icon'] = $request->file('icon')->store($path, 'public');
            }

            // Keep raw schema string
            $data['schema'] = $request->input('schema');

            $category->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully',
                'data' => $category,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ], 404);
            }

            $category->status = ! $category->status;
            $category->save();

            return response()->json([
                'status' => true,
                'message' => 'Category status updated',
                'data' => [
                    'id' => $category->id,
                    'status' => $category->status,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ], 404);
            }

            /* function deletesubcategory($id)
            {
                $subcategory = Category::where('parent_id', $id)->get();
                foreach ($subcategory as $sub) {
                    deletesubcategory($sub->id);

                    if ($sub->image_path) {
                        Storage::disk('public')->delete($sub->image_path);
                    }

                    if ($sub->icon) {
                        Storage::disk('public')->delete($sub->icon);
                    }
                    $sub->delete();
                }
            }*/
            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully',
                'data' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSubcategories($id)
    {
        // Get the category model
        $category = Category::find($id);
        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        // Recursive function to delete all subcategories and their listings
        $deleteRecursive = function ($parentId) use (&$deleteRecursive) {
            $subcategories = Category::where('parent_id', $parentId)->get();

            foreach ($subcategories as $sub) {
                // Delete listings belonging to this subcategory
                Listing::where('category_id', $sub->id)->delete();

                // Recursively delete its subcategories
                $deleteRecursive($sub->id);

                // Finally, delete this subcategory itself
                $sub->delete();
            }
        };

        // Run recursive deletion for all children
        $deleteRecursive($id);

        // Finally delete the main category
        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'All subcategories and their listings have been deleted successfully.',
        ], 200);
    }
}
