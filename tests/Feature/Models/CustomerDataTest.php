<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\QueryException;

it('menghubungkan user dengan item keranjang dan wishlist', function () {
    $user = User::factory()->create();
    CartItem::factory()->for($user)->count(2)->create();
    WishlistItem::factory()->for($user)->create();

    expect($user->cartItems)->toHaveCount(2)
        ->and($user->wishlistItems)->toHaveCount(1)
        ->and($user->cartItems->first()->variant)->toBeInstanceOf(ProductVariant::class)
        ->and($user->wishlistItems->first()->product)->toBeInstanceOf(Product::class);
});

it('menolak baris keranjang ganda untuk varian yang sama', function () {
    $item = CartItem::factory()->create();

    expect(fn () => CartItem::factory()->create([
        'user_id' => $item->user_id,
        'product_variant_id' => $item->product_variant_id,
    ]))->toThrow(QueryException::class);
});

it('menolak wishlist ganda untuk produk yang sama', function () {
    $item = WishlistItem::factory()->create();

    expect(fn () => WishlistItem::factory()->create([
        'user_id' => $item->user_id,
        'product_id' => $item->product_id,
    ]))->toThrow(QueryException::class);
});
