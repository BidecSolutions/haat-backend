<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    //login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid credentials"
            ], 401);
        }

        // Generate token (no expiry)
        $token = $admin->createToken('admin-token', ['*'])->plainTextToken;
        
        $roles = $admin->getRoleNames(); // already strings
        $permissions = $admin->getAllPermissions()->map(function ($perm) {
            return [
                'name' => $perm->name,
                'permission_name' => $perm->permission_name,
                'module_name' => $perm->module_name,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => "Login Successfully",
            'data' => $admin,
            'token' => $token,
            // 'roles' => $roles,
            // 'permissions' => $permissions,
        ], 200);
    }

    // Admin Profile
    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => "Successfully Fetched",
            'data' => $request->user(),
        ], 200);
    }
    // update
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:admins,email,' . $admin->id
        ]);

        $admin->update($request->all());

        return response()->json([
            'status' => true,
            'message' => "Admin updated successfully",
            'data' => $admin
        ], 200);
    }
    // Change Password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }

        $admin->password = Hash::make($request->new_password);
        $admin->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
        ], 200);
    }

    // logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => "Logged out"
        ], 200);
    }
}

