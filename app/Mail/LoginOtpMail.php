<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoginOtpMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $otpCode,
        public readonly string $expiresAt,
        public readonly ?string $ipAddress,
        public readonly ?string $deviceName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Login OTP Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-otp',
        );
    }
}