@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $user->name ?? 'User' }},</div>
    
    <div class="message">
        <p>Your login verification code is:</p>
        <h2 style="margin: 20px 0; color: #667eea;">{{ $code }}</h2>
        <p>This code will expire in 30 minutes.</p>
    </div>

    <div class="message">
        <p>If you didn’t request this login, please ignore this email.</p>
    </div>
@endsection
