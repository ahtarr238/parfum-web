<?php

use App\Livewire\Cart;
use App\Livewire\Wishlist;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('keranjang dan wishlist butuh login', function () {
    $this->get('/keranjang')->assertRedirect('/login');
    $this->get('/wishlist')->assertRedirect('/login');
});

it('keranjang menampilkan item, subtotal, dan total berat', function () {
    $variant = ProductVariant::factory()->for(Product::factory()->state(['name' => 'Hutan Pagi']))
        ->create(['price' => 150000, 'weight_gram' => 300, 'stock' => 5]);
    CartItem::factory()->for($this->user)->create(['product_variant_id' => $variant->id, 'qty' => 2]);

    Livewire::actingAs($this->user)->test(Cart::class)
        ->assertSee('Hutan Pagi')
        ->assertSee('Rp300.000')
        ->assertSee('600 g');
});

it('mengubah jumlah tidak boleh melebihi stok', function () {
    $item = CartItem::factory()->for($this->user)->create([
        'product_variant_id' => ProductVariant::factory()->create(['stock' => 2])->id,
        'qty' => 1,
    ]);

    Livewire::actingAs($this->user)->test(Cart::class)
        ->call('setQty', $item->id, 2)
        ->assertHasNoErrors()
        ->call('setQty', $item->id, 3)
        ->assertHasErrors("qty.{$item->id}");

    expect($item->fresh()->qty)->toBe(2);
});

it('menghapus item dari keranjang', function () {
    $item = CartItem::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)->test(Cart::class)
        ->call('remove', $item->id)
        ->assertDispatched('cart-updated')
        ->assertSee('Keranjangmu masih kosong');
});

it('tidak bisa mengubah item keranjang orang lain', function () {
    $other = CartItem::factory()->create(['qty' => 1]);

    Livewire::actingAs($this->user)->test(Cart::class)->call('remove', $other->id);

    expect(CartItem::find($other->id))->not->toBeNull();
});

it('tombol checkout nonaktif jika ada item bermasalah', function () {
    $variant = ProductVariant::factory()->create(['stock' => 1]);
    CartItem::factory()->for($this->user)->create(['product_variant_id' => $variant->id, 'qty' => 3]);

    Livewire::actingAs($this->user)->test(Cart::class)
        ->assertSee('Tersisa 1')
        ->assertSet('canCheckout', false);
});

it('wishlist menampilkan produk dan bisa dihapus', function () {
    $wish = WishlistItem::factory()->for($this->user)->for(Product::factory()->state(['name' => 'Melati Senja']))->create();

    Livewire::actingAs($this->user)->test(Wishlist::class)
        ->assertSee('Melati Senja')
        ->call('remove', $wish->id)
        ->assertDontSee('Melati Senja');
});

it('pindah ke keranjang memakai varian termurah yang ada stok', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['price' => 50000, 'stock' => 0]);
    $cheapest = ProductVariant::factory()->for($product)->create(['price' => 150000, 'stock' => 3]);
    ProductVariant::factory()->for($product)->create(['price' => 300000, 'stock' => 3]);
    $wish = WishlistItem::factory()->for($this->user)->for($product)->create();

    Livewire::actingAs($this->user)->test(Wishlist::class)
        ->call('moveToCart', $wish->id)
        ->assertDispatched('cart-updated');

    expect($this->user->cartItems()->first()->product_variant_id)->toBe($cheapest->id)
        ->and(WishlistItem::find($wish->id))->toBeNull();
});

it('pindah ke keranjang gagal jika produk habis', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['stock' => 0]);
    $wish = WishlistItem::factory()->for($this->user)->for($product)->create();

    Livewire::actingAs($this->user)->test(Wishlist::class)
        ->call('moveToCart', $wish->id)
        ->assertHasErrors("wish.{$wish->id}");

    expect(WishlistItem::find($wish->id))->not->toBeNull();
});
