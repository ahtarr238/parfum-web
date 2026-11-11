<?php

use App\Enums\UserRole;
use App\Models\User;

it('membuat admin terverifikasi', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Nama', 'Admin Toko')
        ->expectsQuestion('Email', 'admin@example.test')
        ->expectsQuestion('Password', 'rahasia123')
        ->assertSuccessful();

    $user = User::firstWhere('email', 'admin@example.test');

    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->email_verified_at)->not->toBeNull();
});

it('menolak email yang sudah dipakai', function () {
    User::factory()->create(['email' => 'admin@example.test']);

    $this->artisan('app:create-admin')
        ->expectsQuestion('Nama', 'A')
        ->expectsQuestion('Email', 'admin@example.test')
        ->expectsQuestion('Password', 'rahasia123')
        ->assertFailed();
});

it('menolak password kurang dari 8 karakter', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Nama', 'A')
        ->expectsQuestion('Email', 'a@example.test')
        ->expectsQuestion('Password', 'pendek')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
