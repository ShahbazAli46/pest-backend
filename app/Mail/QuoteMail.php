<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct($quote, $pdfPath)
    {
        $this->quote = $quote;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type=$this->quote->is_contracted==1?'Contract':'Quote';
        return new Envelope(
            subject: $type.' Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.quote',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $clientName = str_replace(' ', '_', $this->quote->user->name); // Replace spaces with underscores
        $type=$this->quote->is_contracted==1?'Contract':'Quote';
        $fileName = "{$clientName}_{$type}.pdf";

        return [
            Attachment::fromPath($this->pdfPath)
                ->as($fileName)
                ->withMime('application/pdf'),
        ];
    }
}
