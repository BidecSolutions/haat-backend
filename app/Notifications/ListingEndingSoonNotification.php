<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Listing;

class ListingEndingSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $listing;

    /**
     * Create a new notification instance.
     */
    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    /**
     * Get the notification's delivery channels.
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
        $timeLeft = now()->diffForHumans($this->listing->end_date, true);
        
        return (new MailMessage)
            ->subject('Auction Ending Soon! - Ma3rood')
            ->view('emails.notifications.listing-ending-soon', [
                'notifiable' => $notifiable,
                'listing' => $this->listing,
                'timeLeft' => $timeLeft,
                'subject' => 'Auction Ending Soon!'
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Auction Ending Soon',
            'message' => "The auction for '{$this->listing->title}' is ending soon!",
            'listing_id' => $this->listing->id,
            'listing_title' => $this->listing->title,
            'end_date' => $this->listing->end_date,
        ];
    }

    /**
     * Store data in database notification.
     */
    public function toDatabase($notifiable): array
    {
        $timeLeft = now()->diffForHumans($this->listing->end_date, true);
        
        return [
            'title' => 'Auction Ending Soon',
            'message' => "The auction for '{$this->listing->title}' is ending in {$timeLeft}!",
            'listing_id' => $this->listing->id,
            'listing_title' => $this->listing->title,
            'end_date' => $this->listing->end_date,
            'action_url' => url("/listings/{$this->listing->slug}"),
        ];
    }
}
