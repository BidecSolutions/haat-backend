<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BidPlacedNotification extends Notification
{
    use Queueable;
    protected $bid;

    /**
     * Create a new notification instance.
     */
    public function __construct($bid)
    {
        $this->bid = $bid;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Bid Placed on Your Listing - Ma3rood')
            ->view('emails.notifications.bid-placed', [
                'notifiable' => $notifiable,
                'bid' => $this->bid,
                'subject' => 'New Bid Placed on Your Listing'
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New bid placed on your listing',
            'message' => "{$this->bid->user->name} bid on your listing: {$this->bid->listing->title}",
            'listing_id' => $this->bid->listing_id,
            'amount' => $this->bid->amount
        ];
    }
}
