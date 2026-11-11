@props(['status'])
@php
    $tone = match ($status) {
        \App\Enums\OrderStatus::PendingPayment => 'bg-amber-50 text-amber-800',
        \App\Enums\OrderStatus::Paid, \App\Enums\OrderStatus::Processing => 'bg-sky-50 text-sky-800',
        \App\Enums\OrderStatus::Shipped => 'bg-ember-soft text-ember',
        \App\Enums\OrderStatus::Completed => 'bg-green-50 text-green-800',
        \App\Enums\OrderStatus::Cancelled => 'bg-ink/5 text-ink-2',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex h-7 items-center rounded-md px-2.5 text-[13px] font-medium {$tone}"]) }}>{{ $status->label() }}</span>
