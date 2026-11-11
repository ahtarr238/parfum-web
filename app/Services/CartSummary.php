<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Collection;

final class CartSummary
{
    /**
     * @param  Collection<int, CartItem>  $items  setiap item punya properti dinamis `problem` (?string)
     */
    public function __construct(
        public readonly Collection $items,
        public readonly int $subtotal,
        public readonly int $totalWeight,
        public readonly int $itemCount,
        public readonly bool $hasProblems,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
