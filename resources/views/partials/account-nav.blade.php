<nav class="flex gap-1 overflow-x-auto border-b border-line" aria-label="Akun">
    @foreach ([
        ['orders.index', 'Pesanan', 'pesanan*'],
        ['wishlist', 'Wishlist', 'wishlist'],
        ['addresses', 'Alamat', 'akun/alamat'],
        ['profile', 'Profil', 'profile'],
    ] as [$route, $label, $pattern])
        <a href="{{ route($route) }}" wire:navigate
           @class([
               '-mb-px whitespace-nowrap border-b-2 px-3 pb-3 text-[15px] transition-colors duration-150',
               'border-ink text-ink' => request()->is($pattern),
               'border-transparent text-ink-2 hover:text-ink' => ! request()->is($pattern),
           ])>{{ $label }}</a>
    @endforeach
</nav>
