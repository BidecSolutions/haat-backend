<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    // public function login(Request $request)
    // {
    //     $admin = Admin::where('email', $request->email)->first();

    //     if (!$admin || !Hash::check($request->password, $admin->password)) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

    //     return response()->json(['token' => $token, 'admin' => $admin]);
    // }

    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|unique:admins',
    //         'password' => 'required|string|min:8',
    //     ]);

    //     $admin = Admin::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password), // Hash password
    //     ]);

    //     return response()->json(['message' => 'Admin created successfully', 'admin' => $admin]);
    // }

    // public function logout(Request $request)
    // {
    //     $request->user()->currentAccessToken()->delete();
    //     return response()->json(['message' => 'Admin logged out']);
    // }

    // public function profile(Request $request)
    // {
    //     return response()->json($request->user());
    // }
}
