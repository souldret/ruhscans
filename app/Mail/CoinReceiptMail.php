<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoinReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $heading,
        public string $detail,
        public int $balance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading.' — '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.receipt');
    }
}
