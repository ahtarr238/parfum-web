<?php

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

// Keputusan pemilik (11 Nov 2026): tidak ada verifikasi email. Akun baru langsung bisa belanja.

it('daftar tidak mengirim email verifikasi', function () {
    Notification::fake();

    Volt::test('pages.auth.register')
        ->set('name', 'Budi')->set('email', 'budi@example.test')
        ->set('password', 'rahasia123')->set('password_confirmation', 'rahasia123')
        ->call('register')
        ->assertRedirect('/');

    Notification::assertNothingSent();
});

it('akun yang baru daftar langsung bisa membuka checkout', function () {
    $user = User::factory()->unverified()->create();
    CartItem::factory()->for($user)->create();

    $this->actingAs($user)->get('/checkout')->assertOk()->assertSee('Checkout');
});

it('halaman verifikasi email sudah tidak ada', function () {
    $this->actingAs(User::factory()->unverified()->create())->get('/verify-email')->assertNotFound();
});
