<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Admin;

class AdminController extends Controller
{
    public function index()
    {
        try {
            $admins = Admin::all();

            return response()->json([
                'success' => true,
                'message' => 'Admins fetched successfully',
                'data' => $admins
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching admins: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string',
                'email'    => 'required|email|unique:admins',
                'password' => 'required|string|min:6',
                'phone'    => 'nullable|string',
                'status'   => 'nullable|in:1,2',
            ]);

            $admin = Admin::create([
                'name'       => $request->name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'phone'      => $request->phone,
                'status'     => $request->status ?? 1,
                'created_by' => auth('admin-api')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin created successfully',
                'data'    => $admin
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating admin: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Admin fetched successfully',
                'data'    => $admin
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching admin: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name'   => 'nullable|string',
                'email'  => 'nullable|email|unique:admins,email,' . $id,
                'phone'  => 'nullable|string',
                'status' => 'nullable|in:1,2',
            ]);

            $admin = Admin::findOrFail($id);
            $admin->update($request->only(['name', 'email', 'phone', 'status']));

            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully',
                'data'    => $admin
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating admin: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changeactiveInactive($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            // Toggle status
            $admin->status = $admin->status === 1 ? 2 : 1;
            $admin->save();

            return response()->json([
                'success' => true,
                'message' => 'Admin status updated successfully',
                'data' => [
                    'id' => $admin->id,
                    'status' => $admin->status,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating admin status: ' . $e->getMessage(),
            ], 500);
        }
    }


}
