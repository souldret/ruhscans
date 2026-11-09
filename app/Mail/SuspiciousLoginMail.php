<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspiciousLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $ip) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Hesabında art arda hatalı giriş denemesi');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.suspicious-login');
    }
}
