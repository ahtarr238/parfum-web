<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\HomeController;
use App\Livewire\Account\Addresses;
use App\Livewire\Cart;
use App\Livewire\Catalog;
use App\Livewire\Checkout;
use App\Livewire\Orders\OrderIndex;
use App\Livewire\Orders\OrderShow;
use App\Livewire\ProductDetail;
use App\Livewire\Wishlist;
use Illuminate\Support\Facades\Route;

// Katalog publik
Route::get('/', HomeController::class)->name('home');
Route::get('katalog', Catalog::class)->name('catalog');
Route::get('kategori/{category:slug}', Catalog::class)->name('categories.show');
Route::get('brand', [BrandController::class, 'index'])->name('brands.index');
Route::get('brand/{brand:slug}', Catalog::class)->name('brands.show');
Route::get('parfum/{product:slug}', ProductDetail::class)->name('products.show');

// Customer
Route::middleware('auth')->group(function () {
    Route::get('keranjang', Cart::class)->name('cart');
    Route::get('wishlist', Wishlist::class)->name('wishlist');
    Route::get('checkout', Checkout::class)->name('checkout');
    Route::get('pesanan', OrderIndex::class)->name('orders.index');
    Route::get('pesanan/{order:code}', OrderShow::class)->name('orders.show');
    Route::get('akun/alamat', Addresses::class)->name('addresses');
    Route::view('profile', 'profile')->name('profile');
});

Route::redirect('dashboard', '/')->name('dashboard');

require __DIR__.'/auth.php';
