<?php

namespace App\Livewire\Orders;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderIndex extends Component
{
    public function render(): View
    {
        return view('livewire.orders.order-index')->title('Pesanan saya');
    }
}
