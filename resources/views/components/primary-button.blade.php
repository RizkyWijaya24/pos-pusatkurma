<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-emerald-700 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-emerald-800 focus:bg-emerald-800 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md shadow-emerald-700/10']) }}>
    {{ $slot }}
</button>
