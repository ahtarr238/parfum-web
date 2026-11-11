<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Services\CartService;
use App\Services\CartSummary;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Cart extends Component
{
    public bool $canCheckout = false;

    #[Computed]
    public function summary(): CartSummary
    {
        return app(CartService::class)->summary(auth()->user());
    }

    public function setQty(int $itemId, int $qty, CartService $cart): void
    {
        $item = auth()->user()->cartItems()->find($itemId);

        if (! $item) {
            return;
        }

        try {
            $cart->update($item, $qty);
            $this->resetErrorBag("qty.{$itemId}");
        } catch (CartException $e) {
            $this->addError("qty.{$itemId}", $e->getMessage());
        }

        unset($this->summary);
        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId, CartService $cart): void
    {
        $item = auth()->user()->cartItems()->find($itemId);

        if ($item) {
            $cart->remove($item);
        }

        unset($this->summary);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $this->canCheckout = ! $this->summary->isEmpty() && ! $this->summary->hasProblems;

        return view('livewire.cart')->title('Keranjang');
    }
}
