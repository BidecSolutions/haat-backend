<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;
    /**
     * Create a new message instance.
     */
    public function __construct($user, $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'App Reset Code Mail',
        );
    }

    public function build(){
        return $this->subject('Your Ma3rood Password Reset Code')
        ->view('emails.notifications.reset-code')
        ->with([
            'user' => $this->user,
            'code' => $this->code,
        ]);
    }
}
