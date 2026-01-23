@extends('emails.notifications.layout')

@section('content')
    <p class="greeting">Hello {{ $listing->creator->name }},</p>

    <p class="message">
        You have received a new <strong>appointment request</strong> for your listing:
        <strong>{{ $listing->title }}</strong>.
    </p>

    <ul style="margin: 20px 0; padding-left: 20px; list-style: disc; color: #555;">
        <li style="margin-bottom: 10px;">
            <strong>Buyer:</strong> {{ $buyer->name }} ({{ $buyer->email }})
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Notes:</strong> {{ $appointment->notes ?? 'No notes provided' }}
        </li>
    </ul>

    <p class="message">Please log in to confirm or decline this request.</p>

    {{-- 🔘 Centered gradient button --}}
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.frontend_url') }}/listings/{{ $listing->slug }}" class="button">
            View Listing
        </a>
    </div>
@endsection
