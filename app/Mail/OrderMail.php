<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class OrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    abstract protected function subjectLine(): string;

    abstract protected function intro(): string;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        $this->order->loadMissing('items', 'user');

        return new Content(markdown: 'mail.orders.invoice', with: [
            'order' => $this->order,
            'intro' => $this->intro(),
            'showTracking' => $this->showTracking(),
        ]);
    }

    public function showTracking(): bool
    {
        return false;
    }
}
