@extends('layouts.shop')

@section('title', 'Kebijakan Pengembalian Dana')
@section('meta_description', 'Kebijakan pengembalian barang dan dana (refund) Pusat Kurma Cianjur. Pelajari syarat dan prosedur retur produk kurma.')

@push('styles')
<style>
.info-page { padding: 60px 24px 80px; }
.info-page-inner { max-width: 820px; margin: 0 auto; }
.info-page-header {
    text-align: center;
    margin-bottom: 48px;
    padding-bottom: 32px;
    border-bottom: 2px solid var(--clr-surface-2);
}
.info-page-header .page-icon {
    width: 72px; height: 72px;
    background: linear-gradient(135deg, #d97706, #f59e0b);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #fff;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(217,119,6,.25);
}
.info-page-header h1 {
    font-family: var(--font-heading);
    font-size: 32px;
    color: var(--clr-primary-dark);
    margin-bottom: 8px;
}
.info-page-header p { color: var(--clr-text-muted); font-size: 14px; }

.info-section { margin-bottom: 36px; }
.info-section h2 {
    font-size: 17px;
    font-weight: 700;
    color: var(--clr-primary-dark);
    margin-bottom: 12px;
    padding-left: 14px;
    border-left: 4px solid #d97706;
}
.info-section p, .info-section li {
    font-size: 14px;
    color: var(--clr-text);
    line-height: 1.8;
    margin-bottom: 8px;
}
.info-section ul { padding-left: 20px; }
.info-section ul li { list-style: disc; }

.info-highlight {
    background: rgba(217,119,6,.07);
    border: 1px solid rgba(217,119,6,.2);
    border-radius: 14px;
    padding: 20px 24px;
    margin: 24px 0;
    font-size: 14px;
    color: #92400e;
    line-height: 1.7;
}
.info-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--clr-text-muted);
    margin-bottom: 32px;
}
.info-breadcrumb a { color: var(--clr-primary); text-decoration: none; }
.info-breadcrumb a:hover { text-decoration: underline; }
.info-breadcrumb span { color: var(--clr-border); }

.refund-steps {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin: 20px 0;
    position: relative;
}
.refund-steps::before {
    content: '';
    position: absolute;
    left: 22px;
    top: 0; bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #d97706, rgba(217,119,6,.1));
}
.refund-step {
    display: flex;
    gap: 16px;
    padding-bottom: 24px;
    position: relative;
}
.refund-step:last-child { padding-bottom: 0; }
.refund-step-num {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d97706, #f59e0b);
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(217,119,6,.3);
    position: relative;
    z-index: 1;
}
.refund-step-content { padding-top: 8px; }
.refund-step-content h3 {
    font-size: 15px;
    font-weight: 700;
    color: var(--clr-primary-dark);
    margin-bottom: 4px;
}
.refund-step-content p {
    font-size: 14px;
    color: var(--clr-text-muted);
    line-height: 1.6;
    margin: 0;
}

.status-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin: 16px 0;
}
.status-item {
    padding: 18px 20px;
    border-radius: 14px;
    font-size: 14px;
}
.status-yes {
    background: rgba(16,185,129,.08);
    border: 1px solid rgba(16,185,129,.25);
}
.status-no {
    background: rgba(239,68,68,.06);
    border: 1px solid rgba(239,68,68,.2);
}
.status-item h4 {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.status-yes h4 { color: #065f46; }
.status-no h4 { color: #991b1b; }
.status-item ul { padding-left: 16px; margin: 0; }
.status-item li { font-size: 13px; line-height: 1.7; }
.status-yes li { color: #047857; }
.status-no li { color: #dc2626; }

@media (max-width: 600px) {
    .status-card { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<section class="info-page">
    <div class="info-page-inner">

        <div class="info-breadcrumb">
            <a href="{{ route('shop.index') }}">🏠 Beranda</a>
            <span>/</span>
            <span>Kebijakan Pengembalian Dana</span>
        </div>

        <div class="info-page-header">
            <div class="page-icon">🔄</div>
            <h1>Kebijakan Pengembalian Dana</h1>
            <p>Berlaku sejak 1 Januari 2024 · Terakhir diperbarui {{ date('d F Y') }}</p>
        </div>

        <div class="info-highlight">
            Kepuasan Anda adalah prioritas kami. Kami menerima pengembalian barang dan pengembalian dana (refund)
            dalam kondisi-kondisi tertentu yang diatur dalam kebijakan berikut ini.
            Harap baca dengan seksama sebelum melakukan pembelian.
        </div>

        <div class="info-section">
            <h2>1. Kondisi yang Dapat Dikembalikan vs Tidak</h2>
            <div class="status-card">
                <div class="status-item status-yes">
                    <h4>✅ Dapat Dikembalikan / Refund</h4>
                    <ul>
                        <li>Produk rusak / pesok saat diterima</li>
                        <li>Produk salah kirim (tidak sesuai pesanan)</li>
                        <li>Produk sudah kadaluarsa saat diterima</li>
                        <li>Kuantitas yang dikirim kurang dari yang dipesan</li>
                        <li>Pesanan tidak tiba lebih dari 14 hari kerja (di luar hari libur nasional)</li>
                    </ul>
                </div>
                <div class="status-item status-no">
                    <h4>❌ Tidak Dapat Dikembalikan</h4>
                    <ul>
                        <li>Produk sudah dibuka dan digunakan</li>
                        <li>Produk rusak akibat kelalaian pembeli</li>
                        <li>Ketidakcocokan selera/rasa (bukan cacat produk)</li>
                        <li>Produk dengan kemasan asli sudah dibuang</li>
                        <li>Pengembalian diajukan lebih dari 3 hari setelah produk diterima</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h2>2. Batas Waktu Pengajuan</h2>
            <ul>
                <li>Komplain dan pengajuan pengembalian harus dilakukan <strong>maksimal 3 (tiga) hari kerja</strong> setelah produk diterima.</li>
                <li>Pengajuan setelah melewati batas waktu tersebut tidak akan kami proses.</li>
                <li>Harap segera periksa kondisi produk saat paket tiba.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>3. Prosedur Pengembalian (Langkah demi Langkah)</h2>
            <div class="refund-steps">
                <div class="refund-step">
                    <div class="refund-step-num">1</div>
                    <div class="refund-step-content">
                        <h3>📸 Dokumentasikan Bukti</h3>
                        <p>Ambil foto/video kondisi produk yang bermasalah berserta kemasan dan label pengiriman. Bukti ini wajib disertakan dalam pengajuan.</p>
                    </div>
                </div>
                <div class="refund-step">
                    <div class="refund-step-num">2</div>
                    <div class="refund-step-content">
                        <h3>💬 Hubungi Kami via WhatsApp</h3>
                        <p>Kirim pesan ke <a href="https://wa.me/{{ \App\Models\Setting::get('shop_whatsapp', '6281234567890') }}" target="_blank" style="color:var(--clr-primary);font-weight:600;">{{ \App\Models\Setting::get('shop_phone', '+62 812-3456-7890') }}</a> dengan menyertakan: Kode Pesanan, alasan pengembalian, dan foto/video bukti.</p>
                    </div>
                </div>
                <div class="refund-step">
                    <div class="refund-step-num">3</div>
                    <div class="refund-step-content">
                        <h3>🔍 Proses Verifikasi</h3>
                        <p>Tim kami akan memverifikasi keluhan Anda dalam <strong>1×24 jam kerja</strong>. Kami akan menginformasikan keputusan apakah pengajuan dapat diproses.</p>
                    </div>
                </div>
                <div class="refund-step">
                    <div class="refund-step-num">4</div>
                    <div class="refund-step-content">
                        <h3>📦 Pengembalian Produk (jika diperlukan)</h3>
                        <p>Jika verifikasi disetujui, kami akan memberikan instruksi pengiriman balik. Biaya ongkir pengembalian ditanggung kami jika kesalahan dari pihak kami.</p>
                    </div>
                </div>
                <div class="refund-step">
                    <div class="refund-step-num">5</div>
                    <div class="refund-step-content">
                        <h3>💰 Pengembalian Dana / Penggantian Produk</h3>
                        <p>Setelah produk diterima dan diperiksa oleh kami, refund atau penggantian produk akan diproses dalam <strong>3–7 hari kerja</strong>.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h2>4. Metode Pengembalian Dana</h2>
            <ul>
                <li><strong>Transfer Bank</strong> — Dana dikembalikan ke rekening bank yang Anda daftarkan saat pengajuan.</li>
                <li><strong>Penggantian Produk</strong> — Kami mengirimkan produk pengganti yang sama atau setara (jika stok tersedia) tanpa biaya tambahan.</li>
                <li>Metode refund akan disepakati bersama antara Anda dan tim CS kami saat proses verifikasi.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>5. Pembatalan Pesanan Sebelum Pengiriman</h2>
            <ul>
                <li>Pesanan yang <strong>belum dikemas</strong> dapat dibatalkan dengan menghubungi kami segera via WhatsApp.</li>
                <li>Jika pembayaran sudah dilakukan, dana akan dikembalikan penuh dalam <strong>3–5 hari kerja</strong> ke metode pembayaran asal.</li>
                <li>Pesanan yang sudah dikemas atau dalam perjalanan tidak dapat dibatalkan.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>6. Ketentuan Khusus Produk Kurma</h2>
            <ul>
                <li>Kurma adalah produk pangan alami. Perbedaan warna, tekstur, atau ukuran yang wajar antar kemasan bukan merupakan cacat produk.</li>
                <li>Jika produk datang dalam kondisi rusak akibat proses pengiriman, silakan ajukan klaim ke ekspedisi dan hubungi kami secara bersamaan.</li>
                <li>Untuk produk dengan satuan gram (ditimbang), toleransi berat ±2% dianggap wajar dan tidak termasuk kekurangan yang dapat diklaim.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>7. Hubungi Kami</h2>
            <p>Butuh bantuan atau memiliki pertanyaan tentang pengembalian? Kami siap membantu!</p>
            <ul>
                <li>📍 {{ \App\Models\Setting::get('shop_address', 'Cianjur, Jawa Barat') }}</li>
                <li>📱 WhatsApp: <a href="https://wa.me/{{ \App\Models\Setting::get('shop_whatsapp', '6281234567890') }}" target="_blank" style="color:var(--clr-primary); font-weight:600;">{{ \App\Models\Setting::get('shop_phone', '+62 812-3456-7890') }}</a></li>
                <li>⏰ {{ \App\Models\Setting::get('shop_operational_hours', 'Senin–Sabtu: 08.00–17.00 WIB') }}</li>
            </ul>
            <p style="margin-top:12px; font-size:13px; color: var(--clr-text-muted);">Respon dalam 1×24 jam di hari kerja.</p>
        </div>

        <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--clr-border); display:flex; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('shop.terms') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">📋 Syarat &amp; Ketentuan →</a>
            <a href="{{ route('shop.privacy') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🔒 Kebijakan Privasi →</a>
            <a href="{{ route('shop.index') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🛒 Kembali Belanja →</a>
        </div>

    </div>
</section>
@endsection
