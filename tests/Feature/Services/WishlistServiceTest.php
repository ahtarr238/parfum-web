<?php

use App\Models\Product;
use App\Models\User;
use App\Services\WishlistService;

it('toggle menambah lalu menghapus produk dari wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $service = app(WishlistService::class);

    expect($service->toggle($user, $product))->toBeTrue()
        ->and($user->wishlistItems()->count())->toBe(1)
        ->and($service->toggle($user, $product))->toBeFalse()
        ->and($user->wishlistItems()->count())->toBe(0);
});
