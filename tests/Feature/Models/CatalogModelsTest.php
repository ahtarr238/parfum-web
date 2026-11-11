<?php

use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\QueryException;

it('menghubungkan produk dengan kategori, brand, dan varian', function () {
    $product = Product::factory()->has(ProductVariant::factory()->count(3), 'variants')->create();

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($product->brand)->toBeInstanceOf(Brand::class)
        ->and($product->variants)->toHaveCount(3);
});

it('meng-cast images ke array dan scent_family ke enum', function () {
    $product = Product::factory()->create(['images' => ['a.webp', 'b.webp'], 'scent_family' => 'woody']);

    expect($product->fresh()->images)->toBe(['a.webp', 'b.webp'])
        ->and($product->fresh()->scent_family)->toBe(ScentFamily::Woody);
});

it('menolak stok di bawah nol', function () {
    $variant = ProductVariant::factory()->create(['stock' => 1]);

    expect(fn () => $variant->decrement('stock', 2))->toThrow(QueryException::class);
});

it('menolak slug produk ganda', function () {
    Product::factory()->create(['slug' => 'sama']);

    expect(fn () => Product::factory()->create(['slug' => 'sama']))->toThrow(QueryException::class);
});

it('menolak sku varian ganda', function () {
    ProductVariant::factory()->create(['sku' => 'SKU-1']);

    expect(fn () => ProductVariant::factory()->create(['sku' => 'SKU-1']))->toThrow(QueryException::class);
});

it('seeder kategori idempotent', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::orderBy('slug')->pluck('slug')->all())->toBe(['pria', 'unisex', 'wanita']);
});

it('label aroma sesuai PRD', function () {
    expect(array_map(fn (ScentFamily $s) => $s->label(), ScentFamily::cases()))
        ->toBe(['Woody', 'Floral', 'Fresh', 'Oriental', 'Gourmand']);
});
