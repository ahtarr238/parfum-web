<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

it('mengisi data dev yang dibutuhkan fase berikutnya', function () {
    $this->seed();

    expect(Category::count())->toBe(3)
        ->and(Product::count())->toBe(12)
        ->and(ProductVariant::where('stock', 0)->exists())->toBeTrue()
        ->and(Faq::where('is_active', true)->pluck('question')->all())
        ->toContain('Cara order', 'Ongkir', 'Produk ori?')
        ->and(User::where('role', UserRole::Admin)->exists())->toBeFalse()
        ->and(User::where('email', 'customer@tokoparfum.test')->whereNotNull('email_verified_at')->exists())->toBeTrue();
});

it('setiap produk punya 2 sampai 4 varian', function () {
    $this->seed();

    Product::withCount('variants')->get()->each(
        fn (Product $p) => expect($p->variants_count)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(4)
    );
});
