<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SmtpTestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: config('app.name', 'DiginMarket').' SMTP test');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.smtp-test');
    }
}
