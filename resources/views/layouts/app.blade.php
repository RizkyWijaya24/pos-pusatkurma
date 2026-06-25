@php
    $user = auth()->user();
    $dbNotifications = collect();
    $unreadCount = 0;
    $lowStockAlerts = collect();
    $lowStockCount = 0;

    if ($user) {
        $dbNotifications = $user->notifications()->take(5)->get();
        $unreadCount = $user->unreadNotifications()->count();

        // Query dasar low stock (stok di bawah atau sama dengan 10, tapi di atas 0)
        $lowStockQuery = \App\Models\ProductStock::with(['product', 'location'])
            ->where('stock', '<=', 10)
            ->where('stock', '>', 0);

        if ($user->isAdmin() || $user->isOwner()) {
            $lowStockQuery->whereHas('location', function($q) {
                $q->active();
            });
        } else {
            $myLocation = \App\Models\StockLocation::findByBranchName($user->branch);
            if ($myLocation) {
                $lowStockQuery->where('location_id', $myLocation->id);
            } else {
                $lowStockQuery->whereRaw('1 = 0');
            }
        }

        // Dapatkan jumlah total item kritis untuk badge
        $lowStockCount = $lowStockQuery->count();
        
        // Ambil maksimal 5 item kritis teratas untuk dirender di dropdown
        $lowStockAlerts = $lowStockQuery->orderBy('stock', 'asc')->take(5)->get();
    }
    $totalAlertCount = $unreadCount + $lowStockCount;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 dark:bg-dp-950" translate="no">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google" content="notranslate">

        <title>@hasSection('title') @yield('title') | @endif {{ config('app.name', 'Pusat Kurma Cianjur') }}</title>

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

            /* GLOBAL TOAST */
            #global-toast {
                position: fixed;
                top: 5rem;
                right: 1.25rem;
                z-index: 999999;
                max-width: 24rem;
                width: calc(100% - 2.5rem);
                background-color: rgba(255, 255, 255, 0.97);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
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
    <body class="h-full text-slate-800 dark:text-purple-100 antialiased overflow-x-hidden bg-slate-50 dark:bg-dp-950" x-data="{ sidebarOpen: false }">

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
            
            <div class="fixed inset-0 bg-dp-950/80 dark:bg-dp-950/90 backdrop-blur-sm" @click="sidebarOpen = false"></div>

            <div class="fixed inset-y-0 left-0 flex w-full max-w-xs transition duration-300 transform bg-emerald-950 text-white shadow-2xl">
                <div class="relative flex flex-1 flex-col px-6 py-6 overflow-y-auto">
                    <!-- Close button -->
                    <div class="absolute top-5 right-5">
                        <button type="button" @click="sidebarOpen = false" class="no-transition -m-2.5 p-2.5 text-emerald-200 hover:text-white">
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
            
            <!-- Top Header -->
            <header class="bg-white/80 dark:bg-dp-900/85 backdrop-blur-md sticky top-0 z-10 flex h-16 shrink-0 items-center justify-between gap-x-4 border-b border-slate-100 dark:border-dp-700/60 px-4 sm:px-6 lg:px-8 shadow-sm">
                <!-- Left: Toggle menu for Mobile, Page Title for Desktop -->
                <div class="flex items-center gap-x-4">
                    <button type="button" @click="sidebarOpen = true" class="no-transition -m-2.5 p-2.5 text-slate-500 dark:text-purple-300 hover:text-slate-900 dark:hover:text-purple-100 md:hidden">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Header dynamic title slot -->
                    @isset($header)
                        <div class="font-semibold text-lg sm:text-xl text-slate-800 dark:text-purple-100 leading-tight">
                            {{ $header }}
                        </div>
                    @else
                        <div class="font-semibold text-lg sm:text-xl text-slate-800 dark:text-purple-100 leading-tight">
                            @yield('title', 'Pusat Kurma Cianjur')
                        </div>
                    @endisset
                </div>

                <!-- Right Side Profile & Actions -->
                <div class="flex items-center gap-x-3 lg:gap-x-5">
                    
                    <!-- Notification dropdown -->
                    <div class="relative" x-data="{ notifOpen: false }" @click.away="notifOpen = false">
                        <button type="button" @click="notifOpen = !notifOpen" class="relative rounded-full p-2 text-slate-400 dark:text-purple-400 hover:text-slate-500 dark:hover:text-purple-200 hover:bg-slate-100 dark:hover:bg-dp-800 transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            @if($totalAlertCount > 0)
                                <span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-dp-900 animate-pulse">
                                    {{ $totalAlertCount }}
                                </span>
                            @endif
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>

                        <div 
                            x-show="notifOpen" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-3 origin-top-right rounded-2xl bg-white dark:bg-dp-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10 z-50 border border-slate-100 dark:border-dp-700 overflow-hidden"
                            style="display: none; width: 480px; max-width: calc(100vw - 24px);"
                        >
                            <!-- Header -->
                            <div class="px-4 py-3 bg-slate-50 dark:bg-dp-750 border-b border-slate-100 dark:border-dp-700 flex items-center justify-between">
                                <span class="font-bold text-sm text-slate-800 dark:text-purple-100">Notifikasi & Peringatan</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline">
                                            Tandai semua dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- List Container -->
                            <div class="divide-y divide-slate-100 dark:divide-dp-700" style="max-height: 320px; overflow-y: auto !important; -webkit-overflow-scrolling: touch; position: relative;">
                                
                                {{-- SECTION 1: DATABASE NOTIFICATIONS (LOG & REQUEST TRANSFER) --}}
                                @if($dbNotifications->isNotEmpty())
                                    <div class="px-4 py-1.5 bg-slate-100/50 dark:bg-dp-750/30 text-[10px] font-bold text-slate-400 dark:text-purple-400 tracking-wider uppercase">
                                        Log & Transfer Stok
                                    </div>
                                    @foreach($dbNotifications as $n)
                                        <a href="{{ route('notifications.read', $n->id) }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-dp-700 transition duration-150 {{ !$n->read_at ? 'bg-emerald-50/40 dark:bg-emerald-950/10' : '' }}">
                                            <!-- Icon wrapper based on status -->
                                            <div class="flex-shrink-0">
                                                @php
                                                    $actType = $n->data['action_type'] ?? '';
                                                    $iconBg = 'bg-slate-100 dark:bg-dp-600 text-slate-500 dark:text-purple-300';
                                                    if ($actType === 'created') $iconBg = 'bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400';
                                                    elseif ($actType === 'approved') $iconBg = 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400';
                                                    elseif ($actType === 'rejected') $iconBg = 'bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400';
                                                    elseif ($actType === 'cancelled') $iconBg = 'bg-slate-100 dark:bg-dp-600 text-slate-500 dark:text-purple-300';
                                                @endphp
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $iconBg }}">
                                                    @if($actType === 'created')
                                                        <!-- Exclamation Icon -->
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                                        </svg>
                                                    @elseif($actType === 'approved')
                                                        <!-- Check Icon -->
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    @elseif($actType === 'rejected')
                                                        <!-- X Icon -->
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    @else
                                                        <!-- Bell Icon -->
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </div>
                                            <!-- Message and Time -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-slate-600 dark:text-purple-200 leading-normal">
                                                    {!! $n->data['message'] ?? 'Notifikasi transfer stok.' !!}
                                                </p>
                                                <span class="text-[10px] text-slate-400 dark:text-purple-400 mt-1 block">
                                                    {{ $n->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <!-- Unread dot -->
                                            @if(!$n->read_at)
                                                <div class="flex-shrink-0 flex items-center">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                </div>
                                            @endif
                                        </a>
                                    @endforeach
                                @endif

                                {{-- SECTION 2: LOW STOCK ALERTS --}}
                                @if($lowStockAlerts->isNotEmpty())
                                    <div class="px-4 py-1.5 bg-slate-100/50 dark:bg-dp-750/30 text-[10px] font-bold text-slate-400 dark:text-purple-400 tracking-wider uppercase">
                                        Peringatan Stok Menipis
                                    </div>
                                    @foreach($lowStockAlerts as $ps)
                                        @php
                                            $destUrl = auth()->user()->isKasir() 
                                                ? route('kasir.stock-request') 
                                                : route('admin.stock-transfers.create', ['product_id' => $ps->product_id]);
                                        @endphp
                                        <a href="{{ $destUrl }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-dp-700 transition duration-150 bg-rose-50/20 dark:bg-rose-950/5">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                                    <!-- Warning Triangle Icon -->
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-slate-700 dark:text-purple-200 leading-normal">
                                                    @if(auth()->user()->isKasir())
                                                        Stok <strong>{{ $ps->product->name }}</strong> di cabang Anda menipis. Sisa: <strong class="text-rose-600 dark:text-rose-400">{{ floatval($ps->stock) }} {{ $ps->product->price_unit }}</strong>.
                                                    @else
                                                        Stok <strong>{{ $ps->product->name }}</strong> di <strong>{{ $ps->location->name }}</strong> menipis. Sisa: <strong class="text-rose-600 dark:text-rose-400">{{ floatval($ps->stock) }} {{ $ps->product->price_unit }}</strong>.
                                                    @endif
                                                </p>
                                                <span class="text-[10px] text-rose-500 dark:text-rose-400 font-semibold mt-1 block">
                                                    Klik untuk @if(auth()->user()->isKasir()) ajukan transfer stok @else kirim transfer stok @endif
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                @endif

                                {{-- EMPTY STATE --}}
                                @if($dbNotifications->isEmpty() && $lowStockAlerts->isEmpty())
                                    <div class="px-4 py-8 text-center">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-dp-700 text-slate-400 dark:text-purple-400 flex items-center justify-center mx-auto mb-3">
                                            <!-- Check Circle Icon -->
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700 dark:text-purple-200">Semua Aman!</p>
                                        <p class="text-xs text-slate-400 dark:text-purple-400 mt-0.5">Tidak ada notifikasi atau peringatan stok saat ini.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- DARK MODE TOGGLE BUTTON -->
                    <button 
                        id="dark-mode-toggle"
                        onclick="toggleDarkMode()"
                        title="Toggle Dark Mode"
                        aria-label="Toggle dark mode"
                        class="w-9 h-9 rounded-full border border-slate-200 dark:border-dp-600 bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-purple-300 hover:bg-amber-50 dark:hover:bg-dp-700 hover:border-amber-300 dark:hover:border-violet-500 transition-all duration-200 shadow-sm"
                    >
                        <!-- Sun Icon (light mode) -->
                        <span class="sun-icon">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                        </span>
                        <!-- Moon Icon (dark mode) -->
                        <span class="moon-icon">
                            <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                        </span>
                    </button>

                    <!-- Divider -->
                    <div class="h-6 w-px bg-slate-100 dark:bg-dp-700" aria-hidden="true"></div>

                    <!-- Settings Dropdown -->
                    <div class="relative" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
                        <button type="button" @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-dp-800 transition duration-150">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 flex items-center justify-center font-bold text-xs shadow-inner">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden lg:flex lg:items-center">
                                <span class="text-sm font-medium text-slate-700 dark:text-purple-200">{{ Auth::user()->name }}</span>
                                <svg class="ms-2 h-4 w-4 text-slate-400 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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
                             class="absolute right-0 z-30 mt-2 w-52 origin-top-right rounded-xl bg-white dark:bg-dp-800 p-1 shadow-lg dark:shadow-dp-950/80 ring-1 ring-black/5 dark:ring-dp-600/50 focus:outline-none border border-transparent dark:border-dp-700/60"
                             style="display: none;">
                            
                            <!-- User info header in dropdown -->
                            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-dp-700/60 mb-1">
                                <p class="text-xs font-bold text-slate-800 dark:text-purple-100 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-purple-400 truncate">{{ Auth::user()->email }}</p>
                            </div>

                            <!-- Profile Link -->
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 dark:text-purple-200 hover:bg-slate-50 dark:hover:bg-dp-700/60 rounded-lg font-medium transition duration-150">
                                <svg class="w-4 h-4 text-slate-400 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                {{ __('Your Profile') }}
                            </a>

                            <!-- Divider -->
                            <div class="my-1 border-t border-slate-100 dark:border-dp-700/60"></div>

                            <!-- Logout Form -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 text-left px-4 py-2.5 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg font-medium transition duration-150">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                    </svg>
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main view container -->
            <main class="flex-grow py-8 px-4 sm:px-6 lg:px-8 pb-24 md:pb-8 bg-slate-50 dark:bg-transparent">
                <div class="max-w-[90rem] mx-auto">
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </main>
        </div>

        <!-- Mobile Bottom Navigation Bar -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 dark:bg-dp-900/97 backdrop-blur-md border-t border-slate-100 dark:border-dp-700/50 shadow-2xl flex justify-around items-center py-2 px-2 z-30">
            <!-- Home/Dashboard -->
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 {{ (request()->routeIs('dashboard') || request()->routeIs('kasir.dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('owner.dashboard')) ? 'text-emerald-600 dark:text-violet-400 font-extrabold' : 'text-slate-500 dark:text-purple-400' }} hover:text-emerald-800 dark:hover:text-violet-300 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Dashboard</span>
            </a>

            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('admin.transactions.*') ? 'text-emerald-600 dark:text-violet-400 font-extrabold' : 'text-slate-500 dark:text-purple-400' }} hover:text-emerald-700 dark:hover:text-violet-300 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Riwayat</span>
            </a>
            @elseif(Auth::user()->isOwner())
            <a href="{{ route('owner.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('owner.transactions.*') ? 'text-emerald-600 dark:text-violet-400 font-extrabold' : 'text-slate-500 dark:text-purple-400' }} hover:text-emerald-700 dark:hover:text-violet-300 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Riwayat</span>
            </a>
            @elseif(Auth::user()->isKasir())
            <a href="{{ route('kasir.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('kasir.transactions.*') ? 'text-emerald-600 dark:text-violet-400 font-extrabold' : 'text-slate-500 dark:text-purple-400' }} hover:text-emerald-700 dark:hover:text-violet-300 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Riwayat</span>
            </a>
            @endif

            <!-- Profile -->
            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('profile.edit') ? 'text-emerald-600 dark:text-violet-400 font-extrabold' : 'text-slate-500 dark:text-purple-400' }} hover:text-emerald-700 dark:hover:text-violet-300 transition duration-150 py-1 px-3 rounded-xl">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span class="text-[10px] font-bold tracking-wide">Profile</span>
            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="flex flex-col items-center">
                @csrf
                <button type="submit" class="flex flex-col items-center gap-0.5 text-rose-500 dark:text-rose-400 hover:text-rose-600 dark:hover:text-rose-300 transition duration-150 py-1 px-3 rounded-xl">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span class="text-[10px] font-bold tracking-wide">Keluar</span>
                </button>
            </form>
        </nav>

        <!-- GLOBAL TOAST NOTIFICATION SYSTEM -->
        <div id="global-toast">
             <div id="toast-indicator" class="absolute left-0 top-0 bottom-0 w-1.5"></div>
             <div id="toast-icon-bg" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                 <span id="toast-icon-slot"></span>
             </div>
             <div class="flex-grow">
                 <p id="toast-message" style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 0.875rem; font-weight: 700; line-height: 1.25rem;"></p>
             </div>
             <button type="button" onclick="hideGlobalToast()" style="background: none; border: none; padding: 0; cursor: pointer; transition: color 0.2s;" class="shrink-0 text-slate-400 hover:text-slate-600 dark:text-purple-400 dark:hover:text-purple-200">
                 <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
        </div>

        <script>
            /* ================================================
               DARK MODE TOGGLE - PERSISTENT
               ================================================ */
            function toggleDarkMode() {
                const html = document.documentElement;
                const isDark = html.classList.toggle('dark');
                localStorage.setItem('pk-theme', isDark ? 'dark' : 'light');

                const toastMsg = document.getElementById('toast-message');
                if (toastMsg) {
                    toastMsg.style.color = isDark ? '#ede9fe' : '#1e293b';
                }
            }

            // Sync toast message color on load
            (function() {
                const toastMsg = document.getElementById('toast-message');
                if (toastMsg) {
                    const isDark = document.documentElement.classList.contains('dark');
                    toastMsg.style.color = isDark ? '#ede9fe' : '#1e293b';
                }
            })();

            /* ================================================
               GLOBAL TOAST NOTIFICATION SYSTEM
               ================================================ */
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

            const toastColorsDark = {
                success: { indicator: '#10b981', bg: '#052e1c', text: '#34d399' },
                warning: { indicator: '#f59e0b', bg: '#2d1a02', text: '#fbbf24' },
                info: { indicator: '#818cf8', bg: '#1e1a4e', text: '#a5b4fc' },
                error: { indicator: '#f43f5e', bg: '#2d0a17', text: '#fb7185' }
            };

            function showGlobalToast(message, type = 'success') {
                const toast = document.getElementById('global-toast');
                const indicator = document.getElementById('toast-indicator');
                const iconBg = document.getElementById('toast-icon-bg');
                const iconSlot = document.getElementById('toast-icon-slot');
                const msgEl = document.getElementById('toast-message');

                if (!toast) return;

                if (globalToastTimer) clearTimeout(globalToastTimer);

                const isDark = document.documentElement.classList.contains('dark');
                const colors = isDark ? toastColorsDark[type] : toastColors[type];

                msgEl.innerText = message;
                msgEl.style.color = isDark ? '#ede9fe' : '#1e293b';
                iconSlot.innerHTML = toastIcons[type] || toastIcons.success;

                indicator.style.backgroundColor = colors.indicator;
                iconBg.style.backgroundColor = colors.bg;
                iconBg.style.color = colors.text;

                toast.classList.add('show');

                globalToastTimer = setTimeout(() => {
                    hideGlobalToast();
                }, 2800);
            }

            function hideGlobalToast() {
                const toast = document.getElementById('global-toast');
                if (toast) toast.classList.remove('show');
                globalToastTimer = null;
            }

            window.addEventListener('toast', (e) => {
                showGlobalToast(e.detail.message, e.detail.type);
            });

            window.showToast = showGlobalToast;
            window.hideToast = hideGlobalToast;
        </script>

    </body>
</html>
