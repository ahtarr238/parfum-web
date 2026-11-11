<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Volt\Volt;

it('mengarahkan guest ke login admin', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('menolak customer terverifikasi', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('mengizinkan admin', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
});

it('register membuat user dengan role customer', function () {
    Volt::test('pages.auth.register')
        ->set('name', 'Budi')
        ->set('email', 'budi@example.test')
        ->set('password', 'rahasia123')
        ->set('password_confirmation', 'rahasia123')
        ->call('register');

    expect(User::firstWhere('email', 'budi@example.test')->role)->toBe(UserRole::Customer);
});

it('tidak bisa mengisi role lewat mass assignment', function () {
    $user = User::create([
        'name' => 'X',
        'email' => 'x@example.test',
        'password' => 'rahasia123',
        'role' => 'admin',
    ]);

    expect($user->fresh()->role)->toBe(UserRole::Customer);
});
