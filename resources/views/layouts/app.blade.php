<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|instrument-serif:400&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-screen">
        <div class="flex min-h-screen flex-col">
            <livewire:layout.navigation />

            @if (isset($header))
                <header class="page pt-10 pb-2">
                    {{ $header }}
                </header>
            @endif

            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-shop.footer />
        </div>

        @stack('modals')
    </body>
</html>
