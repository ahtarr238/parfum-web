<x-app-layout>
    <x-slot name="title">Brand</x-slot>
    <div class="page pt-10">
        <h1 class="font-serif text-4xl sm:text-5xl">Brand</h1>
        <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($brands as $brand)
                <a href="{{ route('brands.show', $brand) }}" wire:navigate
                   class="card flex items-center gap-4 p-5 transition-colors duration-150 hover:border-ink/25">
                    @if ($brand->logo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo) }}" alt="" loading="lazy" class="size-12 rounded-lg object-contain">
                    @else
                        <span class="flex size-12 items-center justify-center rounded-lg bg-paper-raised font-serif text-xl text-ink-2">{{ mb_substr($brand->name, 0, 1) }}</span>
                    @endif
                    <span>
                        <span class="block text-[15px] font-medium">{{ $brand->name }}</span>
                        <span class="meta tabular-nums">{{ $brand->products_count }} parfum</span>
                    </span>
                </a>
            @empty
                <p class="text-ink-2">Belum ada brand.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
