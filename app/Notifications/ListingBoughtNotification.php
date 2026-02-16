<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingBoughtNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $listing, public $role) {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        // Load buyer (the one who bought the listing)
        $buyer = $this->listing->buyNowPurchases()
            ->with([
                'buyer.countries',
                'buyer.regions',
                'buyer.governorates',
                'buyer.cities',
            ])
            ->latest()
            ->first()?->buyer;

        // Seller = listing creator
        $seller = $this->listing->creator()
            ->with([
                'countries',
                'regions',
                'governorates',
                'cities',
            ])
            ->first();

        $subject = $this->role === 'buyer'
            ? 'Listing Purchased - Haat'
            : 'Your Listing Was Sold - Haat';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notifications.listing-bought', [
                'notifiable' => $notifiable,
                'listing' => $this->listing,
                'role' => $this->role,
                'buyer' => $buyer,
                'seller' => $seller,
                'subject' => $subject,
            ])
            ->greeting('')
            ->salutation('');
    }

    public function toDatabase($notifiable)
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'amount' => $this->listing->buy_now_price,
            'role' => $this->role,
        ];
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
}
