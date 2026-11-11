<footer class="mt-24 border-t border-line">
    <div class="page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-3">
            <x-application-logo />
            <p class="max-w-xs text-sm leading-6 text-ink-2">Parfum original dan decant, dikirim dari satu gudang ke seluruh Indonesia.</p>
        </div>
        <div>
            <p class="text-sm font-medium text-ink">Belanja</p>
            <ul class="mt-3 space-y-2 text-sm text-ink-2">
                <li><a href="{{ route('categories.show', 'pria') }}" wire:navigate class="hover:text-ink">Parfum pria</a></li>
                <li><a href="{{ route('categories.show', 'wanita') }}" wire:navigate class="hover:text-ink">Parfum wanita</a></li>
                <li><a href="{{ route('categories.show', 'unisex') }}" wire:navigate class="hover:text-ink">Unisex</a></li>
                <li><a href="{{ route('brands.index') }}" wire:navigate class="hover:text-ink">Semua brand</a></li>
            </ul>
        </div>
        <div>
            <p class="text-sm font-medium text-ink">Bantuan</p>
            <ul class="mt-3 space-y-2 text-sm text-ink-2">
                <li>Pembayaran: VA bank, QRIS, e-wallet</li>
                <li>Pengiriman: JNE, J&amp;T, SiCepat, dan lainnya</li>
                <li>Tanya kami lewat chat di pojok kanan bawah</li>
            </ul>
        </div>
        <div>
            <p class="text-sm font-medium text-ink">Akun</p>
            <ul class="mt-3 space-y-2 text-sm text-ink-2">
                @auth
                    <li><a href="{{ route('orders.index') }}" wire:navigate class="hover:text-ink">Pesanan saya</a></li>
                    <li><a href="{{ route('profile') }}" wire:navigate class="hover:text-ink">Profil &amp; alamat</a></li>
                @else
                    <li><a href="{{ route('login') }}" wire:navigate class="hover:text-ink">Masuk</a></li>
                    <li><a href="{{ route('register') }}" wire:navigate class="hover:text-ink">Daftar</a></li>
                @endauth
            </ul>
        </div>
    </div>
    <div class="page border-t border-line py-6 meta">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
</footer>
