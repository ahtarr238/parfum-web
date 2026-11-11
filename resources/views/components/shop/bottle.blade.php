@props(['family' => 'woody', 'seed' => 0])
@php
    $colors = [
        'woody' => ['#c9a27c', '#8b6b4e'],
        'floral' => ['#ecbcc6', '#c0788a'],
        'fresh' => ['#b9dcd2', '#5e9488'],
        'oriental' => ['#e2a27e', '#b0603a'],
        'gourmand' => ['#e3c79c', '#a07c4c'],
    ];
    [$light, $deep] = $colors[$family] ?? $colors['woody'];
    // Variasi bentuk kecil supaya kartu tidak identik.
    $shapes = [
        ['w' => 92, 'h' => 120, 'r' => 14, 'cap' => 34, 'capH' => 30],
        ['w' => 104, 'h' => 108, 'r' => 22, 'cap' => 40, 'capH' => 24],
        ['w' => 80, 'h' => 132, 'r' => 10, 'cap' => 30, 'capH' => 36],
    ];
    $s = $shapes[$seed % 3];
    $x = 100 - $s['w'] / 2;
    $y = 196 - $s['h'];
    $liquidTop = $y + $s['h'] * 0.32;
    $id = 'b'.md5($family.$seed.uniqid());
@endphp
<svg viewBox="0 0 200 240" {{ $attributes->merge(['class' => 'h-full w-full']) }} role="img" aria-label="Ilustrasi botol parfum">
    <defs>
        <linearGradient id="{{ $id }}-liquid" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="{{ $deep }}" stop-opacity="0.92" />
            <stop offset="0.55" stop-color="{{ $light }}" stop-opacity="0.95" />
            <stop offset="1" stop-color="{{ $deep }}" stop-opacity="0.85" />
        </linearGradient>
        <linearGradient id="{{ $id }}-cap" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#2a241d" />
            <stop offset="0.45" stop-color="#5a4f43" />
            <stop offset="1" stop-color="#211c17" />
        </linearGradient>
        <clipPath id="{{ $id }}-clip">
            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $s['w'] }}" height="{{ $s['h'] }}" rx="{{ $s['r'] }}" />
        </clipPath>
    </defs>

    <ellipse cx="100" cy="204" rx="{{ $s['w'] * 0.62 }}" ry="7" fill="#1f1b16" opacity="0.08" />

    {{-- Leher dan tutup --}}
    <rect x="{{ 100 - 9 }}" y="{{ $y - 10 }}" width="18" height="12" rx="2" fill="#d9d2c6" />
    <rect x="{{ 100 - $s['cap'] / 2 }}" y="{{ $y - 10 - $s['capH'] }}" width="{{ $s['cap'] }}" height="{{ $s['capH'] }}" rx="4" fill="url(#{{ $id }}-cap)" />
    <rect x="{{ 100 - $s['cap'] / 2 + 5 }}" y="{{ $y - 10 - $s['capH'] + 4 }}" width="3" height="{{ $s['capH'] - 8 }}" rx="1.5" fill="#fff" opacity="0.18" />

    {{-- Kaca --}}
    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $s['w'] }}" height="{{ $s['h'] }}" rx="{{ $s['r'] }}" fill="#fbf8f2" stroke="#1f1b16" stroke-opacity="0.12" />
    <g clip-path="url(#{{ $id }}-clip)">
        <rect x="{{ $x }}" y="{{ $liquidTop }}" width="{{ $s['w'] }}" height="{{ $s['h'] }}" fill="url(#{{ $id }}-liquid)" />
        <rect x="{{ $x }}" y="{{ $liquidTop }}" width="{{ $s['w'] }}" height="2" fill="#fff" opacity="0.35" />
        <rect x="{{ $x + 8 }}" y="{{ $y + 8 }}" width="6" height="{{ $s['h'] - 16 }}" rx="3" fill="#fff" opacity="0.45" />
        <rect x="{{ $x + $s['w'] - 12 }}" y="{{ $y + 14 }}" width="3" height="{{ $s['h'] - 28 }}" rx="1.5" fill="#fff" opacity="0.2" />
    </g>

    {{-- Label --}}
    <rect x="{{ 100 - $s['w'] * 0.3 }}" y="{{ $y + $s['h'] * 0.5 }}" width="{{ $s['w'] * 0.6 }}" height="{{ $s['h'] * 0.22 }}" rx="2" fill="#fdfbf7" opacity="0.92" />
    <rect x="{{ 100 - $s['w'] * 0.18 }}" y="{{ $y + $s['h'] * 0.58 }}" width="{{ $s['w'] * 0.36 }}" height="2" fill="#1f1b16" opacity="0.5" />
    <rect x="{{ 100 - $s['w'] * 0.12 }}" y="{{ $y + $s['h'] * 0.64 }}" width="{{ $s['w'] * 0.24 }}" height="1.5" fill="#1f1b16" opacity="0.3" />
</svg>
