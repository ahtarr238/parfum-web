<?php

namespace App\Livewire\Orders;

use App\Enums\OrderStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OrderIndex extends Component
{
    use WithPagination;

    public const TABS = [
        '' => 'Semua',
        'pending_payment' => 'Menunggu Bayar',
        'processing' => 'Diproses',
        'shipped' => 'Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    #[Url(except: '')]
    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $statuses = match ($this->status) {
            'processing' => [OrderStatus::Paid, OrderStatus::Processing],
            '' => null,
            default => array_filter([OrderStatus::tryFrom($this->status)]),
        };

        $orders = auth()->user()->orders()
            ->with('items')
            ->when($statuses !== null, fn ($q) => $q->whereIn('status', $statuses))
            ->latest()
            ->paginate(10);

        return view('livewire.orders.order-index', ['orders' => $orders])->title('Pesanan saya');
    }
}
