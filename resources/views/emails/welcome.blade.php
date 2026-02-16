@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $user->name }},</div>
    
    <div class="message">
        <p>🎉 Welcome to <strong>Haat</strong>! We're excited to have you join our marketplace community.</p>

        <p>Here’s what you can do with your new account:</p>

        <ul>
            <li>🛍️ Browse and bid on amazing items</li>
            <li>📦 List your own items for sale</li>
            <li>💰 Make offers on listings</li>
            <li>👀 Watch items you’re interested in</li>
            <li>📱 Get notified about bids and offers</li>
        </ul>

        <p>Ready to get started? Click the button below to explore our marketplace:</p>
    </div>

    <a href="{{ config('app.frontend_url') }}" class="button">Start Browsing</a>

    <div class="message">
        <p>If you have any questions or need help, don’t hesitate to contact our support team.</p>

        <p>Happy bidding!</p>

        <p>Best regards,<br>The Haat Team</p>
    </div>
@endsection
