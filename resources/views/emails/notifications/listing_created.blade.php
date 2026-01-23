@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $user->name ?? 'User' }},</div>

    <div class="message">
        <p>🎉 Your new listing has been <strong>created successfully</strong> on <strong>Ma3rood</strong>!</p>
        <p>Here are the details of your listing:</p>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Title</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ $listing->title }}</td>
        </tr>
        @if(!empty($listing->subtitle))
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Subtitle</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ $listing->subtitle }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Listing Type</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ ucfirst($listing->listing_type) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Condition</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ ucfirst($listing->condition) }}</td>
        </tr>
        @if($listing->buy_now_price)
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Buy Now Price</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;"><img src="http://ma3rood.datainovate.com/backend/public/images/RialSignn.png" 
            alt="SAR" 
            width="14" 
            height="14" 
            style="vertical-align:middle;">
            {{ number_format($listing->buy_now_price, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Category</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ $listing->category->name ?? 'N/A' }}</td>
        </tr>
        @if($listing->address)
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Address</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ $listing->address }}</td>
        </tr>
        @endif
        @if($listing->expire_at)
        <tr>
            <td style="padding: 10px; border: 1px solid #e9ecef; font-weight: bold;">Expires On</td>
            <td style="padding: 10px; border: 1px solid #e9ecef;">{{ \Carbon\Carbon::parse($listing->expire_at)->format('M d, Y h:i A') }}</td>
        </tr>
        @endif
    </table>

    <div class="message">
        <p>You can view or manage your listing using the button below:</p>
    </div>
    @php
    $marketplaceurl = config('app.frontend_url') . '/marketplace/' . $listing->category->name . '/' . $listing->slug;
    $motorsurl = config('app.frontend_url') . '/motors/' . $listing->slug;
    @endphp

    @if ($listing->listing_type === 'marketplace')
        <a href="{{ $marketplaceurl }}" class="button">View Listing</a>
    @endif
    @if ($listing->listing_type === 'motors')
        <a href="{{ $motorsurl }}" class="button">View Listing</a>
    @endif
    <div class="message">
        <p>Thank you for choosing <strong>Ma3rood</strong> — we’re excited to help your listing reach potential buyers!</p>
    </div>
@endsection
