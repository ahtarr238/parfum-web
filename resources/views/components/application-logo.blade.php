<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-[17px] font-medium tracking-normal text-ink']) }}>
    <svg viewBox="0 0 20 24" class="h-5 w-auto" aria-hidden="true">
        <rect x="7" y="1" width="6" height="4" rx="1" fill="currentColor" />
        <path d="M4 8.5C4 7.1 5.1 6 6.5 6h7C14.9 6 16 7.1 16 8.5V20a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V8.5Z" fill="none" stroke="currentColor" stroke-width="1.6" />
        <path d="M5.6 14h8.8v6a1.6 1.6 0 0 1-1.6 1.6H7.2A1.6 1.6 0 0 1 5.6 20v-6Z" fill="#8a4b1f" />
    </svg>
    <span>{{ config('app.name') }}</span>
</span>
