    @extends('emails.notifications.layout')

    @section('content')

        <div class="greeting">Hello {{ optional($user)->name ?? "Sir" }},</div>

        <div class="message">
            <p>You requested to reset your password on the Ma3rood App.</p>

            <p>Here is your 6-digit code:</p>

            <h2 style="font-size: 24px; font-weight: bold; letter-spacing: 3px; text-align: center;">
                {{ $code }}
            </h2>

            <p>This code will expire in 30 minutes.</p>

            <p>If you did not request this, please ignore this email.</p>
        </div>
    @endsection
