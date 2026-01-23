@extends('emails.notifications.layout')

@section('content')
    @if (isset($is_restore) && $is_restore)
        {{-- Account Restoration Email --}}
        <div class="greeting">Hello {{ $user->name ?? 'User' }},</div>
        
        <div class="message">
            <p>We received a request to restore your Ma3rood account. Please use the code below to complete the process.</p>
            <p>Your account restoration code is:</p>
            <h2 style="margin: 20px 0; color: #667eea;">{{ $code }}</h2>
            <p>This code will expire in 30 minutes.</p>
        </div>

        <div class="message">
            <p>If you did not request to restore your account, please ignore this email and your account will remain inactive.</p>
        </div>
    @else
        {{-- Standard Verification Email --}}
        <div class="greeting">Hello {{ $user->name ?? 'User' }},</div>
        <div class="message">
            <p>Your login verification code is:</p>
            <h2 style="margin: 20px 0; color: #667eea;">{{ $code }}</h2>
            <p>This code will expire in 30 minutes.</p>
        </div>
        <div class="message">
            <p>If you didn’t request this login, please ignore this email.</p>
        </div>
    @endif
@endsection
