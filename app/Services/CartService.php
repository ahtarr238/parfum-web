<?php

namespace App\Services;

use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function add(User $user, ProductVariant $variant, int $qty = 1): CartItem
    {
        if ($qty < 1) {
            throw new CartException('Jumlah minimal 1.');
        }

        return DB::transaction(function () use ($user, $variant, $qty) {
            $variant = ProductVariant::with('product')->lockForUpdate()->findOrFail($variant->id);
            $this->ensureSellable($variant);

            $item = $user->cartItems()->firstOrNew(['product_variant_id' => $variant->id]);
            $newQty = ($item->exists ? $item->qty : 0) + $qty;

            if ($newQty > $variant->stock) {
                $left = $variant->stock - ($item->exists ? $item->qty : 0);
                throw new CartException($left > 0
                    ? "Stok tidak cukup. Kamu masih bisa menambah {$left} lagi."
                    : "Stok tidak cukup. Semua stok ({$variant->stock}) sudah ada di keranjangmu.");
            }

            $item->qty = $newQty;
            $item->save();

            return $item;
        });
    }

    public function update(CartItem $item, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($item);

            return;
        }

        $variant = $item->variant()->with('product')->firstOrFail();
        $this->ensureSellable($variant);

        if ($qty > $variant->stock) {
            throw new CartException("Stok tidak cukup. Tersisa {$variant->stock}.");
        }

        $item->update(['qty' => $qty]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function summary(User $user): CartSummary
    {
        $items = $user->cartItems()
            ->with(['variant.product.brand'])
            ->orderBy('id')
            ->get()
            ->each(function (CartItem $item) {
                $variant = $item->variant;
                $item->problem = match (true) {
                    ! $variant->product->is_active || $variant->stock === 0 => 'Stok habis. Hapus dari keranjang untuk lanjut.',
                    $item->qty > $variant->stock => "Stok tidak cukup. Tersisa {$variant->stock}.",
                    default => null,
                };
            });

        return new CartSummary(
            items: $items,
            subtotal: (int) $items->sum(fn (CartItem $i) => $i->variant->price * $i->qty),
            totalWeight: (int) $items->sum(fn (CartItem $i) => $i->variant->weight_gram * $i->qty),
            itemCount: (int) $items->sum('qty'),
            hasProblems: $items->contains(fn (CartItem $i) => $i->problem !== null),
        );
    }

    private function ensureSellable(ProductVariant $variant): void
    {
        if (! $variant->product->is_active || $variant->stock <= 0) {
            throw new CartException('Maaf, ukuran ini sedang habis.');
        }
    }
}
