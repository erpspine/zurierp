<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanySubscriptionActivatedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Zuri ERP Subscription Is Active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-subscription-activated',
        );
    }
}