@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>⏰ The auction for the following listing is ending soon:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $listing->title }}</h3>
            <p style="margin: 0; color: #666;">Time remaining: {{ $timeLeft }}</p>
            <p style="margin: 0; color: #666;">Current highest bid: <img src="http://ma3rood.datainovate.com/backend/public/images/RialSignn.png" 
            alt="SAR" 
            width="14" 
            height="14" 
            style="vertical-align:middle;">{{ number_format($currentHighestBid ?? 0, 2) }}</p>
        </div>
        
        <p>Don't miss out on this opportunity! Place your bid now.</p>
    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $listing->slug }}" class="button">Place Your Bid Now</a>
    
    <div class="message">
        <p>Thank you for using Ma3rood!</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
@endsection
