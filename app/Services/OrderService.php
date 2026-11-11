<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Exceptions\ShippingException;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public const EXPIRES_AFTER_HOURS = 24;

    public function __construct(
        private readonly ShippingService $shipping,
        private readonly VoucherService $vouchers,
    ) {}

    /**
     * Buat order dari keranjang. Stok dikurangi di dalam transaction dengan row lock,
     * harga/nama/alamat disalin (snapshot), ongkir dihitung ulang di server.
     */
    public function place(User $user, Address $address, string $shippingKey, ?string $voucherCode = null): Order
    {
        if ($address->user_id !== $user->id) {
            throw new OrderException('Alamat tidak ditemukan.');
        }

        $cartItems = $user->cartItems()->orderBy('product_variant_id')->get();

        if ($cartItems->isEmpty()) {
            throw new OrderException('Keranjang kosong.');
        }

        $order = DB::transaction(function () use ($user, $address, $shippingKey, $voucherCode, $cartItems) {
            /** @var Collection<int, ProductVariant> $variants */
            $variants = ProductVariant::with('product:id,name,is_active')
                ->whereIn('id', $cartItems->pluck('product_variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $weight = 0;

            foreach ($cartItems as $item) {
                $variant = $variants->get($item->product_variant_id);

                if (! $variant || ! $variant->product->is_active || $variant->stock < $item->qty) {
                    $name = $variant ? "{$variant->product->name} {$variant->label()}" : 'Salah satu produk';
                    throw new OrderException("Maaf, stok {$name} tidak cukup. Cek lagi keranjangmu.");
                }

                $subtotal += $variant->price * $item->qty;
                $weight += $variant->weight_gram * $item->qty;
            }

            $option = $this->shippingOption($address, $weight, $shippingKey);
            $voucher = $voucherCode ? $this->vouchers->claim($voucherCode, $subtotal, $user) : null;
            $discount = $voucher ? $this->vouchers->discountFor($voucher, $subtotal) : 0;

            $order = Order::create([
                'code' => $this->uniqueCode(),
                'user_id' => $user->id,
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $subtotal,
                'shipping_cost' => $option->cost,
                'discount' => $discount,
                'total' => $subtotal - $discount + $option->cost,
                'courier' => $option->courier,
                'courier_service' => $option->service,
                'address_snapshot' => $address->toSnapshot(),
                'voucher_id' => $voucher?->id,
                'expires_at' => now()->addHours(self::EXPIRES_AFTER_HOURS),
            ]);

            $cartItems->each(function (CartItem $item) use ($order, $variants) {
                $variant = $variants->get($item->product_variant_id);

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'size_ml' => $variant->size_ml,
                    'price' => $variant->price,
                    'qty' => $item->qty,
                ]);

                $variant->decrement('stock', $item->qty);
            });

            $user->cartItems()->delete();

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    private function shippingOption(Address $address, int $weight, string $key): ShippingOption
    {
        try {
            $option = $this->shipping->findOption($address->district_id, $weight, $key);
        } catch (ShippingException $e) {
            throw new OrderException($e->getMessage(), previous: $e);
        }

        if (! $option) {
            throw new OrderException('Pilihan kurir tidak tersedia lagi. Pilih ulang kurirnya.');
        }

        return $option;
    }

    private function uniqueCode(): string
    {
        do {
            // Tanpa karakter yang mirip (0/O, 1/I) supaya mudah dibacakan ke admin.
            $code = 'INV-'.now()->format('Ymd').'-'.Str::upper(substr(str_shuffle(str_repeat('23456789ABCDEFGHJKLMNPQRSTUVWXYZ', 3)), 0, 5));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
