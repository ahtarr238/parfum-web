<?php

namespace App\Livewire\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderShow extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $this->order = $order;
    }

    public function confirmReceived(OrderService $orders): void
    {
        $this->run(fn () => $orders->complete($this->order));
        session()->flash('status', 'Terima kasih. Pesanan ditandai selesai, sekarang kamu bisa menulis ulasan.');
    }

    public function cancel(OrderService $orders): void
    {
        if ($this->order->status !== OrderStatus::PendingPayment) {
            $this->addError('action', 'Pesanan yang sudah dibayar tidak bisa dibatalkan sendiri. Hubungi kami lewat chat.');

            return;
        }

        $this->run(fn () => $orders->cancelUnpaid($this->order));
    }

    public function render(): View
    {
        $this->order->load(['items.variant.product', 'items.review', 'payment']);

        return view('livewire.orders.order-show')->title("Pesanan {$this->order->code}");
    }

    private function run(callable $action): void
    {
        try {
            $action();
        } catch (OrderException $e) {
            $this->addError('action', $e->getMessage());
        }

        $this->order->refresh();
    }
}
