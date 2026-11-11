<?php

use App\Models\Address;
use App\Models\User;
use App\Services\AddressBook;

beforeEach(function () {
    $this->book = app(AddressBook::class);
    $this->user = User::factory()->create();
});

it('alamat pertama otomatis jadi utama', function () {
    $address = $this->book->add($this->user, Address::factory()->raw());

    expect($address->is_default)->toBeTrue();
});

it('alamat kedua tidak mengambil status utama', function () {
    $this->book->add($this->user, Address::factory()->raw());

    expect($this->book->add($this->user, Address::factory()->raw())->is_default)->toBeFalse();
});

it('alamat baru bisa langsung dijadikan utama', function () {
    $first = $this->book->add($this->user, Address::factory()->raw());
    $second = $this->book->add($this->user, [...Address::factory()->raw(), 'is_default' => true]);

    expect($second->fresh()->is_default)->toBeTrue()
        ->and($first->fresh()->is_default)->toBeFalse();
});

it('makeDefault menyisakan tepat satu alamat utama', function () {
    $this->book->add($this->user, Address::factory()->raw());
    $second = $this->book->add($this->user, Address::factory()->raw());

    $this->book->makeDefault($second);

    expect($this->user->addresses()->where('is_default', true)->pluck('id')->all())->toBe([$second->id]);
});

it('menghapus alamat utama mempromosikan alamat terbaru', function () {
    $first = $this->book->add($this->user, Address::factory()->raw());
    $this->travel(1)->minutes();
    $second = $this->book->add($this->user, Address::factory()->raw());

    $this->book->remove($first);

    expect($second->fresh()->is_default)->toBeTrue();
});

it('menghapus alamat terakhir tidak error', function () {
    $this->book->remove($this->book->add($this->user, Address::factory()->raw()));

    expect($this->user->addresses()->count())->toBe(0);
});

it('defaultAddress mengembalikan alamat utama', function () {
    $this->book->add($this->user, Address::factory()->raw());
    $second = $this->book->add($this->user, Address::factory()->raw());
    $this->book->makeDefault($second);

    expect($this->user->fresh()->defaultAddress->id)->toBe($second->id);
});
