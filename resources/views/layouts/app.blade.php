<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50" translate="no">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google" content="notranslate">

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

            /* GLOBAL TOAST PREMIUM GLASSMORPHISM THEME */
            #global-toast {
                position: fixed;
                top: 5rem; /* 80px - beautiful placement below h-16 (64px) navbar */
                right: 1.25rem; /* 20px */
                z-index: 999999;
                max-width: 24rem;
                width: calc(100% - 2.5rem);
                background-color: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border-radius: 1rem;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
                border: 1px solid rgba(241, 245, 249, 0.8);
                padding: 1rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                overflow: hidden;
                transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
                transform: translateX(150%);
                opacity: 0;
                pointer-events: none;
                box-sizing: border-box;
            }

            #global-toast.show {
                transform: translateX(0);
                opacity: 1;
                pointer-events: auto;
            }
        </style>
    </head>
    <body class="h-full text-slate-800 antialiased overflow-x-hidden" x-data="{ sidebarOpen: false }">

        <!-- Impersonation Banner -->
        @if (session()->has('impersonate_original_user_id'))
            <div class="bg-amber-500 text-white px-4 py-2.5 text-xs sm:text-sm font-bold flex items-center justify-between shadow-md sticky top-0 z-50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
                    </svg>
                    <span>Anda sedang mengakses akun: <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->email }})</span>
                </div>
                <form method="POST" action="{{ route('admin.impersonate.leave') }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-white hover:bg-slate-100 text-amber-700 px-3 py-1.5 rounded-xl font-black text-xs transition duration-150 shadow-sm active:scale-95">
                        Kembali ke Admin
                    </button>
                </form>
            </div>
        @endif

        <!-- Laravel Session Flash to Global Toast triggers -->
        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => {
                        if (window.showToast) {
                            window.showToast("{{ session('success') }}", 'success');
                        } else {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: "{{ session('success') }}", type: 'success' } }));
                        }
                    }, 200);
                });
            </script>
        @endif

        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => {
                        if (window.showToast) {
                            window.showToast("{{ session('error') }}", 'error');
                        } else {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: "{{ session('error') }}", type: 'error' } }));
                        }
                    }, 200);
                });
            </script>
        @endif

        <!-- Sidebar for Mobile (Off-canvas) -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="relative z-50 md:hidden" 
             role="dialog" 
             aria-modal="true" 
             style="display: none;">
            
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="sidebarOpen = false"></div>

            <div class="fixed inset-y-0 left-0 flex w-full max-w-xs transition duration-300 transform bg-emerald-950 text-white shadow-2xl">
                <!-- Mobile Sidebar Content -->
                <div class="relative flex flex-1 flex-col px-6 py-6 overflow-y-auto">
                    <!-- Close button -->
                    <div class="absolute top-5 right-5">
                        <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5 text-emerald-200 hover:text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Brand / Title -->
                    <div class="flex items-center gap-3 py-4 border-b border-emerald-800/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-400 to-teal-300 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <span class="text-xl font-bold text-emerald-900">PK</span>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold bg-gradient-to-r from-white to-emerald-200 bg-clip-text text-transparent">Pusat Kurma</h1>
                            <p class="text-xs text-emerald-300 tracking-wide font-medium">Cianjur</p>
                        </div>
                    </div>

                    <!-- Navigation Links inside Mobile Sidebar -->
                    <nav class="mt-8 flex flex-col gap-2">
                        @include('layouts.navigation-links')
                    </nav>

                    <!-- Footer / Account details in Mobile Sidebar -->
                    <div class="mt-auto pt-6 border-t border-emerald-800/50 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-800 flex items-center justify-center font-bold text-emerald-200 border border-emerald-700">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm leading-tight text-white">{{ Auth::user()->name }}</h4>
                                <span class="inline-flex mt-1 px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase rounded bg-emerald-500/25 text-emerald-300 border border-emerald-500/35">
                                    {{ Auth::user()->role }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Static Sidebar for Desktop -->
        <div class="hidden md:fixed md:inset-y-0 md:z-20 md:flex md:w-64 md:flex-col bg-emerald-950 text-white border-r border-emerald-900/30">
            <div class="flex flex-col flex-grow px-6 py-6 overflow-y-auto">
                
                <!-- Brand / Logo -->
                <div class="flex items-center gap-3 py-4 border-b border-emerald-900/50">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-400 to-teal-300 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <span class="text-xl font-bold text-emerald-900">PK</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold bg-gradient-to-r from-white to-emerald-200 bg-clip-text text-transparent">Pusat Kurma</h1>
                        <p class="text-xs text-emerald-300 tracking-wide font-medium">Cianjur</p>
                    </div>
                </div>

                <!-- Desktop Sidebar Navigation -->
                <nav class="mt-8 flex flex-col gap-2">
                    @include('layouts.navigation-links')
                </nav>

                <!-- Sidebar Footer -->
                <div class="mt-auto pt-6 border-t border-emerald-900/50 flex flex-col gap-3">
                    <div class="flex items-center gap-3 p-2 rounded-xl bg-emerald-900/30 border border-emerald-800/10">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-700 to-teal-600 flex items-center justify-center font-bold text-emerald-100 shadow-md">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="font-semibold text-sm leading-none text-white truncate">{{ Auth::user()->name }}</h4>
                            <span class="inline-flex mt-1.5 px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                {{ Auth::user()->role }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="md:pl-64 flex flex-col min-h-screen">
            
            <!-- Desktop/Mobile Top Header -->
            <header class="bg-white/80 backdrop-blur-md sticky top-0 z-10 flex h-16 shrink-0 items-center justify-between gap-x-4 border-b border-slate-100 px-4 sm:px-6 lg:px-8 shadow-sm">
                <!-- Left: Toggle menu for Mobile, Page Title for Desktop -->
                <div class="flex items-center gap-x-4">
                    <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-slate-500 hover:text-slate-900 md:hidden">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Header dynamic title slot -->
                    @isset($header)
                        <div class="font-semibold text-lg sm:text-xl text-slate-800 leading-tight">
                            {{ $header }}
                        </div>
                    @else
                        <div class="font-semibold text-lg sm:text-xl text-slate-800 leading-tight">
                            Pusat Kurma Cianjur
                        </div>
                    @endisset
                </div>

                <!-- Right Side Profile & Actions -->
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    
                    <!-- Notification bell -->
                    <button class="relative rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-50 transition duration-150">
                        <span class="absolute top-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                    </button>

                    <!-- Divider -->
                    <div class="h-6 w-px bg-slate-100" aria-hidden="true"></div>

                    <!-- Settings Dropdown -->
                    <div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
                        <button type="button" @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-50 transition duration-150">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs shadow-inner">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden lg:flex lg:items-center">
                                <span class="text-sm font-medium text-slate-700">{{ Auth::user()->name }}</span>
                                <svg class="ms-2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </button>

                        <!-- Dropdown panel -->
                        <div x-show="dropdownOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 z-30 mt-2 w-48 origin-top-right rounded-xl bg-white p-1 shadow-lg ring-1 ring-black/5 focus:outline-none"
                             style="display: none;">
                            
                            <!-- Profile Link -->
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 rounded-lg font-medium transition duration-150">
                                {{ __('Your Profile') }}
                            </a>

                            <!-- Divider -->
                            <div class="my-1 border-t border-slate-100"></div>

                            <!-- Logout Form -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left block px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 rounded-lg font-medium transition duration-150">
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main view container -->
            <main class="flex-grow py-8 px-4 sm:px-6 lg:px-8 pb-24 md:pb-8">
                <!-- Page Slot -->
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <!-- Mobile Touch-Friendly Bottom Navigation Bar (Optimized for Mobile Thumbs) -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-100 shadow-2xl flex justify-around items-center py-2 px-2 z-30">
            <!-- Home/Dashboard -->
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 {{ (request()->routeIs('dashboard') || request()->routeIs('kasir.dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('owner.dashboard')) ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }} hover:text-emerald-800 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Dashboard</span>
            </a>

            @if(Auth::user()->isAdmin())
            <!-- Riwayat Transaksi (Admin) -->
            <a href="{{ route('admin.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('admin.transactions.*') ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }} hover:text-emerald-700 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Riwayat</span>
            </a>
            @elseif(Auth::user()->isOwner())
            <!-- Riwayat Transaksi (Owner) -->
            <a href="{{ route('owner.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('owner.transactions.*') ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }} hover:text-emerald-700 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Riwayat</span>
            </a>
            @elseif(Auth::user()->isKasir())
            <!-- Riwayat Transaksi (Kasir) -->
            <a href="{{ route('kasir.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('kasir.transactions.*') ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }} hover:text-emerald-700 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Riwayat</span>
            </a>
            @endif

            <!-- Profile -->
            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('profile.edit') ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }} hover:text-emerald-700 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Profile</span>
            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="flex flex-col items-center">
                @csrf
                <button type="submit" class="flex flex-col items-center gap-0.5 text-rose-500 hover:text-rose-600 transition duration-150 py-1 px-3 rounded-xl">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span class="text-[10px] font-bold tracking-wide">Keluar</span>
                </button>
            </form>
        </nav>

        <!-- GLOBAL TOAST NOTIFICATION SYSTEM -->
        <div id="global-toast">
             <!-- Colored Indicator Line on Left -->
             <div id="toast-indicator" class="absolute left-0 top-0 bottom-0 w-1.5"></div>

             <!-- Icon Wrapper -->
             <div id="toast-icon-bg" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                 <!-- Icon SVG is injected here -->
                 <span id="toast-icon-slot"></span>
             </div>

             <!-- Message content -->
             <div class="flex-grow">
                 <p id="toast-message" style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 0.875rem; font-weight: 700; color: #1e293b; line-height: 1.25rem;"></p>
             </div>

             <!-- Close button -->
             <button type="button" onclick="hideGlobalToast()" style="background: none; border: none; padding: 0; cursor: pointer; color: #94a3b8; transition: color 0.2s;" onmouseover="this.style.color='#475569'" onmouseout="this.style.color='#94a3b8'" class="shrink-0">
                 <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
        </div>

        <script>
            let globalToastTimer = null;

            const toastIcons = {
                success: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
                warning: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>`,
                info: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>`,
                error: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
            };

            const toastColors = {
                success: { indicator: '#10b981', bg: '#ecfdf5', text: '#059669' },
                warning: { indicator: '#f59e0b', bg: '#fffbeb', text: '#d97706' },
                info: { indicator: '#0ea5e9', bg: '#f0f9ff', text: '#0284c7' },
                error: { indicator: '#f43f5e', bg: '#fff1f2', text: '#e11d48' }
            };

            function showGlobalToast(message, type = 'success') {
                const toast = document.getElementById('global-toast');
                const indicator = document.getElementById('toast-indicator');
                const iconBg = document.getElementById('toast-icon-bg');
                const iconSlot = document.getElementById('toast-icon-slot');
                const msgEl = document.getElementById('toast-message');

                if (!toast) return;

                // Clear previous timer
                if (globalToastTimer) {
                    clearTimeout(globalToastTimer);
                }

                // Update content
                msgEl.innerText = message;
                iconSlot.innerHTML = toastIcons[type] || toastIcons.success;

                // Update colors using inline styles for absolute reliability
                indicator.style.backgroundColor = toastColors[type].indicator;
                iconBg.style.backgroundColor = toastColors[type].bg;
                iconBg.style.color = toastColors[type].text;

                // Show toast (slide-in)
                toast.classList.add('show');

                // Auto hide
                globalToastTimer = setTimeout(() => {
                    hideGlobalToast();
                }, 2800);
            }

            function hideGlobalToast() {
                const toast = document.getElementById('global-toast');
                if (toast) {
                    toast.classList.remove('show');
                }
                globalToastTimer = null;
            }

            // Global window-level listener
            window.addEventListener('toast', (e) => {
                showGlobalToast(e.detail.message, e.detail.type);
            });

            // Make toast helper functions globally accessible on the window
            window.showToast = showGlobalToast;
            window.hideToast = hideGlobalToast;
        </script>

    </body>
</html>
