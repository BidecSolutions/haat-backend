<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Login Verification Code - HAAT',
        );
    }

    public function build()
    {
        return $this->subject('Your Login Verification Code - HAAT')
            ->view('emails.verification')
            ->with([
                'user' => $this->user,
                'code' => $this->code,
            ]);
    }
}
