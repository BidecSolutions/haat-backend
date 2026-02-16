<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuctionSoldNotification extends Notification
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
        $winningBid = $this->listing->bids()
            ->with([
                'user.countries',
                'user.regions',
                'user.governorates',
                'user.cities',
            ])
            ->orderByDesc('amount')
            ->first();

        return (new MailMessage)
            ->subject('Your Listing Has Been Sold! - Haat')
            ->view('emails.notifications.auction-sold', [
                'notifiable' => $notifiable,
                'listing' => $this->listing,
                'winningBid' => $winningBid,
                'subject' => 'Your Listing Has Been Sold!',
            ])
            ->greeting('')
            ->salutation('');
    }

    public function toDatabase($notifiable): array
    {
        $winningBid = $this->listing->bids()->orderByDesc('amount')->first();

        return [
            'title' => 'Your listing was sold!',
            'message' => "Listing '{$this->listing->title}' sold for \${$winningBid->amount} to {$winningBid->user->name}.",
            'listing_id' => $this->listing->id,
            'amount' => $winningBid->amount,
        ];
    }
}
