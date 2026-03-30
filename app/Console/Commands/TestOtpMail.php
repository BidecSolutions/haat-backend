<?php

namespace App\Console\Commands;

use App\Mail\VerificationMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestOtpMail extends Command
{
    protected $signature = 'mail:otp-test {email : Email to send test OTP}';
    protected $description = 'Send a test OTP email to verify mail configuration';

    public function handle(): int
    {
        $email = $this->argument('email');
        $code = (string) random_int(100000, 999999);

        $this->info('Mail config: ' . config('mail.default'));
        $this->line('  From: ' . config('mail.from.address'));
        $this->line('  Host: ' . (config('mail.default') === 'smtp' ? config('mail.mailers.smtp.host') : 'N/A'));

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log - OTP will be written to storage/logs/laravel.log, NOT sent to inbox!');
            $this->line('To receive emails in inbox, set MAIL_MAILER=smtp and configure SMTP in .env');
        }

        $user = User::where('email', $email)->first() ?? new User(['email' => $email, 'name' => 'Test User']);

        try {
            Mail::to($email)->send(new VerificationMail($user, $code));
            $this->info("OTP email sent to {$email}");
            $this->line("Test OTP code: {$code}");

            if (config('mail.default') === 'log') {
                $this->newLine();
                $this->line('Check: storage/logs/laravel.log for the full email content.');
            }
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            $this->line('Check .env: MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD');
            return 1;
        }
    }
}
