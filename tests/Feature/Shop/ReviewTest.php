<?php

use App\Enums\OrderStatus;
use App\Livewire\Reviews\ReviewForm;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function itemFor(User $user, OrderStatus $status): OrderItem
{
    $order = Order::factory()->for($user)->status($status)->create();

    return OrderItem::factory()->for($order)->create();
}

it('menulis ulasan untuk item dari pesanan selesai', function () {
    $item = itemFor($this->user, OrderStatus::Completed);

    Livewire::actingAs($this->user)->test(ReviewForm::class, ['orderItemId' => $item->id])
        ->set('rating', 5)
        ->set('comment', 'Wanginya tahan lama.')
        ->call('save')
        ->assertHasNoErrors();

    $review = Review::first();
    expect($review->rating)->toBe(5)
        ->and($review->product_id)->toBe($item->variant->product_id)
        ->and($review->is_visible)->toBeTrue();
});

it('menolak ulasan untuk pesanan yang belum selesai', function () {
    $item = itemFor($this->user, OrderStatus::Shipped);

    Livewire::actingAs($this->user)->test(ReviewForm::class, ['orderItemId' => $item->id])
        ->set('rating', 4)
        ->call('save')
        ->assertHasErrors('rating');

    expect(Review::count())->toBe(0);
});

it('menolak ulasan kedua untuk item yang sama', function () {
    $item = itemFor($this->user, OrderStatus::Completed);
    Review::factory()->create(['order_item_id' => $item->id, 'user_id' => $this->user->id]);

    Livewire::actingAs($this->user)->test(ReviewForm::class, ['orderItemId' => $item->id])
        ->set('rating', 4)
        ->call('save')
        ->assertHasErrors('rating');

    expect(Review::count())->toBe(1);
});

it('menolak ulasan untuk item milik orang lain', function () {
    $item = itemFor(User::factory()->create(), OrderStatus::Completed);

    Livewire::actingAs($this->user)->test(ReviewForm::class, ['orderItemId' => $item->id])
        ->set('rating', 4)
        ->call('save')
        ->assertHasErrors('rating');

    expect(Review::count())->toBe(0);
});

it('rating harus 1 sampai 5', function () {
    $item = itemFor($this->user, OrderStatus::Completed);

    Livewire::actingAs($this->user)->test(ReviewForm::class, ['orderItemId' => $item->id])
        ->set('rating', 6)
        ->call('save')
        ->assertHasErrors('rating')
        ->set('rating', 0)
        ->call('save')
        ->assertHasErrors('rating');
});

it('ulasan yang disembunyikan admin tidak tampil di halaman produk', function () {
    $item = itemFor($this->user, OrderStatus::Completed);
    $product = $item->variant->product;
    Review::factory()->create(['product_id' => $product->id, 'comment' => 'Terlihat', 'is_visible' => true]);
    Review::factory()->create(['product_id' => $product->id, 'comment' => 'Disembunyikan', 'is_visible' => false]);

    $this->get(route('products.show', $product))
        ->assertSee('Terlihat')
        ->assertDontSee('Disembunyikan');
});
