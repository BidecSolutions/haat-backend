@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        @if($role === 'buyer')
            <p>🎉 Congratulations! You have successfully purchased the following listing:</p>
        @else
            <p>🎉 Great news! Your listing has been sold:</p>
        @endif
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $listing->title }}</h3>
            <p style="margin: 0; color: #666;">Price: <img src="http://Haat.datainovate.com/backend/public/images/RialSignn.png" 
            alt="SAR" 
            width="14" 
            height="14" 
            style="vertical-align:middle;">{{ number_format($listing->buy_now_price, 2) }}</p>
        </div>
        
        @if($role === 'buyer')
            <h4 style="margin-top: 20px;">Seller Details</h4>
            <p>Name: {{ $seller->name }}</p>
            <p>Email: {{ $seller->email }}</p>
            <p>Phone: {{ $seller->phone }}</p>
            <p>Country: {{ $seller->country_name }}</p>
            <p>Region: {{ $seller->region_name }}</p>
            <p>Governorate: {{ $seller->governorate_name }}</p>
            <p>City: {{ $seller->city_name }}</p>
        @else
            <h4 style="margin-top: 20px;">Buyer Details</h4>
            <p>Name: {{ $buyer->name }}</p>
            <p>Email: {{ $buyer->email }}</p>
            <p>Phone: {{ $buyer->phone }}</p>
            <p>Country: {{ $buyer->country_name }}</p>
            <p>Region: {{ $buyer->region_name }}</p>
            <p>Governorate: {{ $buyer->governorate_name }}</p>
            <p>City: {{ $buyer->city_name }}</p>
        @endif

    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $listing->slug }}" class="button">View Listing</a>
    
    <div class="message">
        <p>Thank you for using Haat!</p>
        
        <p>Best regards,<br>The Haat Team</p>
    </div>
@endsection

