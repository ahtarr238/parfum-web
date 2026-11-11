<?php

use App\Enums\OrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\CatalogQuery;

function makeProduct(array $attrs = [], array $variants = [[50, 300000, 5]]): Product
{
    $product = Product::factory()->create($attrs);

    foreach ($variants as [$ml, $price, $stock]) {
        ProductVariant::factory()->for($product)->create([
            'size_ml' => $ml,
            'price' => $price,
            'stock' => $stock,
            'is_decant' => $ml <= 10,
        ]);
    }

    return $product;
}

function names(CatalogQuery $query): array
{
    return $query->builder()->pluck('name')->all();
}

it('mencari berdasarkan nama produk atau nama brand', function () {
    $brand = Brand::factory()->create(['name' => 'Senja Parfumerie']);
    makeProduct(['name' => 'Melati Pagi']);
    makeProduct(['name' => 'Hujan', 'brand_id' => $brand->id]);
    makeProduct(['name' => 'Lain']);

    expect(names(new CatalogQuery(search: 'melati')))->toBe(['Melati Pagi'])
        ->and(names(new CatalogQuery(search: 'senja')))->toBe(['Hujan']);
});

it('memfilter kategori lewat slug', function () {
    $pria = Category::factory()->create(['slug' => 'pria']);
    makeProduct(['name' => 'A', 'category_id' => $pria->id]);
    makeProduct(['name' => 'B']);

    expect(names(new CatalogQuery(category: 'pria')))->toBe(['A']);
});

it('memfilter beberapa brand dan aroma sekaligus', function () {
    $b1 = Brand::factory()->create(['slug' => 'satu']);
    $b2 = Brand::factory()->create(['slug' => 'dua']);
    makeProduct(['name' => 'A', 'brand_id' => $b1->id, 'scent_family' => 'woody']);
    makeProduct(['name' => 'B', 'brand_id' => $b2->id, 'scent_family' => 'floral']);
    makeProduct(['name' => 'C', 'brand_id' => $b2->id, 'scent_family' => 'woody']);
    makeProduct(['name' => 'D', 'scent_family' => 'woody']);

    expect(names((new CatalogQuery(brands: ['satu', 'dua'], scents: ['woody']))->sorted('termurah')))
        ->toEqualCanonicalizing(['A', 'C']);
});

it('memfilter ukuran termasuk decant', function () {
    makeProduct(['name' => 'Besar'], [[100, 500000, 3]]);
    makeProduct(['name' => 'Kecil'], [[10, 50000, 3]]);
    makeProduct(['name' => 'Sedang'], [[50, 300000, 3]]);

    expect(names(new CatalogQuery(sizes: ['100'])))->toBe(['Besar'])
        ->and(names(new CatalogQuery(sizes: ['decant'])))->toBe(['Kecil'])
        ->and(names(new CatalogQuery(sizes: ['decant', '50'])))->toEqualCanonicalizing(['Kecil', 'Sedang']);
});

it('memfilter rentang harga dari varian mana pun', function () {
    makeProduct(['name' => 'Murah'], [[30, 150000, 1]]);
    makeProduct(['name' => 'Campur'], [[10, 60000, 1], [100, 900000, 1]]);
    makeProduct(['name' => 'Mahal'], [[100, 1200000, 1]]);

    expect(names(new CatalogQuery(minPrice: 100000, maxPrice: 1000000)))->toEqualCanonicalizing(['Murah', 'Campur']);
});

it('mengurutkan termurah berdasarkan harga varian termurah', function () {
    makeProduct(['name' => 'B'], [[50, 300000, 1]]);
    makeProduct(['name' => 'A'], [[10, 50000, 1], [100, 900000, 1]]);
    makeProduct(['name' => 'C'], [[50, 400000, 1]]);

    expect(names((new CatalogQuery)->sorted('termurah')))->toBe(['A', 'B', 'C']);
});

it('mengurutkan terbaru', function () {
    makeProduct(['name' => 'Lama', 'created_at' => now()->subDays(2)]);
    makeProduct(['name' => 'Baru', 'created_at' => now()]);

    expect(names((new CatalogQuery)->sorted('terbaru')))->toBe(['Baru', 'Lama']);
});

it('mengurutkan terlaris hanya dari order yang sudah dibayar', function () {
    $laris = makeProduct(['name' => 'Laris']);
    $batal = makeProduct(['name' => 'Batal']);
    makeProduct(['name' => 'Sepi']);

    $paid = Order::factory()->status(OrderStatus::Completed)->create();
    OrderItem::factory()->for($paid)->create(['product_variant_id' => $laris->variants->first()->id, 'qty' => 3]);

    $cancelled = Order::factory()->status(OrderStatus::Cancelled)->create();
    OrderItem::factory()->for($cancelled)->create(['product_variant_id' => $batal->variants->first()->id, 'qty' => 10]);

    expect(names((new CatalogQuery)->sorted('terlaris'))[0])->toBe('Laris');
});

it('menyertakan harga mulai dan total stok', function () {
    makeProduct(['name' => 'A'], [[10, 60000, 0], [50, 300000, 4]]);

    $product = (new CatalogQuery)->builder()->first();

    expect($product->min_price)->toBe(60000)
        ->and($product->total_stock)->toBe(4)
        ->and($product->isSoldOut())->toBeFalse();
});

it('produk nonaktif dan stok nol ditandai habis tapi tetap tampil', function () {
    makeProduct(['name' => 'Nonaktif', 'is_active' => false]);
    makeProduct(['name' => 'Kosong'], [[50, 300000, 0]]);

    $products = (new CatalogQuery)->builder()->get();

    expect($products)->toHaveCount(2)
        ->and($products->every->isSoldOut())->toBeTrue();
});

it('mengabaikan nilai filter yang tidak dikenal', function () {
    makeProduct(['name' => 'A']);

    expect(names(new CatalogQuery(scents: ['bukan-aroma'], sizes: ['abc'])))->toBe(['A']);
});
