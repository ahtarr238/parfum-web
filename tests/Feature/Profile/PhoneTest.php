<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('menyimpan nomor HP dari halaman profil', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('profile.update-profile-information-form')
        ->set('phone', '081234567890')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->phone)->toBe('081234567890');
});

it('menolak nomor HP berisi huruf', function () {
    Volt::actingAs(User::factory()->create())->test('profile.update-profile-information-form')
        ->set('phone', '08abc')
        ->call('updateProfileInformation')
        ->assertHasErrors(['phone']);
});
