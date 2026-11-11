<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class WishlistService
{
    /**
     * @return bool true jika produk sekarang ada di wishlist
     */
    public function toggle(User $user, Product $product): bool
    {
        $deleted = $user->wishlistItems()->where('product_id', $product->id)->delete();

        if ($deleted) {
            return false;
        }

        $user->wishlistItems()->firstOrCreate(['product_id' => $product->id]);

        return true;
    }
}
