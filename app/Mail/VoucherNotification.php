<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $guest;
    public $qrCodeBase64;

    /**
     * Create a new message instance.
     */
    public function __construct(Guest $guest, string $qrCodeBase64)
    {
        $this->guest = $guest;
        $this->qrCodeBase64 = $qrCodeBase64;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Voucher Spesial Untuk Anda!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.voucher', // Nama file Blade template
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
