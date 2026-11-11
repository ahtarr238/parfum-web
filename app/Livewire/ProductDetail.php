<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    public ?int $variantId = null;

    public int $qty = 1;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['brand', 'category', 'variants']);

        $this->variantId = $this->product->variants->firstWhere('stock', '>', 0)?->id
            ?? $this->product->variants->first()?->id;
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        return $this->product->variants->firstWhere('id', $this->variantId);
    }

    public function selectVariant(int $id): void
    {
        if ($this->product->variants->contains('id', $id)) {
            $this->variantId = $id;
            $this->qty = 1;
            unset($this->variant);
        }
    }

    public function increment(): void
    {
        $this->qty = min($this->qty + 1, max(1, $this->variant?->stock ?? 1));
    }

    public function decrement(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function addToCart(CartService $cart): void
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('products.show', $this->product));
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if (! $this->variant) {
            return;
        }

        try {
            $cart->add(auth()->user(), $this->variant, $this->qty);
        } catch (CartException $e) {
            $this->addError('qty', $e->getMessage());

            return;
        }

        $this->resetErrorBag();
        $this->dispatch('cart-updated');
        session()->flash('cart-added', $this->variant->label());
    }

    public function toggleWishlist(WishlistService $wishlist): void
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('products.show', $this->product));
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $wishlist->toggle(auth()->user(), $this->product);
        unset($this->inWishlist);
    }

    #[Computed]
    public function inWishlist(): bool
    {
        return (bool) auth()->user()?->wishlistItems()->where('product_id', $this->product->id)->exists();
    }

    public function render(): View
    {
        $reviews = $this->product->visibleReviews()->with('user:id,name')->limit(20)->get();

        return view('livewire.product-detail', [
            'reviews' => $reviews,
            'rating' => $reviews->isEmpty() ? null : round($this->product->visibleReviews()->avg('rating'), 1),
            'reviewCount' => $this->product->visibleReviews()->count(),
            'soldOut' => $this->product->isSoldOut(),
            'related' => Product::query()
                ->with('brand:id,name,slug')
                ->withMin('variants as min_price', 'price')
                ->withSum('variants as total_stock', 'stock')
                ->where('id', '!=', $this->product->id)
                ->where('scent_family', $this->product->scent_family)
                ->where('is_active', true)
                ->limit(4)
                ->get(),
        ])->title($this->product->name);
    }
}
