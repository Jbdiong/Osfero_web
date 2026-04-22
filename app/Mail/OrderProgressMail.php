<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderProgressTrack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OrderProgressMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $track;
    public $content;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, OrderProgressTrack $track, string $content)
    {
        $this->order = $order;
        $this->track = $track;
        $this->content = $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Progress Update: Order #' . $this->order->id . ' - ' . $this->track->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-progress',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->track->attachments as $file) {
            if (Storage::disk('public')->exists($file->file_path)) {
                $attachments[] = Attachment::fromPath(Storage::disk('public')->path($file->file_path))
                    ->as($file->file_name ?? basename($file->file_path));
            }
        }

        return $attachments;
    }
}
