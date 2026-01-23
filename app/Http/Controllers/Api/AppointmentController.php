<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    /**
     * 📌 Buyer: Create a new appointment request
     */
    public function store(Request $request){
        $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'notes'      => 'nullable|string',
        ]);

        $listing = Listing::findOrFail($request->listing_id);
        $buyer   = User::find(Auth::id());

        if (!$buyer) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        }

        $appointment = Appointment::create([
            'listing_id' => $listing->id,
            'seller_id'  => $listing->created_by,
            'buyer_id'   => $buyer->id,
            'notes'      => $request->notes,
            'status'     => 'pending',
        ]);

        $seller = User::find($listing->created_by);

        // 📧 Send email to seller about new request
        Mail::send('emails.notifications.appointment_request', [
            'appointment' => $appointment,
            'buyer'       => $buyer,
            'listing'     => $listing
        ], function($message) use ($seller) {
            $message->to($seller->email)
                    ->subject('New Appointment Request for Your Listing');
        });

        return response()->json([
            'status'  => true,
            'message' => 'Appointment request created successfully',
            'data'    => $appointment,
        ]);
    }

    /**
     * 📌 Seller: View all appointment requests for their listings
     */
    public function sellerAppointments()
    {
        $sellerId = Auth::id();
        $appointments = Appointment::with(['listing', 'buyer_id'])
            ->where('seller_id', $sellerId)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $appointments,
        ]);
    }

    /**
     * 📌 Buyer: View their appointment requests
     */
    public function buyerAppointments()
    {
        $userId = Auth::id();
        $appointments = Appointment::with(['listing', 'seller_id'])
            ->where('buyer_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $appointments,
        ]);
    }

    /**
     * 📌 Seller: Confirm an appointment with date/time & location
     */
    public function confirm($id, Request $request)
    {
        $request->validate([
            'appointment_date' => 'required|date|after:today',
            'appointment_time' => 'required',
            'address'          => 'required|string|max:255',
            'latitude'         => 'nullable|string',
            'longitude'        => 'nullable|string',
        ]);

        $sellerId    = Auth::id();
        $appointment = Appointment::where('id', $id)
            ->where('seller_id', $sellerId)
            ->firstOrFail();

        $appointment->update([
            'status'           => AppointmentStatus::Approved,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'address'          => $request->address,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
        ]);

        $buyer   = User::find($appointment->buyer_id);
        $listing = Listing::find($appointment->listing_id);

        // 📧 Send email to buyer with confirmation
        Mail::send('emails.notifications.appointment_confirmed', [
            'appointment' => $appointment,
            'buyer'       => $buyer,
            'listing'     => $listing
        ], function($message) use ($buyer) {
            $message->to($buyer->email)
                    ->subject('Your Appointment Has Been Confirmed');
        });

        return response()->json([
            'status'  => true,
            'message' => 'Appointment confirmed',
            'data'    => $appointment,
        ]);
    }
    /**
     * 📌 Seller: Decline an appointment
     */
    public function decline($id)
    {
        $sellerId    = Auth::id();
        $appointment = Appointment::where('id', $id)
            ->where('seller_id', $sellerId)
            ->firstOrFail();

        $appointment->update(['status' => AppointmentStatus::Declined]);

        $buyer   = User::find($appointment->buyer_id);
        $listing = Listing::find($appointment->listing_id);

        // 📧 Notify buyer about decline
        Mail::send('emails.notifications.appointment_declined', [
            'appointment' => $appointment,
            'buyer'       => $buyer,
            'listing'     => $listing
        ], function($message) use ($buyer) {
            $message->to($buyer->email)
                    ->subject('Your Appointment Request Was Declined');
        });

        return response()->json([
            'status'  => true,
            'message' => 'Appointment declined',
        ]);
    }

    /**
     * 📌 Mark appointment as completed
     */
    public function complete($id)
    {
        $userId = Auth::id();
        $appointment = Appointment::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('seller_id', $userId)
                  ->orWhere('buyer_id', $userId);
            })
            ->firstOrFail();

        $appointment->update(['status' => 'completed']);

        return response()->json([
            'status'  => true,
            'message' => 'Appointment marked as completed',
            'data'    => $appointment,
        ]);
    }
}
