<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentReceivedMail;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Mail;

/**
 * Email transaksi (fitur 13). Mailable-nya ShouldQueue, jadi dikirim lewat queue database.
 */
class SendOrderEmails
{
    public function placed(OrderPlaced $event): void
    {
        Mail::to($event->order->user)->queue(new OrderPlacedMail($event->order));
    }

    public function paid(OrderPaid $event): void
    {
        Mail::to($event->order->user)->queue(new PaymentReceivedMail($event->order));
    }

    public function shipped(OrderShipped $event): void
    {
        Mail::to($event->order->user)->queue(new OrderShippedMail($event->order));
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderPlaced::class => 'placed',
            OrderPaid::class => 'paid',
            OrderShipped::class => 'shipped',
        ];
    }
}
