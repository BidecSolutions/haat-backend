<?php

namespace App\Mail;

use App\Models\FeedbackResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback; // FeedbackResponse instance

    public $subject = "New Feedback Submitted on Haat";

    /**
     * Create a new message instance.
     *
     * @param FeedbackResponse $feedback
     */
    public function __construct(FeedbackResponse $feedback)
    {
        $this->feedback = $feedback;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $adminEmail = config('mail.admin_email') ?: config('mail.from.address', 'hello@example.com');

        return $this->to($adminEmail)
            ->subject($this->subject)
            ->view('emails.feedback.feedback_submitted');
    }
}
