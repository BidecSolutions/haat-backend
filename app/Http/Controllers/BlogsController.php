<?php

namespace App\Http\Controllers;

use App\Enums\BlogsType;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlogsController extends Controller
{
    /**
     * Display all blogs (list) with optional type filter
     */
    public function index(Request $request)
    {
        $query = Blog::with(['creator', 'updator'])->latest();

        // Filter by type if provided
        if ($request->has('type') && in_array($request->type, BlogsType::values())) {
            $query->where('type', $request->type);
        }

        $blogs = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Blogs retrieved successfully',
            'data' => $blogs,
        ], 200);
    }

    /**
     * Store a newly created blog
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'nullable|string|in:'.implode(',', BlogsType::values()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'data' => $validator->errors(),
            ], 422);
        }

        $directory = 'listings/images';
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0775, true);
        }

        $blog = new Blog;

        if ($request->hasFile('blog_image')) {
            $image = $request->file('blog_image');
            $path = $image->store($directory, 'public');
            $blog->blog_image = $path;
        }

        $blog->title = $request->input('title');
        $blog->description = $request->input('description');
        $blog->type = $request->input('type', BlogsType::HOME->value); // default to HOME enum value
        $blog->created_by = auth('admin-api')->id();
        $blog->updated_by = auth('admin-api')->id();
        $blog->save();

        return response()->json([
            'status' => true,
            'message' => 'Blog created successfully',
            'data' => $blog,
        ], 201);
    }

    /**
     * Show a single blog by ID
     */
    public function show($id)
    {
        $blog = Blog::with(['creator', 'updator'])->find($id);

        if (! $blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Blog retrieved successfully',
            'data' => $blog,
        ], 200);
    }

    /**
     * Update an existing blog
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (! $blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'blog_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|in:'.implode(',', BlogsType::values()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'data' => $validator->errors(),
            ], 422);
        }

        $directory = 'listings/images';
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0775, true);
        }

        // Handle image replacement
        if ($request->hasFile('blog_image')) {
            if ($blog->blog_image && Storage::disk('public')->exists($blog->blog_image)) {
                Storage::disk('public')->delete($blog->blog_image);
            }

            $image = $request->file('blog_image');
            $path = $image->store($directory, 'public');
            $blog->blog_image = $path;
        }

        $blog->title = $request->input('title', $blog->title);
        $blog->description = $request->input('description', $blog->description);
        $blog->type = $request->input('type', $blog->type ?? BlogsType::HOME->value);
        $blog->updated_by = auth('admin-api')->id();
        $blog->save();

        return response()->json([
            'status' => true,
            'message' => 'Blog updated successfully',
            'data' => $blog,
        ], 200);
    }

    /**
     * Delete a blog
     */
    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (! $blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        if ($blog->blog_image && Storage::disk('public')->exists($blog->blog_image)) {
            Storage::disk('public')->delete($blog->blog_image);
        }

        $blog->delete();

        return response()->json([
            'status' => true,
            'message' => 'Blog deleted successfully',
        ], 200);
    }
}
