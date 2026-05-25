<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pusat Kurma Cianjur') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: 'Outfit', sans-serif;
            }
        </style>
    </head>
    <body class="text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-tr from-emerald-950 via-teal-900 to-emerald-900">
            <div class="mb-6 flex flex-col items-center gap-3">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-400 to-teal-300 flex items-center justify-center shadow-xl shadow-emerald-500/20 animate-bounce" style="animation-duration: 3s">
                    <span class="text-3xl font-extrabold text-emerald-950">PK</span>
                </div>
                <div class="text-center">
                    <h1 class="text-2xl font-extrabold tracking-tight text-white">Pusat Kurma Cianjur</h1>
                    <p class="text-emerald-300 text-xs font-semibold tracking-wider uppercase mt-1">Sistem POS & Manajemen Toko</p>
                </div>
            </div>

            <div class="w-full sm:max-w-md px-8 py-10 bg-white/95 backdrop-blur-md shadow-2xl border border-emerald-800/10 overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
