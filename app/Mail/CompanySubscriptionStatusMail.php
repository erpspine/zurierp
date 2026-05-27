<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanySubscriptionStatusMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $statusType,
        public readonly string $loginUrl,
        public readonly ?int $daysRemaining = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->statusType) {
                'cancelled' => 'Your Zuri ERP Subscription Has Been Cancelled',
                'expired' => 'Your Zuri ERP Subscription Has Expired',
                default => 'Your Zuri ERP Subscription Is Expiring Soon',
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-subscription-status',
        );
    }
}
