@extends('emails.notifications.layout')

@section('content')
    <p class="greeting">Hello {{ $buyer->name }},</p>

    <p class="message">
        Unfortunately, your appointment request for <strong>{{ $listing->title }}</strong>
        has been <span style="color: red; font-weight: bold;">declined</span> by the seller.
    </p>

    <p class="message">
        You may request another time or browse other listings that match your needs.
    </p>

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.frontend_url') }}/listings" class="button">
            Browse Listings
        </a>
    </div>
@endsection
