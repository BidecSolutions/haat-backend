<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Listing;

class ManualBidApprovalRequiredNotification extends Notification implements ShouldQueue
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
        $url = url('/seller/listings/' . $this->listing->id . '/review-bid');

        return (new MailMessage)
            ->subject('Manual Approval Required for Bid - Ma3rood')
            ->view('emails.notifications.manual-bid-approval', [
                'notifiable' => $notifiable,
                'listing' => $this->listing,
                'url' => $url,
                'subject' => 'Manual Approval Required for Bid'
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
    }

    /**
     * Store data in database notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Manual Bid Approval Needed',
            'message' => "Your listing \"{$this->listing->title}\" received a bid below reserve. Review it.",
            'listing_id' => $this->listing->id,
            'listing_title' => $this->listing->title,
            'action_url' => url('/seller/listings/' . $this->listing->id . '/review-bid'),
        ];
    }
}
