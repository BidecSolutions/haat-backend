<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 🔔 Get user's notifications
    // public function index(Request $request)
    // {
    //     $notifications = auth('api')->user()
    //         ->notifications()
    //         ->latest()
    //         ->paginate(20);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Notifications fetched successfully',
    //         'data' => $notifications
    //     ]);
    // }

    // public function index(Request $request)
    // {
    //     $user = auth('api')->user();

    //     // Fetch latest notifications
    //     $notifications = $user->notifications()->latest()->get();

    //     // Extract listing IDs
    //     $listingIds = $notifications
    //         ->filter(fn($n) => isset($n->data['listing_id']))
    //         ->pluck('data.listing_id')
    //         ->unique()
    //         ->values();

    //     // Fetch listings in one query
    //     $listings = \App\Models\Listing::whereIn('id', $listingIds)->get()->keyBy('id');

    //     // Map notifications with attached listing
    //     $transformed = $notifications->map(function ($n) use ($listings) {
    //         $data = $n->data;
    //         $listing = isset($data['listing_id']) ? $listings->get($data['listing_id']) : null;

    //         return [
    //             'id' => $n->id,
    //             'type' => $n->type,
    //             'read_at' => $n->read_at,
    //             'created_at' => $n->created_at,
    //             'data' => $data,
    //             'listing' => $listing,
    //         ];
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Notifications with listings fetched successfully',
    //         'data' => $transformed
    //     ]);
    // }

    public function index(Request $request)
    {
        $user = auth('api')->user();

        // Fetch latest notifications
        $notifications = $user->notifications()->latest()->get();

        // Extract listing IDs
        $listingIds = $notifications
            ->filter(fn($n) => isset($n->data['listing_id']))
            ->pluck('data.listing_id')
            ->unique()
            ->values();

        // Fetch listings with images
        $listings = \App\Models\Listing::with('images')
            ->whereIn('id', $listingIds)
            ->get()
            ->keyBy('id');

        // Map notifications with attached listing and its images
        $transformed = $notifications->map(function ($n) use ($listings) {
            $data = $n->data;
            $listing = isset($data['listing_id']) ? $listings->get($data['listing_id']) : null;

            return [
                'id' => $n->id,
                'type' => $n->type,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'data' => $data,
                'listing' => $listing,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Notifications with listings and images fetched successfully',
            'data' => $transformed
        ]);
    }

    public function unReadList(Request $request)
    {
        $notifications = auth('api')->user()
            ->unreadNotifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Unread notifications fetched successfully',
            'data' => $notifications
        ]);
    }


    // ✅ Mark a single notification as read
    public function markAsRead($id)
    {
        $notification = auth('api')->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found',
                'data' => null
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    // ✅ Mark all as read
    public function markAllAsRead()
    {
        auth('api')->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read',
            'data' => null
        ]);
    }

    public function unreadCount()
    {
        $count = auth('api')->user()->unreadNotifications->count();

        return response()->json([
            'status' => true,
            'message' => 'Unread count fetched',
            'data' => $count
        ]);
    }
}
