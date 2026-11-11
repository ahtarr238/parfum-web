<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
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

    /**
     * Batalkan order dan kembalikan stok serta kuota voucher. Aman dipanggil bersamaan
     * (row lock pada order), stok tidak akan dikembalikan dua kali.
     */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = $this->lock($order);

            if (! in_array($locked->status, [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Processing], true)) {
                throw new OrderException("Pesanan berstatus {$locked->status->label()} tidak bisa dibatalkan.");
            }

            $locked->load('items');
            $variantIds = $locked->items->pluck('product_variant_id')->filter()->sort()->values();
            ProductVariant::whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get();

            foreach ($locked->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->qty);
                }
            }

            if ($locked->voucher_id && $locked->voucher) {
                $this->vouchers->release($locked->voucher);
            }

            $locked->update(['status' => OrderStatus::Cancelled]);

            return $locked;
        });
    }

    /**
     * Batalkan semua order Menunggu Bayar yang lewat batas waktu. Mengembalikan jumlah order.
     */
    public function expireOverdue(): int
    {
        $count = 0;

        Order::where('status', OrderStatus::PendingPayment)
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->each(function (Order $order) use (&$count) {
                try {
                    $this->cancel($order);
                    $count++;
                } catch (OrderException) {
                    // Sudah berubah status (mis. baru dibayar) di antara query dan lock.
                }
            });

        return $count;
    }

    /**
     * HANYA dipanggil dari webhook Midtrans dengan signature valid.
     *
     * @param  array<string, mixed>  $payment
     */
    public function markPaid(Order $order, array $payment): Order
    {
        $order = DB::transaction(function () use ($order, $payment) {
            $locked = $this->lock($order);

            if ($locked->status === OrderStatus::Paid) {
                return $locked;
            }

            if ($locked->status !== OrderStatus::PendingPayment) {
                throw new OrderException("Pesanan {$locked->code} berstatus {$locked->status->label()}, tidak bisa ditandai dibayar.");
            }

            $locked->update(['status' => OrderStatus::Paid]);
            $locked->payment?->update([
                'status' => 'settlement',
                'payment_type' => $payment['payment_type'] ?? $locked->payment->payment_type,
                'paid_at' => now(),
                'raw_response' => $payment,
            ]);

            return $locked;
        });

        OrderPaid::dispatch($order);

        return $order;
    }

    public function markProcessing(Order $order): Order
    {
        return $this->transition($order, [OrderStatus::Paid], OrderStatus::Processing);
    }

    public function ship(Order $order, string $trackingNumber): Order
    {
        $trackingNumber = trim($trackingNumber);

        if ($trackingNumber === '') {
            throw new OrderException('Nomor resi wajib diisi.');
        }

        $order = $this->transition($order, [OrderStatus::Paid, OrderStatus::Processing], OrderStatus::Shipped, [
            'tracking_number' => $trackingNumber,
        ]);

        OrderShipped::dispatch($order);

        return $order;
    }

    public function complete(Order $order): Order
    {
        return $this->transition($order, [OrderStatus::Shipped], OrderStatus::Completed);
    }

    /**
     * @param  list<OrderStatus>  $from
     * @param  array<string, mixed>  $extra
     */
    private function transition(Order $order, array $from, OrderStatus $to, array $extra = []): Order
    {
        return DB::transaction(function () use ($order, $from, $to, $extra) {
            $locked = $this->lock($order);

            if (! in_array($locked->status, $from, true)) {
                throw new OrderException("Pesanan berstatus {$locked->status->label()} tidak bisa diubah menjadi {$to->label()}.");
            }

            $locked->update(['status' => $to, ...$extra]);

            return $locked;
        });
    }

    private function lock(Order $order): Order
    {
        return Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
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
