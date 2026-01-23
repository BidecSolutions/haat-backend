<?php

namespace App\Notifications;

use App\Models\ListingOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OfferExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;

    public function __construct(ListingOffer $offer)
    {
        $this->offer = $offer;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Offer Has Expired - Ma3rood')
            ->view('emails.notifications.offer-expired', [
                'notifiable' => $notifiable,
                'offer' => $this->offer,
                'subject' => 'Your Offer Has Expired'
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Offer Expired',
            'message' => "Your offer on '{$this->offer->listing->title}' has expired.",
            'listing_id' => $this->offer->listing_id,
            'offer_id' => $this->offer->id,
        ];
    }
}
