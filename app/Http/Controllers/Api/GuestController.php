<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    //
    // app/Http/Controllers/GuestController.php
    public function generate()
    {
        $guestId = (string) Str::uuid();

        // Optionally save in DB if you want to track guests explicitly
        Guest::create(['guest_id' => $guestId]);

        return response()->json([
            'status' => true,
            'guest_id' => $guestId,
        ]);
    }
}
