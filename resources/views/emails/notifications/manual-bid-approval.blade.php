@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>Your listing received a bid that did not meet the reserve price:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $listing->title }}</h3>
            <p style="margin: 0; color: #666;">You can choose to accept or reject this bid manually.</p>
        </div>
        
        <p>Click the button below to review the bid:</p>
    </div>
    
    <a href="{{ $url }}" class="button">Review the Bid</a>
    
    <div class="message">
        <p>Thank you for using Ma3rood!</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
@endsection

