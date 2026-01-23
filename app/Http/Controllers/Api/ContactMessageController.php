<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'subject'      => 'nullable|string|max:255',
            'message'      => 'required|string|max:1000',
        ]);

        $contact = ContactMessage::create($request->only([
            'name', 'email', 'phone_number', 'subject', 'message'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Message sent successfully!',
            'data' => $contact
        ]);
    }

    // Optional: for admin to view
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Messages retrieved successfully!',
            'data' => $messages
        ]);
    }
}
