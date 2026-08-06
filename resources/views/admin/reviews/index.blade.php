<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-50 dark:bg-dp-800 rounded-xl">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-purple-100 leading-tight text-lg">Ulasan Produk</h2>
                    <p class="text-xs text-slate-400 font-medium">Moderasi ulasan dari pelanggan</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-600 flex items-center gap-1 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="reviewAdmin()">

        {{-- Status Tabs --}}
        <div class="flex gap-2 mb-6 flex-wrap">
            @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'all' => 'Semua'] as $s => $label)
            <a href="{{ route('admin.reviews.index', ['status' => $s]) }}"
               class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $status === $s ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-dp-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-dp-700' }}">
                {{ $label }}
                @if($s !== 'all')
                    <span class="ml-1 text-xs font-mono">({{ $counts[$s] }})</span>
                @endif
            </a>
            @endforeach
        </div>

        {{-- Reviews List --}}
        <div class="bg-white dark:bg-dp-900 rounded-2xl border border-slate-200 dark:border-dp-700 overflow-hidden shadow-sm">
            @if($reviews->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                <p class="font-medium">Tidak ada ulasan di kategori ini.</p>
            </div>
            @else
            <div class="divide-y divide-slate-100 dark:divide-dp-700">
                @foreach($reviews as $review)
                <div class="p-5 hover:bg-slate-50 dark:hover:bg-dp-800 transition-colors" id="review-{{ $review->id }}">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex-1 min-w-0">
                            {{-- Stars --}}
                            <div class="flex items-center gap-1 mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="h-4 w-4 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-400 ml-1">{{ $review->rating }}/5</span>
                            </div>

                            {{-- Info --}}
                            <div class="font-bold text-slate-800 dark:text-slate-100">{{ $review->reviewer_name }}</div>
                            <div class="text-xs text-slate-400 mb-2">
                                Order: <span class="font-mono">{{ $review->order_code }}</span>
                                &bull; Produk: <span class="font-semibold">{{ $review->product?->name ?? 'Produk dihapus' }}</span>
                                &bull; {{ $review->created_at->diffForHumans() }}
                            </div>
                            @if($review->comment)
                                <p class="text-sm text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-dp-800 rounded-xl p-3 border border-slate-100 dark:border-dp-700">
                                    "{{ $review->comment }}"
                                </p>
                            @else
                                <p class="text-xs text-slate-400 italic">Tidak ada komentar</p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if(!$review->is_approved)
                            <button
                                onclick="approveReview({{ $review->id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Setujui
                            </button>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Disetujui
                            </span>
                            @endif
                            <button
                                onclick="deleteReview({{ $review->id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold bg-red-50 hover:bg-red-100 text-red-600 rounded-xl transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            {{-- Pagination --}}
            <div class="px-4 py-4 border-t border-slate-100 dark:border-dp-700">
                {{ $reviews->appends(['status' => $status])->links() }}
            </div>
            @endif
        </div>
    </div>

    <script>
    function reviewAdmin() {
        return {};
    }
    const csrf = document.querySelector('meta[name=csrf-token]').content;

    function approveReview(id) {
        fetch(`/admin/reviews/${id}/approve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                if (window.showToast) window.showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            }
        });
    }

    function deleteReview(id) {
        if (!confirm('Hapus ulasan ini? Tindakan tidak dapat dibatalkan.')) return;
        fetch(`/admin/reviews/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                if (window.showToast) window.showToast(data.message, 'success');
                document.getElementById('review-' + id)?.remove();
            }
        });
    }
    </script>
</x-app-layout>
