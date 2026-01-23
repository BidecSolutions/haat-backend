<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reset Your Password - Ma3rood')
            ->view('emails.notifications.reset-password', [
                'notifiable' => $notifiable,
                'token' => $this->token,
                'subject' => 'Reset Your Password'
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
    }
}
