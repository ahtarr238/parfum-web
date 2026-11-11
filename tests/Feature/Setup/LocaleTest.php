<?php

use Livewire\Volt\Volt;

it('menampilkan pesan validasi dalam bahasa Indonesia', function () {
    $component = Volt::test('pages.auth.register')->call('register')->assertHasErrors(['email']);

    expect($component->errors()->first('email'))->toContain('wajib diisi');
});

it('memakai zona waktu Jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
});
