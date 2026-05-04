<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessages;
use Illuminate\Support\Facades\Storage;

class ContactNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public ContactMessages $contactMessage)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Inquiry:' . $this->contactMessage->subject,
            replyTo: [$this->contactMessage->email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Check if a file path was actually saved to the database
        if ($this->contactMessage->attached_file) {
            $attachments[] = Attachment::fromPath(storage_path('app/public/' . $this->contactMessage->attached_file))
                ->as('Inquiry_Attachment') // Optional: Give the file a clean name in the email
                ->withMime(Storage::mimeType('public/' . $this->contactMessage->attached_file));
        }

        return $attachments;
    }
}
