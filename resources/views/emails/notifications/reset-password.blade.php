@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>You are receiving this email because we received a password reset request for your Ma3rood account.</p>
        
        <p>Click the button below to reset your password:</p>
    </div>
    
    <!-- <a href="{{ config('app.frontend_url') }}/reset-password?token={{ $token }}&email={{ urlencode($notifiable->email ?? '') }}" class="button">Reset Password</a> -->
    <a href="{{ config('app.frontend_url') }}/reset-password?token={{ $token }}&email={{ urlencode($notifiable->email ?? '') }}" class="button" >Reset Password</a>
    
    <div class="message">
        <p>This password reset link will expire in 60 minutes.</p>
        
        <p>If you did not request a password reset, no further action is required.</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
@endsection
