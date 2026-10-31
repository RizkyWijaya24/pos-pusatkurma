<!-- Dashboard Link -->
<a href="{{ route('dashboard') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
    </svg>
    <span>General Dashboard</span>
</a>

<!-- Role-Specific Kasir Dashboard (Admin & Kasir) -->
@if(Auth::user()->isKasir() || Auth::user()->isAdmin())
<a href="{{ route('kasir.dashboard') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('kasir.dashboard') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H3m0 0h-.375c-.621 0-1.125.504-1.125 1.125V18m0 0H3.375c.621 0 1.125-.504 1.125-1.125V18M3 18.75h-.375A1.125 1.125 0 011.5 17.625V6M2.25 18.75h2.25m-2.25 0v-4.5m18 4.5v-4.5m-18 4.5h18" />
    </svg>
    <span>Dashboard Kasir</span>
</a>
@endif

<!-- Role-Specific Admin Dashboard (Admin only) -->
@if(Auth::user()->isAdmin())
<a href="{{ route('admin.dashboard') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774a1.125 1.125 0 01.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.398 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738a1.125 1.125 0 01-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527a1.125 1.125 0 01-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
    <span>Dashboard Admin</span>
</a>
@endif

<!-- Role-Specific Owner Dashboard (Admin & Owner) -->
@if(Auth::user()->isOwner() || Auth::user()->isAdmin())
<a href="{{ route('owner.dashboard') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('owner.dashboard') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 10.5h6" />
    </svg>
    <span>Dashboard Owner</span>
</a>
@endif

<!-- Divider -->
<div class="my-2 border-t border-emerald-800/30"></div>

<!-- Riwayat Transaksi — role-aware link -->
@if(Auth::user()->isAdmin())
<a href="{{ route('admin.transactions.index') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.transactions.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
    </svg>
    <span>Riwayat Transaksi</span>
</a>
@elseif(Auth::user()->isOwner())
<a href="{{ route('owner.transactions.index') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('owner.transactions.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
    </svg>
    <span>Riwayat Transaksi</span>
</a>
@elseif(Auth::user()->isKasir())
<a href="{{ route('kasir.transactions.index') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('kasir.transactions.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
    </svg>
    <span>Riwayat Transaksi</span>
</a>
@endif


<!-- Stock, Purchases & Receivables Management (Role-aware) -->
@if(Auth::user()->isAdmin() || Auth::user()->isOwner())
@php
    $unpaidPurchasesCount = \App\Models\Purchase::whereIn('payment_status', ['unpaid', 'partial'])->whereRaw('total_amount > paid_amount')->count();
    $activeReceivablesBadge = \App\Models\Transaction::where('transaction_type', 'wholesale')
        ->where('payment_method', 'Piutang / Tempo')
        ->where('payment_status', 'unpaid')
        ->count();
    $totalPendingFinance = $unpaidPurchasesCount + $activeReceivablesBadge;
@endphp
<a href="{{ route('admin.purchases.index') }}" 
   class="flex items-center justify-between px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.purchases.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <div class="flex items-center gap-3.5">
        <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.948c0-.621-.504-1.125-1.125-1.125H4.125C3.504 5.5 3 6.004 3 6.625v7.5c0 .621.504 1.125 1.125 1.125h1.5" />
        </svg>
        <span>Stok, Pembelian & Piutang</span>
    </div>
    @if($totalPendingFinance > 0)
        <span class="bg-amber-500 text-slate-950 font-extrabold text-[11px] px-2 py-0.5 rounded-full shadow-sm" title="{{ $unpaidPurchasesCount }} Faktur Utang & {{ $activeReceivablesBadge }} Piutang Aktif">
            {{ $totalPendingFinance }}
        </span>
    @endif
</a>
@endif

@if(Auth::user()->isKasir())
<a href="{{ route('kasir.stock-request') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('kasir.stock-request') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
    </svg>
    <span>Request Stok</span>
</a>
@endif

@if(Auth::user()->isAdmin())
@php
    $pendingTransfersCount = \App\Models\StockTransfer::pending()->count();
@endphp
<a href="{{ route('admin.stock-transfers.index') }}" 
   class="flex items-center justify-between px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.stock-transfers.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <div class="flex items-center gap-3.5">
        <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        <span>Transfer Stok</span>
    </div>
    @if($pendingTransfersCount > 0)
        <span class="bg-yellow-500 text-slate-950 font-extrabold text-[11px] px-2 py-0.5 rounded-full shadow-sm animate-pulse">
            {{ $pendingTransfersCount }}
        </span>
    @endif
</a>
<a href="{{ route('admin.repack.index') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.repack.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
    </svg>
    <span>Repack & Pecah Stok</span>
</a>
<a href="{{ route('admin.conversions.index') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.conversions.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L17.5 12M21 7.5H7.5" />
    </svg>
    <span>Aturan Konversi Produk</span>
</a>
<a href="{{ route('admin.affiliates.index') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('admin.affiliates.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
    </svg>
    <span>Afiliasi B2B (Tour Leader)</span>
</a>
@endif

@if(Auth::user()->isOwner())
@php
    $pendingTransfersCount = \App\Models\StockTransfer::pending()->count();
@endphp
<a href="{{ route('owner.stock-transfers.index') }}" 
   class="flex items-center justify-between px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('owner.stock-transfers.*') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <div class="flex items-center gap-3.5">
        <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.732 2.076 1.714m-5.8 0a2.251 2.251 0 0 0-2.338-.043M9 6.75h-.75a2.25 2.25 0 0 0-2.25 2.25v10.5a2.25 2.25 0 0 0 2.25 2.25h3.75a2.25 2.25 0 0 0 2.25-2.25V9A2.25 2.25 0 0 0 12 6.75h-.75M9 6.75c0-.414.336-.75.75-.75h1.5a.75.75 0 0 1 .75.75M9 6.75h3" />
        </svg>
        <span>Persetujuan Stok</span>
    </div>
    @if($pendingTransfersCount > 0)
        <span class="bg-yellow-500 text-slate-950 font-extrabold text-[11px] px-2 py-0.5 rounded-full shadow-sm animate-pulse">
            {{ $pendingTransfersCount }}
        </span>
    @endif
</a>
@endif

@if(Auth::user()->isOwner() || Auth::user()->isAdmin())
<a href="{{ route('owner.stock-report') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('owner.stock-report') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 10.5h6" />
    </svg>
    <span>Laporan Stok</span>
</a>

<a href="{{ route('owner.stock-adjustment-log') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('owner.stock-adjustment-log') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 17.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
    </svg>
    <span>Log Koreksi Stok</span>
</a>
@endif

<!-- Divider -->
<div class="my-2 border-t border-emerald-800/30"></div>

<!-- Profile Link -->
<a href="{{ route('profile.edit') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
    </svg>
    <span>Profil Saya</span>
</a>
