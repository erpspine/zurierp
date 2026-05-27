<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanyUserPasswordResetMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly Company $company,
        public readonly CompanyUser $user,
        public readonly string $plainPassword,
        public readonly string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Zuri ERP Password Was Reset',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-user-password-reset',
        );
    }
}
