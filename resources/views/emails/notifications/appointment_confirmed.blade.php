@extends('emails.notifications.layout')

@section('content')
    <p class="greeting">Hello {{ $buyer->name }},</p>

    <p class="message">
        Your appointment for <strong>{{ $listing->title }}</strong> has been
        <span style="color: green; font-weight: bold;">confirmed</span>.
    </p>

    <ul style="margin: 20px 0; padding-left: 20px; list-style: disc; color: #555;">
        <li style="margin-bottom: 10px;">
            <strong>Date:</strong> {{ $appointment->appointment_date }}
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Time:</strong> {{ $appointment->appointment_time }}
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Location:</strong> {{ $appointment->address }}
        </li>
        @if($appointment->latitude && $appointment->longitude)
            <li style="margin-bottom: 10px;">
                <strong>Map:</strong>
                <a href="https://www.google.com/maps?q={{ $appointment->latitude }},{{ $appointment->longitude }}" target="_blank">
                    View on Google Maps
                </a>
            </li>
        @endif
    </ul>

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.frontend_url') }}/appointments" class="button">
            View Appointment
        </a>
    </div>

    <p class="message">Thank you for using Ma3rood!</p>
@endsection
