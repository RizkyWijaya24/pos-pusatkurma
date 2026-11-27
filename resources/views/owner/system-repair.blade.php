@extends('layouts.app')

@section('title', 'Kesehatan & Self-Repair Sistem')

@section('content')
@php
    $initialPayload = [
        'diagnostics' => $diagnostics,
        'recentErrors' => $recentErrors,
        'repairHistory' => $repairHistory,
        'hasRescueKey' => $hasRescueKey,
        'rescueUrl' => $rescueUrl,
    ];
@endphp

<script type="application/json" id="system-repair-init">@json($initialPayload)</script>

<div x-data="systemRepairApp()" class="flex flex-col gap-6 max-w-full overflow-hidden">

    {{-- Header --}}
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-purple-100">
                    Kesehatan & Self-Repair Sistem
                </h1>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider border whitespace-nowrap"
                      :class="statusBadgeClass(diagnostics.overall_status)"
                      x-text="statusLabel(diagnostics.overall_status)">
                </span>
            </div>
            <p class="text-slate-400 dark:text-purple-400 mt-1 text-sm font-medium leading-relaxed">
                Diagnosa kesehatan aplikasi dan pulihkan cache, migrasi, sinkronisasi stok, serta status DP/piutang dengan 1 klik tanpa membuka kodingan.
                <span class="inline-block text-xs text-slate-400 whitespace-nowrap ml-1">
                    (Dicek: <span x-text="diagnostics.checked_at"></span>)
                </span>
            </p>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
            <button type="button"
                    @click="runDiagnose()"
                    :disabled="isDiagnosing || isRepairing"
                    class="px-4 py-2.5 bg-white hover:bg-slate-50 dark:bg-dp-800 dark:hover:bg-dp-700 text-slate-700 dark:text-purple-200 border border-slate-200 dark:border-dp-700 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 transition shadow-sm whitespace-nowrap disabled:opacity-50">
                <svg class="w-4 h-4 shrink-0" :class="{ 'animate-spin': isDiagnosing }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span x-text="isDiagnosing ? 'Memindai...' : 'Scan Ulang'"></span>
            </button>

            <button type="button"
                    @click="runRepair(['all'])"
                    :disabled="isRepairing || isDiagnosing"
                    class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-extrabold text-xs uppercase tracking-wider flex items-center gap-2 shadow-md shadow-emerald-700/20 transition whitespace-nowrap disabled:opacity-50 active:scale-95">
                <svg class="w-4 h-4 shrink-0" :class="{ 'animate-spin': isRepairing }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" />
                </svg>
                <span x-text="isRepairing ? 'Memperbaiki Sistem...' : 'Perbaiki Semua (1 Klik)'"></span>
            </button>
        </div>
    </div>

    {{-- Health Score Overview + Pre-Boot Rescue Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Health Score Card --}}
        <div class="lg:col-span-2 bg-white dark:bg-dp-800 rounded-3xl p-6 border border-slate-100 dark:border-dp-700 shadow-md flex flex-col justify-between gap-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-purple-400">Skor Kesehatan Sistem</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-4xl font-black tracking-tight"
                              :class="diagnostics.score >= 85 ? 'text-emerald-700 dark:text-emerald-400' : (diagnostics.score >= 60 ? 'text-amber-600' : 'text-rose-600')"
                              x-text="diagnostics.score + '%'"></span>
                        <span class="text-xs font-bold text-slate-500 dark:text-purple-300" x-text="'dari 6 subsistem utama'"></span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold text-slate-500 dark:text-purple-300 block">Status Koneksi DB</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-extrabold mt-0.5"
                          :class="diagnostics.db_connected ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600'">
                        <span class="w-2 h-2 rounded-full" :class="diagnostics.db_connected ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span x-text="diagnostics.db_connected ? 'Terhubung (Online)' : 'Terputus / Offline'"></span>
                    </span>
                </div>
            </div>

            <div class="w-full h-3 bg-slate-100 dark:bg-dp-900 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     :class="diagnostics.score >= 85 ? 'bg-emerald-600' : (diagnostics.score >= 60 ? 'bg-amber-500' : 'bg-rose-600')"
                     :style="'width: ' + diagnostics.score + '%'"></div>
            </div>

            {{-- Latest Repair Execution Summary --}}
            <template x-if="lastResults.length > 0">
                <div class="rounded-2xl bg-slate-50 dark:bg-dp-900/60 border border-slate-200 dark:border-dp-700 p-4 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-extrabold text-slate-700 dark:text-purple-100 uppercase tracking-wider">Hasil Perbaikan Terakhir</span>
                        <span class="text-slate-400 font-semibold" x-text="lastExecutedAt"></span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="res in lastResults" :key="res.key">
                            <div class="flex items-start gap-2 text-xs p-2.5 rounded-xl bg-white dark:bg-dp-800 border border-slate-100 dark:border-dp-700">
                                <span class="px-1.5 py-0.5 rounded font-black uppercase text-[10px] shrink-0"
                                      :class="res.status === 'fixed' ? 'bg-emerald-100 text-emerald-800' : (res.status === 'ok' ? 'bg-slate-100 text-slate-600' : 'bg-rose-100 text-rose-700')"
                                      x-text="res.status"></span>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 dark:text-purple-100" x-text="res.title"></div>
                                    <div class="text-slate-500 dark:text-purple-300 leading-snug mt-0.5" x-text="res.summary"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Lapisan 2: Emergency Pre-Boot Rescue Link Card --}}
        <div class="bg-amber-50/70 dark:bg-dp-800 rounded-3xl p-6 border border-amber-200 dark:border-amber-800/40 shadow-md flex flex-col justify-between gap-4">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-amber-800 dark:text-amber-300">Lapisan 2: Link Darurat (Pre-Boot)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                          :class="hasRescueKey ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                          x-text="hasRescueKey ? 'Aktif' : 'Belum Aktif'"></span>
                </div>
                <p class="text-xs text-slate-600 dark:text-purple-300 mt-2 leading-relaxed font-medium">
                    Simpan bookmark link ini. Jika suatu saat POS mengalami <strong>Error 500</strong> hingga halaman Admin tidak bisa dibuka, buka link darurat ini untuk membersihkan cache & mendiagnosa file error.
                </p>
            </div>

            <template x-if="hasRescueKey && rescueUrl">
                <div class="space-y-2">
                    <div class="flex items-center gap-1.5">
                        <input type="text"
                               readonly
                               :value="rescueUrl"
                               class="w-full text-[11px] font-mono bg-white dark:bg-dp-900 border border-amber-200 dark:border-dp-700 rounded-xl px-3 py-2 text-slate-700 dark:text-purple-100 select-all">
                        <button type="button"
                                @click="copyRescueUrl()"
                                class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shrink-0 transition">
                            Salin
                        </button>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <a :href="rescueUrl"
                           target="_blank"
                           class="text-[11px] font-extrabold text-amber-800 dark:text-amber-300 hover:underline">
                            Buka Halaman Rescue &rarr;
                        </a>
                        <button type="button"
                                @click="generateRescueKey()"
                                :disabled="isGeneratingKey"
                                class="text-[11px] font-bold text-slate-500 hover:text-slate-800 dark:text-purple-300">
                            <span x-text="isGeneratingKey ? 'Memproses...' : 'Reset Kunci Baru'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="!hasRescueKey">
                <button type="button"
                        @click="generateRescueKey()"
                        :disabled="isGeneratingKey"
                        class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-extrabold uppercase tracking-wider shadow-sm transition">
                    <span x-text="isGeneratingKey ? 'Membuat Kunci...' : 'Aktifkan Link Darurat (.env)'"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- 6 Subsystem Diagnostic Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <template x-for="check in diagnostics.checks" :key="check.key">
            <div class="bg-white dark:bg-dp-800 rounded-3xl p-5 border shadow-sm flex flex-col justify-between gap-4 transition"
                 :class="cardBorderClass(check.status)">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-sm leading-snug" x-text="check.title"></h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shrink-0 border"
                              :class="statusBadgeClass(check.status)"
                              x-text="statusLabel(check.status)"></span>
                    </div>

                    <p class="text-xs font-semibold text-slate-600 dark:text-purple-300 leading-relaxed" x-text="check.summary"></p>

                    <template x-if="check.details && check.details.length > 0">
                        <ul class="text-[11px] text-slate-500 dark:text-purple-400 space-y-1 bg-slate-50 dark:bg-dp-900/50 rounded-xl p-2.5 border border-slate-100 dark:border-dp-700 max-h-32 overflow-y-auto">
                            <template x-for="(d, idx) in check.details" :key="idx">
                                <li class="leading-snug break-words" x-text="'• ' + d"></li>
                            </template>
                        </ul>
                    </template>
                </div>

                <div class="pt-1 flex justify-end">
                    <button type="button"
                            x-show="check.repairable"
                            @click="runRepair([check.key])"
                            :disabled="isRepairing || isDiagnosing"
                            class="px-3.5 py-2 rounded-xl text-xs font-extrabold transition flex items-center gap-1.5 disabled:opacity-50"
                            :class="check.status === 'ok'
                                ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-dp-700 dark:text-purple-200'
                                : 'bg-amber-600 hover:bg-amber-700 text-white shadow-sm'">
                        <span x-text="activeModule === check.key ? 'Memproses...' : (check.status === 'ok' ? 'Jalankan Ulang' : 'Perbaiki Bagian Ini')"></span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Bottom Row: Recent Application Errors + Self-Repair History --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        {{-- Recent Errors Viewer --}}
        <div class="xl:col-span-2 bg-white dark:bg-dp-800 rounded-3xl border border-slate-100 dark:border-dp-700 shadow-md overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-dp-700 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-base">Ringkasan Log Error Terbaru</h3>
                    <p class="text-xs text-slate-400 dark:text-purple-400 font-medium">Maksimal 50 error terbaru dari storage/logs/laravel.log</p>
                </div>
                <button type="button"
                        @click="clearLogs()"
                        :disabled="isClearingLogs"
                        class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition disabled:opacity-50">
                    <span x-text="isClearingLogs ? 'Mengarsipkan...' : 'Arsipkan & Bersihkan Log'"></span>
                </button>
            </div>

            <div class="p-4 max-h-96 overflow-y-auto space-y-2.5">
                <template x-if="recentErrors.length === 0">
                    <div class="py-10 text-center text-sm font-semibold text-slate-400">
                        Tidak ada catatan error pada log saat ini.
                    </div>
                </template>

                <template x-for="(err, idx) in recentErrors" :key="idx">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-dp-900/50 border border-slate-100 dark:border-dp-700 space-y-1">
                        <div class="flex items-center justify-between gap-2 text-[11px]">
                            <span class="px-2 py-0.5 rounded font-black uppercase bg-rose-100 text-rose-800" x-text="err.level"></span>
                            <span class="font-mono text-slate-400" x-text="err.timestamp"></span>
                        </div>
                        <div class="text-xs font-semibold text-slate-700 dark:text-purple-100 break-words" x-text="err.message"></div>
                        <template x-if="err.location">
                            <div class="text-[11px] font-mono text-emerald-700 dark:text-emerald-400 break-all" x-text="err.location"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Repair Audit History --}}
        <div class="bg-white dark:bg-dp-800 rounded-3xl border border-slate-100 dark:border-dp-700 shadow-md overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-dp-700">
                <h3 class="font-extrabold text-slate-800 dark:text-purple-100 text-base">Riwayat Self-Repair</h3>
                <p class="text-xs text-slate-400 dark:text-purple-400 font-medium">Audit eksekusi perbaikan sistem</p>
            </div>

            <div class="p-4 max-h-96 overflow-y-auto space-y-2.5">
                <template x-if="repairHistory.length === 0">
                    <div class="py-10 text-center text-sm font-semibold text-slate-400">
                        Belum ada riwayat eksekusi Self-Repair.
                    </div>
                </template>

                <template x-for="(hist, idx) in repairHistory" :key="idx">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-dp-900/50 border border-slate-100 dark:border-dp-700 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
                            <span x-text="hist.timestamp"></span>
                            <span x-text="'User #' + (hist.user_id || '-')"></span>
                        </div>
                        <template x-for="r in hist.results" :key="r.key">
                            <div class="flex items-start gap-1.5 text-[11px]">
                                <span class="px-1.5 py-0.5 rounded font-bold uppercase text-[9px] shrink-0"
                                      :class="r.status === 'fixed' ? 'bg-emerald-100 text-emerald-800' : (r.status === 'ok' ? 'bg-slate-200 text-slate-600' : 'bg-rose-100 text-rose-700')"
                                      x-text="r.status"></span>
                                <span class="text-slate-600 dark:text-purple-300 leading-snug" x-text="r.summary"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function systemRepairApp() {
    const init = JSON.parse(document.getElementById('system-repair-init').textContent);

    return {
        diagnostics: init.diagnostics,
        recentErrors: init.recentErrors || [],
        repairHistory: init.repairHistory || [],
        hasRescueKey: Boolean(init.hasRescueKey),
        rescueUrl: init.rescueUrl || '',
        lastResults: [],
        lastExecutedAt: '',
        isDiagnosing: false,
        isRepairing: false,
        isClearingLogs: false,
        isGeneratingKey: false,
        activeModule: null,

        csrf() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        toast(msg, type = 'success') {
            if (window.showToast) {
                window.showToast(msg, type);
            } else {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: msg, type } }));
            }
        },

        statusLabel(status) {
            const map = {
                healthy: 'Sistem Sehat',
                ok: 'Normal',
                warning: 'Perlu Perhatian',
                critical: 'Kritis / Error',
                skipped: 'Dilewati'
            };
            return map[status] || status;
        },

        statusBadgeClass(status) {
            if (status === 'healthy' || status === 'ok') {
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            }
            if (status === 'warning') {
                return 'bg-amber-50 text-amber-800 border-amber-200';
            }
            if (status === 'skipped') {
                return 'bg-slate-100 text-slate-600 border-slate-200';
            }
            return 'bg-rose-50 text-rose-700 border-rose-200';
        },

        cardBorderClass(status) {
            if (status === 'critical') return 'border-rose-200 dark:border-rose-800/50';
            if (status === 'warning') return 'border-amber-200 dark:border-amber-800/50';
            return 'border-slate-100 dark:border-dp-700';
        },

        async runDiagnose() {
            if (this.isDiagnosing || this.isRepairing) return;
            this.isDiagnosing = true;
            try {
                const res = await fetch('{{ route("owner.system-repair.diagnose") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.diagnostics = data.diagnostics;
                    this.recentErrors = data.recent_errors || [];
                    this.repairHistory = data.repair_history || [];
                    this.toast('Scan kesehatan sistem selesai.', 'success');
                } else {
                    this.toast(data.message || 'Gagal memindai sistem.', 'error');
                }
            } catch (e) {
                this.toast('Terjadi kesalahan jaringan saat memindai.', 'error');
            } finally {
                this.isDiagnosing = false;
            }
        },

        async runRepair(modules = ['all']) {
            if (this.isRepairing || this.isDiagnosing) return;
            this.isRepairing = true;
            this.activeModule = modules.length === 1 && modules[0] !== 'all' ? modules[0] : 'all';
            try {
                const res = await fetch('{{ route("owner.system-repair.run") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                    body: JSON.stringify({ modules })
                });
                const data = await res.json();
                if (data.success) {
                    this.lastResults = data.results || [];
                    this.lastExecutedAt = data.executed_at || '';
                    this.diagnostics = data.diagnostics;
                    this.recentErrors = data.recent_errors || [];
                    this.repairHistory = data.repair_history || [];
                    const hasFail = this.lastResults.some(r => r.status === 'failed');
                    this.toast(hasFail ? 'Perbaikan selesai dengan beberapa catatan.' : 'Self-Repair berhasil dijalankan!', hasFail ? 'warning' : 'success');
                } else {
                    this.toast(data.message || 'Gagal menjalankan Self-Repair.', 'error');
                }
            } catch (e) {
                this.toast('Terjadi kesalahan jaringan saat memperbaiki sistem.', 'error');
            } finally {
                this.isRepairing = false;
                this.activeModule = null;
            }
        },

        async clearLogs() {
            if (this.isClearingLogs) return;
            this.isClearingLogs = true;
            try {
                const res = await fetch('{{ route("owner.system-repair.clear-logs") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.recentErrors = data.recent_errors || [];
                    this.diagnostics = data.diagnostics;
                    this.toast(data.message || 'Log berhasil diarsipkan.', 'success');
                } else {
                    this.toast(data.message || 'Gagal membersihkan log.', 'error');
                }
            } catch (e) {
                this.toast('Terjadi kesalahan jaringan saat mengarsipkan log.', 'error');
            } finally {
                this.isClearingLogs = false;
            }
        },

        async generateRescueKey() {
            if (this.isGeneratingKey) return;
            this.isGeneratingKey = true;
            try {
                const res = await fetch('{{ route("owner.system-repair.rescue-key") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.hasRescueKey = true;
                    this.rescueUrl = data.rescue_url;
                    this.toast(data.message, 'success');
                } else {
                    this.toast(data.message || 'Gagal membuat kunci rescue.', 'error');
                }
            } catch (e) {
                this.toast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                this.isGeneratingKey = false;
            }
        },

        copyRescueUrl() {
            if (!this.rescueUrl) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(this.rescueUrl)
                    .then(() => this.toast('Link Pre-Boot Rescue berhasil disalin!', 'success'))
                    .catch(() => this.toast('Silakan salin link dari kolom teks.', 'warning'));
            }
        }
    };
}
</script>
@endsection
