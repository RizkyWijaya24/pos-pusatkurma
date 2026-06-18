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

        {{-- DARK MODE INIT — Run before render to prevent flash --}}
        <script>
            (function() {
                const stored = localStorage.getItem('pk-theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
    </head>
    <body class="text-slate-900 dark:text-purple-100 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative overflow-hidden
            bg-gradient-to-tr from-emerald-950 via-teal-900 to-emerald-900
            dark:from-dp-950 dark:via-dp-900 dark:to-dp-850">

            <!-- Light mode decorative bg -->
            <div class="absolute inset-0 pointer-events-none">
                {{-- Light mode blobs --}}
                <div class="dark:hidden absolute top-0 left-0 w-96 h-96 bg-emerald-400/10 rounded-full filter blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
                <div class="dark:hidden absolute bottom-0 right-0 w-96 h-96 bg-teal-400/10 rounded-full filter blur-3xl translate-x-1/3 translate-y-1/3"></div>

                {{-- Dark purple mode ambient glows --}}
                <div class="hidden dark:block absolute top-0 left-1/4 w-[600px] h-[400px] bg-violet-600/10 rounded-full filter blur-3xl -translate-y-1/2"></div>
                <div class="hidden dark:block absolute bottom-0 right-1/4 w-[500px] h-[400px] bg-purple-700/10 rounded-full filter blur-3xl translate-y-1/3"></div>
                <div class="hidden dark:block absolute top-1/2 left-1/2 w-[400px] h-[300px] bg-indigo-600/8 rounded-full filter blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
            </div>

            <!-- Dark Mode Toggle - top right -->
            <div class="absolute top-5 right-5 z-10">
                <button 
                    id="dark-mode-toggle-guest"
                    onclick="toggleDarkMode()"
                    title="Toggle Dark Mode"
                    aria-label="Toggle dark mode"
                    class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 dark:bg-dp-800/80 dark:hover:bg-dp-700/80 backdrop-blur-sm border border-white/20 dark:border-dp-600/60 flex items-center justify-center transition-all duration-200 shadow-lg dark:shadow-dp-950/50"
                >
                    <svg id="guest-sun-icon" class="w-5 h-5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <svg id="guest-moon-icon" class="w-5 h-5 text-violet-400 hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>
            </div>

            <!-- Brand Logo & Title -->
            <div class="mb-6 flex flex-col items-center gap-3 relative z-10">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-400 to-teal-300 flex items-center justify-center shadow-xl shadow-emerald-500/20 animate-bounce dark:shadow-violet-500/20" style="animation-duration: 3s">
                    <span class="text-3xl font-extrabold text-emerald-950">PK</span>
                </div>
                <div class="text-center">
                    <h1 class="text-2xl font-extrabold tracking-tight text-white">Pusat Kurma Cianjur</h1>
                    <p class="text-emerald-300 dark:text-violet-400 text-xs font-semibold tracking-wider uppercase mt-1">Sistem POS & Manajemen Toko</p>
                </div>
            </div>

            <!-- Card -->
            <div class="w-full sm:max-w-md px-8 py-10 relative z-10
                bg-white/95 backdrop-blur-md shadow-2xl border border-emerald-800/10 overflow-hidden sm:rounded-2xl
                dark:bg-dp-850/95 dark:border dark:border-dp-700/60 dark:shadow-dp-950/80"
                style="">
                {{ $slot }}
            </div>
        </div>

        <script>
            function toggleDarkMode() {
                const html = document.documentElement;
                const isDark = html.classList.toggle('dark');
                localStorage.setItem('pk-theme', isDark ? 'dark' : 'light');

                const sunIcon = document.getElementById('guest-sun-icon');
                const moonIcon = document.getElementById('guest-moon-icon');
                if (sunIcon && moonIcon) {
                    if (isDark) {
                        sunIcon.classList.add('hidden');
                        moonIcon.classList.remove('hidden');
                    } else {
                        sunIcon.classList.remove('hidden');
                        moonIcon.classList.add('hidden');
                    }
                }
            }

            // Set correct icon on load
            (function() {
                const isDark = document.documentElement.classList.contains('dark');
                const sunIcon = document.getElementById('guest-sun-icon');
                const moonIcon = document.getElementById('guest-moon-icon');
                if (sunIcon && moonIcon) {
                    if (isDark) {
                        sunIcon.classList.add('hidden');
                        moonIcon.classList.remove('hidden');
                    } else {
                        sunIcon.classList.remove('hidden');
                        moonIcon.classList.add('hidden');
                    }
                }
            })();
        </script>
    </body>
</html>
