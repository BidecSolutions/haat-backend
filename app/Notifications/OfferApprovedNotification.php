<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferApprovedNotification extends Notification
{
    public $offer;

    public function __construct($offer)
    {
        $this->offer = $offer;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // You can use 'database' or 'broadcast' too
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Offer Was Accepted 🎉')
            ->line("Your offer of {$this->offer->amount} on '{$this->offer->listing->title}' has been approved.")
            ->action('View Listing', url('/listing/' . $this->offer->listing->slug))
            ->line('Thank you for using our platform!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Offer accepted',
            'message' => "Your offer on '{$this->offer->listing->title}' was accepted!",
            'listing_id' => $this->offer->listing_id,
            'amount' => $this->offer->amount,
        ];
    }
}