<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanyWelcomeCredentialsMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly Company $company,
        public readonly string $adminName,
        public readonly string $adminEmail,
        public readonly string $plainPassword,
        public readonly string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Zuri ERP - Company Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-welcome-credentials',
        );
    }
}
