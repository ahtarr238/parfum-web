<?php

use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;

beforeEach(function () {
    $this->cart = app(CartService::class);
    $this->user = User::factory()->create();
});

it('menambah item baru ke keranjang', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    $this->cart->add($this->user, $variant, 2);

    expect($this->user->cartItems()->first()->qty)->toBe(2);
});

it('menggabungkan qty untuk varian yang sama', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    $this->cart->add($this->user, $variant, 2);
    $this->cart->add($this->user, $variant, 1);

    expect($this->user->cartItems()->count())->toBe(1)
        ->and($this->user->cartItems()->first()->qty)->toBe(3);
});

it('menolak jumlah yang melebihi stok', function () {
    $variant = ProductVariant::factory()->create(['stock' => 3]);
    $this->cart->add($this->user, $variant, 2);

    expect(fn () => $this->cart->add($this->user, $variant, 2))
        ->toThrow(CartException::class, 'Stok tidak cukup');
    expect($this->user->cartItems()->first()->qty)->toBe(2);
});

it('menolak varian yang habis atau produk nonaktif', function () {
    $habis = ProductVariant::factory()->create(['stock' => 0]);
    $nonaktif = ProductVariant::factory()->for(Product::factory()->inactive())->create(['stock' => 5]);

    expect(fn () => $this->cart->add($this->user, $habis, 1))->toThrow(CartException::class);
    expect(fn () => $this->cart->add($this->user, $nonaktif, 1))->toThrow(CartException::class);
});

it('menolak qty nol atau negatif', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    expect(fn () => $this->cart->add($this->user, $variant, 0))->toThrow(CartException::class);
});

it('mengubah jumlah dalam batas stok', function () {
    $item = CartItem::factory()->for($this->user)->create([
        'product_variant_id' => ProductVariant::factory()->create(['stock' => 4])->id,
        'qty' => 1,
    ]);

    $this->cart->update($item, 4);
    expect($item->fresh()->qty)->toBe(4);

    expect(fn () => $this->cart->update($item, 5))->toThrow(CartException::class);
});

it('update ke nol menghapus item', function () {
    $item = CartItem::factory()->for($this->user)->create();

    $this->cart->update($item, 0);

    expect(CartItem::find($item->id))->toBeNull();
});

it('menghitung subtotal, total berat, dan jumlah item', function () {
    $a = ProductVariant::factory()->create(['price' => 100000, 'weight_gram' => 300, 'stock' => 10]);
    $b = ProductVariant::factory()->create(['price' => 50000, 'weight_gram' => 50, 'stock' => 10]);
    $this->cart->add($this->user, $a, 2);
    $this->cart->add($this->user, $b, 3);

    $summary = $this->cart->summary($this->user);

    expect($summary->subtotal)->toBe(350000)
        ->and($summary->totalWeight)->toBe(750)
        ->and($summary->itemCount)->toBe(5)
        ->and($summary->items)->toHaveCount(2)
        ->and($summary->hasProblems)->toBeFalse();
});

it('menandai item yang stoknya turun setelah dimasukkan', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $this->cart->add($this->user, $variant, 4);
    $variant->update(['stock' => 2]);

    $summary = $this->cart->summary($this->user);

    expect($summary->hasProblems)->toBeTrue()
        ->and($summary->items->first()->problem)->toContain('Tersisa 2');
});
