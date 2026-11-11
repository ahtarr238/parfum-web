<?php

namespace App\Livewire;

use App\Exceptions\OrderException;
use App\Exceptions\ShippingException;
use App\Exceptions\VoucherException;
use App\Models\Address;
use App\Models\Voucher;
use App\Services\CartService;
use App\Services\CartSummary;
use App\Services\OrderService;
use App\Services\ShippingOption;
use App\Services\ShippingService;
use App\Services\VoucherService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Checkout extends Component
{
    public ?int $addressId = null;

    public ?string $shippingKey = null;

    public string $voucherInput = '';

    public ?string $voucherCode = null;

    public function mount(): void
    {
        if ($this->summary->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $this->addressId = auth()->user()->defaultAddress?->id
            ?? auth()->user()->addresses()->latest()->value('id');
    }

    public function updatedAddressId(): void
    {
        $this->shippingKey = null;
        unset($this->shippingOptions, $this->address);
    }

    #[Computed]
    public function summary(): CartSummary
    {
        return app(CartService::class)->summary(auth()->user());
    }

    /** @return Collection<int, Address> */
    #[Computed]
    public function addresses(): Collection
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->latest()->get();
    }

    #[Computed]
    public function address(): ?Address
    {
        return $this->addresses->firstWhere('id', $this->addressId);
    }

    /**
     * @return array{options: list<ShippingOption>, error: ?string}
     */
    #[Computed]
    public function shippingOptions(): array
    {
        if (! $this->address) {
            return ['options' => [], 'error' => null];
        }

        try {
            return ['options' => app(ShippingService::class)->costs($this->address->district_id, $this->summary->totalWeight), 'error' => null];
        } catch (ShippingException $e) {
            return ['options' => [], 'error' => $e->getMessage()];
        }
    }

    #[Computed]
    public function selectedShipping(): ?ShippingOption
    {
        foreach ($this->shippingOptions['options'] as $option) {
            if ($option->key() === $this->shippingKey) {
                return $option;
            }
        }

        return null;
    }

    #[Computed]
    public function voucher(): ?Voucher
    {
        if (! $this->voucherCode) {
            return null;
        }

        try {
            return app(VoucherService::class)->preview($this->voucherCode, $this->summary->subtotal);
        } catch (VoucherException) {
            return null;
        }
    }

    #[Computed]
    public function discount(): int
    {
        return $this->voucher ? app(VoucherService::class)->discountFor($this->voucher, $this->summary->subtotal) : 0;
    }

    public function applyVoucher(VoucherService $vouchers): void
    {
        $this->resetErrorBag('voucherInput');

        if (trim($this->voucherInput) === '') {
            return;
        }

        try {
            $voucher = $vouchers->preview($this->voucherInput, $this->summary->subtotal);
        } catch (VoucherException $e) {
            $this->addError('voucherInput', $e->getMessage());

            return;
        }

        $this->voucherCode = $voucher->code;
        $this->voucherInput = '';
        unset($this->voucher, $this->discount);
    }

    public function removeVoucher(): void
    {
        $this->voucherCode = null;
        unset($this->voucher, $this->discount);
    }

    public function placeOrder(OrderService $orders): void
    {
        $this->resetErrorBag();

        $address = auth()->user()->addresses()->find($this->addressId);

        if (! $address) {
            $this->addError('addressId', 'Pilih alamat pengiriman.');

            return;
        }

        if (! $this->shippingKey) {
            $this->addError('shippingKey', 'Pilih kurir pengiriman.');

            return;
        }

        try {
            $order = $orders->place(auth()->user(), $address, $this->shippingKey, $this->voucherCode);
        } catch (VoucherException $e) {
            $this->voucherCode = null;
            $this->addError('voucherInput', $e->getMessage());

            return;
        } catch (OrderException $e) {
            $this->addError('order', $e->getMessage());
            unset($this->summary, $this->shippingOptions);

            return;
        }

        $this->dispatch('cart-updated');
        session()->flash('order-placed', true);
        $this->redirectRoute('orders.show', $order, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.checkout')->title('Checkout');
    }
}
