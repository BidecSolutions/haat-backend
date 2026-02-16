@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>🎉 Great news! Your offer has been approved for the following listing:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $offer->listing->title }}</h3>
            <p style="margin: 0; color: #666;">Approved offer: <img src="http://Haat.datainovate.com/backend/public/images/RialSignn.png" 
            alt="SAR" 
            width="14" 
            height="14" 
            style="vertical-align:middle;">{{ number_format($offer->amount, 2) }}</p>
        </div>
        
        <p>The seller will contact you soon to arrange payment and delivery details.</p>
    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $offer->listing->slug }}" class="button">View Listing</a>
    
    <div class="message">
        <p>Thank you for using Haat!</p>
        
        <p>Best regards,<br>The Haat Team</p>
    </div>
@endsection

