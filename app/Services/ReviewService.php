<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;

class ReviewService
{
    /**
     * Hanya untuk item dari order milik user dengan status Selesai, satu review per item.
     */
    public function submit(User $user, int $orderItemId, int $rating, ?string $comment): Review
    {
        $item = OrderItem::with(['order', 'variant', 'review'])->find($orderItemId);

        if (! $item || $item->order->user_id !== $user->id) {
            throw new OrderException('Item pesanan tidak ditemukan.');
        }

        if ($item->order->status !== OrderStatus::Completed) {
            throw new OrderException('Ulasan bisa ditulis setelah pesanan selesai.');
        }

        if ($item->review) {
            throw new OrderException('Kamu sudah mengulas item ini.');
        }

        if (! $item->variant) {
            throw new OrderException('Produk ini sudah tidak dijual.');
        }

        return Review::create([
            'user_id' => $user->id,
            'product_id' => $item->variant->product_id,
            'order_item_id' => $item->id,
            'rating' => $rating,
            'comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
            'is_visible' => true,
        ]);
    }
}
