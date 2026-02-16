@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>🎉 Congratulations! Your listing has been sold at auction:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $listing->title }}</h3>
            <p style="margin: 0; color: #666;">Winning bid: <img src="http://Haat.datainovate.com/backend/public/images/RialSignn.png" 
            alt="SAR" 
            width="14" 
            height="14" 
            style="vertical-align:middle;">{{ number_format($winningBid->amount, 2) }}</p>
            <p style="margin: 0; color: #666;">Winner: {{ $winningBid->user->name }}</p>
            <p style="margin: 0; color: #666;">Email: {{ $winningBid->user->email }}</p>
            <p style="margin: 0; color: #666;">Phone: {{ $winningBid->user->phone }}</p>

            <p style="margin: 0; color: #666;">Country: {{ $winningBid->user->country_name }}</p>
            <p style="margin: 0; color: #666;">Region: {{ $winningBid->user->region_name }}</p>
            <p style="margin: 0; color: #666;">Governorate: {{ $winningBid->user->governorate_name }}</p>
            <p style="margin: 0; color: #666;">City: {{ $winningBid->user->city_name }}</p>
        </div>
        
        <p>Please contact the buyer soon to arrange payment and delivery details.</p>
    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $listing->slug }}" class="button">View Listing</a>
    
    <div class="message">
        <p>Thank you for using Haat!</p>
        
        <p>Best regards,<br>The Haat Team</p>
    </div>
@endsection

