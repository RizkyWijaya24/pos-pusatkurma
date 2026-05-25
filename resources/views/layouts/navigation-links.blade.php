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

<!-- Profile Link -->
<a href="{{ route('profile.edit') }}" 
   class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'bg-emerald-800 text-white shadow-md' : 'text-emerald-100 hover:bg-emerald-900/40 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
    </svg>
    <span>Profil Saya</span>
</a>
