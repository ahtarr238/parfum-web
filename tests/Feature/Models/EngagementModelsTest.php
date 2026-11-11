<?php

use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\QueryException;

it('hanya satu review per item order', function () {
    $review = Review::factory()->create();

    expect(fn () => Review::factory()->create(['order_item_id' => $review->order_item_id]))
        ->toThrow(QueryException::class);
});

it('menyimpan percakapan guest dengan status bot', function () {
    $conversation = Conversation::create(['session_id' => 'abc123']);

    expect($conversation->fresh()->user_id)->toBeNull()
        ->and($conversation->fresh()->status)->toBe(ConversationStatus::Bot);
});

it('pesan terurut dan sender ter-cast', function () {
    $conversation = Conversation::factory()->create();
    Message::factory()->for($conversation)->create(['sender_type' => 'customer', 'body' => 'satu']);
    Message::factory()->for($conversation)->create(['sender_type' => 'bot', 'body' => 'dua']);

    $messages = $conversation->fresh()->messages;

    expect($messages->pluck('body')->all())->toBe(['satu', 'dua'])
        ->and($messages->first()->sender_type)->toBe(MessageSender::Customer);
});

it('reviews produk hanya milik produk itu', function () {
    $product = Product::factory()->create();
    Review::factory()->count(2)->create(['product_id' => $product->id]);
    Review::factory()->create();

    expect($product->reviews)->toHaveCount(2);
});
