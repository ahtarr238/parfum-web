<?php

use App\Livewire\Actions\Logout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public int $cartCount = 0;

    public function mount(): void
    {
        $this->refreshCart();
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->cartCount = (int) (auth()->user()?->cartItems()->sum('qty') ?? 0);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false, search: false }" class="sticky top-0 z-30 border-b border-line bg-paper/95 backdrop-blur supports-[backdrop-filter]:bg-paper/80">
    <div class="page flex h-16 items-center gap-6">
        <a href="{{ route('home') }}" wire:navigate class="shrink-0">
            <x-application-logo />
        </a>

        <div class="hidden items-center gap-1 md:flex">
            @foreach (['pria' => 'Pria', 'wanita' => 'Wanita', 'unisex' => 'Unisex'] as $slug => $label)
                <a href="{{ route('categories.show', $slug) }}" wire:navigate
                   @class(['rounded-lg px-3 py-2 text-[15px] transition-colors duration-150',
                       'text-ink bg-ink/5' => request()->is('kategori/'.$slug),
                       'text-ink-2 hover:text-ink' => ! request()->is('kategori/'.$slug)])>{{ $label }}</a>
            @endforeach
            <a href="{{ route('brands.index') }}" wire:navigate
               @class(['rounded-lg px-3 py-2 text-[15px] transition-colors duration-150',
                   'text-ink bg-ink/5' => request()->is('brand*'),
                   'text-ink-2 hover:text-ink' => ! request()->is('brand*')])>Brand</a>
        </div>

        <form action="{{ route('catalog') }}" method="GET" class="ms-auto hidden w-full max-w-xs lg:block" role="search">
            <label for="nav-search" class="sr-only">Cari parfum</label>
            <div class="relative">
                <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" stroke-width="1.5" />
                <input id="nav-search" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama atau brand"
                       class="field h-9 pl-9 text-sm" autocomplete="off">
            </div>
        </form>

        <div class="ms-auto flex items-center gap-1 lg:ms-0">
            <button type="button" @click="search = ! search" class="inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-ink/5 hover:text-ink lg:hidden" aria-label="Cari">
                <x-lucide-search class="size-[18px]" stroke-width="1.5" />
            </button>

            @auth
                <a href="{{ route('wishlist') }}" wire:navigate class="hidden size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-ink/5 hover:text-ink sm:inline-flex" aria-label="Wishlist">
                    <x-lucide-heart class="size-[18px]" stroke-width="1.5" />
                </a>
            @endauth

            <a href="{{ route('cart') }}" wire:navigate class="relative inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-ink/5 hover:text-ink" aria-label="Keranjang">
                <x-lucide-shopping-bag class="size-[18px]" stroke-width="1.5" />
                @if ($cartCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 min-w-[18px] rounded-full bg-ember px-1 text-center text-[11px] font-medium leading-[18px] text-white tabular-nums" data-testid="cart-count">{{ $cartCount }}</span>
                @endif
            </a>

            @auth
                <div class="relative hidden sm:block" x-data="{ menu: false }" @click.outside="menu = false">
                    <button type="button" @click="menu = ! menu" class="inline-flex h-9 items-center gap-2 rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5 hover:text-ink">
                        <span x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name" class="max-w-[10rem] truncate"></span>
                        <x-lucide-chevron-down class="size-4" stroke-width="1.5" />
                    </button>
                    <div x-cloak x-show="menu" x-transition.opacity.duration.150ms
                         class="absolute right-0 mt-2 w-52 rounded-xl border border-line bg-paper-card p-1 shadow-float">
                        <a href="{{ route('orders.index') }}" wire:navigate class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">
                            <x-lucide-package class="size-4" stroke-width="1.5" /> Pesanan saya
                        </a>
                        <a href="{{ route('wishlist') }}" wire:navigate class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">
                            <x-lucide-heart class="size-4" stroke-width="1.5" /> Wishlist
                        </a>
                        <a href="{{ route('profile') }}" wire:navigate class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">
                            <x-lucide-user class="size-4" stroke-width="1.5" /> Profil &amp; alamat
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ url('/admin') }}" class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">
                                <x-lucide-layout-dashboard class="size-4" stroke-width="1.5" /> Panel admin
                            </a>
                        @endif
                        <div class="my-1 border-t border-line"></div>
                        <button wire:click="logout" class="flex h-9 w-full items-center gap-2.5 rounded-lg px-3 text-sm text-ink-2 hover:bg-ink/5 hover:text-ink">
                            <x-lucide-log-out class="size-4" stroke-width="1.5" /> Keluar
                        </button>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" wire:navigate class="hidden h-9 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:text-ink sm:inline-flex">Masuk</a>
            @endauth

            <button type="button" @click="open = ! open" class="inline-flex size-9 items-center justify-center rounded-lg text-ink-2 hover:bg-ink/5 hover:text-ink md:hidden" aria-label="Menu">
                <x-lucide-menu class="size-[18px]" stroke-width="1.5" x-show="! open" />
                <x-lucide-x class="size-[18px]" stroke-width="1.5" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- Search mobile --}}
    <div x-cloak x-show="search" class="border-t border-line lg:hidden">
        <form action="{{ route('catalog') }}" method="GET" class="page py-3" role="search">
            <label for="nav-search-mobile" class="sr-only">Cari parfum</label>
            <input id="nav-search-mobile" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama atau brand" class="field h-10">
        </form>
    </div>

    {{-- Menu mobile --}}
    <div x-cloak x-show="open" class="border-t border-line md:hidden">
        <div class="page space-y-1 py-3">
            @foreach (['pria' => 'Pria', 'wanita' => 'Wanita', 'unisex' => 'Unisex'] as $slug => $label)
                <a href="{{ route('categories.show', $slug) }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">{{ $label }}</a>
            @endforeach
            <a href="{{ route('brands.index') }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Brand</a>
            <div class="my-2 border-t border-line"></div>
            @auth
                <a href="{{ route('orders.index') }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Pesanan saya</a>
                <a href="{{ route('wishlist') }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Wishlist</a>
                <a href="{{ route('profile') }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Profil &amp; alamat</a>
                <button wire:click="logout" class="flex h-10 w-full items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Keluar</button>
            @else
                <a href="{{ route('login') }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Masuk</a>
                <a href="{{ route('register') }}" wire:navigate class="flex h-10 items-center rounded-lg px-3 text-[15px] text-ink-2 hover:bg-ink/5">Daftar</a>
            @endauth
        </div>
    </div>
</nav>
