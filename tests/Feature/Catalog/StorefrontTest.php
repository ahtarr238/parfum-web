<?php

use App\Livewire\Catalog;
use App\Livewire\ProductDetail;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

function productWith(array $attrs = [], array $variants = [[50, 300000, 5]]): Product
{
    $product = Product::factory()->create($attrs);
    foreach ($variants as [$ml, $price, $stock]) {
        ProductVariant::factory()->for($product)->create(['size_ml' => $ml, 'price' => $price, 'stock' => $stock]);
    }

    return $product;
}

it('menampilkan beranda', function () {
    productWith(['name' => 'Hutan Pagi']);

    $this->get('/')->assertOk()->assertSee('Hutan Pagi')->assertSee('Lihat katalog');
});

it('katalog menampilkan produk dan label habis', function () {
    productWith(['name' => 'Ada Stok']);
    productWith(['name' => 'Kosong'], [[50, 300000, 0]]);
    productWith(['name' => 'Nonaktif', 'is_active' => false]);

    Livewire::test(Catalog::class)
        ->assertSee('Ada Stok')
        ->assertSee('Kosong')
        ->assertSee('Nonaktif')
        ->assertSeeInOrder(['Habis'])
        ->assertSee('3 parfum');
});

it('filter aroma membaca query string', function () {
    productWith(['name' => 'Kayu', 'scent_family' => 'woody']);
    productWith(['name' => 'Bunga', 'scent_family' => 'floral']);

    Livewire::withQueryParams(['aroma' => ['woody']])
        ->test(Catalog::class)
        ->assertSee('Kayu')
        ->assertDontSee('Bunga');
});

it('filter harga dan search bisa diubah dari komponen', function () {
    productWith(['name' => 'Murah'], [[30, 100000, 1]]);
    productWith(['name' => 'Mahal'], [[100, 900000, 1]]);

    Livewire::test(Catalog::class)
        ->set('max', '200.000')
        ->assertSee('Murah')
        ->assertDontSee('Mahal')
        ->set('max', '')
        ->set('q', 'mahal')
        ->assertSee('Mahal')
        ->assertDontSee('Murah');
});

it('halaman kategori hanya menampilkan kategori itu', function () {
    $pria = Category::factory()->create(['name' => 'Pria', 'slug' => 'pria']);
    productWith(['name' => 'Untuk Pria', 'category_id' => $pria->id]);
    productWith(['name' => 'Lainnya']);

    $this->get('/kategori/pria')->assertOk()->assertSee('Untuk Pria')->assertDontSee('Lainnya');
});

it('halaman brand hanya menampilkan brand itu', function () {
    $brand = Brand::factory()->create(['name' => 'Rimba', 'slug' => 'rimba']);
    productWith(['name' => 'Dari Rimba', 'brand_id' => $brand->id]);
    productWith(['name' => 'Lainnya']);

    $this->get('/brand/rimba')->assertOk()->assertSee('Dari Rimba')->assertDontSee('Lainnya');
    $this->get('/brand')->assertOk()->assertSee('Rimba');
});

it('kategori yang tidak ada mengembalikan 404', function () {
    $this->get('/kategori/tidak-ada')->assertNotFound();
    $this->get('/parfum/tidak-ada')->assertNotFound();
});

it('detail produk mengganti harga, stok, dan berat saat ukuran dipilih', function () {
    $product = Product::factory()->create(['top_notes' => 'Bergamot, Lemon']);
    $small = ProductVariant::factory()->for($product)->create(['size_ml' => 30, 'price' => 150000, 'stock' => 4, 'weight_gram' => 240]);
    $big = ProductVariant::factory()->for($product)->create(['size_ml' => 100, 'price' => 450000, 'stock' => 2, 'weight_gram' => 450]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertSee('Rp150.000')
        ->assertSee('Bergamot')
        ->call('selectVariant', $big->id)
        ->assertSee('Rp450.000')
        ->assertSee('Stok 2')
        ->assertSee('Berat 450 g');
});

it('detail memilih varian yang masih ada stok secara default', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['size_ml' => 30, 'stock' => 0, 'price' => 100000]);
    $ready = ProductVariant::factory()->for($product)->create(['size_ml' => 50, 'stock' => 3, 'price' => 200000]);

    Livewire::test(ProductDetail::class, ['product' => $product])->assertSet('variantId', $ready->id);
});

it('mengabaikan varian dari produk lain', function () {
    $product = productWith();
    $other = ProductVariant::factory()->create();
    $original = $product->variants->first()->id;

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->call('selectVariant', $other->id)
        ->assertSet('variantId', $original);
});

it('guest yang menambah ke keranjang diarahkan ke login', function () {
    $product = productWith();

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->call('addToCart')
        ->assertRedirect(route('login'));
});

it('customer bisa menambah ke keranjang dari detail', function () {
    $user = User::factory()->create();
    $product = productWith();

    Livewire::actingAs($user)->test(ProductDetail::class, ['product' => $product])
        ->call('increment')
        ->call('addToCart')
        ->assertDispatched('cart-updated')
        ->assertHasNoErrors();

    expect((int) $user->cartItems()->sum('qty'))->toBe(2);
});

it('jumlah di detail tidak bisa melebihi stok', function () {
    $product = productWith([], [[50, 300000, 2]]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->call('increment')->call('increment')->call('increment')
        ->assertSet('qty', 2);
});

it('produk nonaktif tidak bisa dibeli', function () {
    $user = User::factory()->create();
    $product = productWith(['is_active' => false]);

    Livewire::actingAs($user)->test(ProductDetail::class, ['product' => $product])
        ->assertSee('Habis')
        ->call('addToCart')
        ->assertHasErrors('qty');

    expect($user->cartItems()->count())->toBe(0);
});
