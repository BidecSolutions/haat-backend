<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Listing;

class AuctionWonNotification extends Notification
{
    use Queueable;

    public $listing;

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Congratulations! You Won the Auction - Ma3rood')
            ->view('emails.notifications.auction-won', [
                'notifiable' => $notifiable,
                'listing' => $this->listing,
                'subject' => 'Congratulations! You Won the Auction'
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
    }

    public function toDatabase($notifiable): array
    {
        $highestBid = $this->listing->bids()->orderByDesc('amount')->first();

        return [
            'title' => 'You won the auction!',
            'message' => "You won the listing: {$this->listing->title} with a bid of \${$highestBid->amount}.",
            'listing_id' => $this->listing->id,
            'amount' => $highestBid->amount,
        ];
    }
}
