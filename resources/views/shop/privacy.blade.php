@extends('layouts.shop')

@section('title', 'Kebijakan Privasi')
@section('meta_description', 'Kebijakan Privasi Pusat Kurma Cianjur — bagaimana kami mengumpulkan, menggunakan, dan melindungi data pribadi Anda.')

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
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #fff;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(29,78,216,.2);
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
    border-left: 4px solid #3b82f6;
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
    background: rgba(29,78,216,.05);
    border: 1px solid rgba(29,78,216,.15);
    border-radius: 14px;
    padding: 20px 24px;
    margin: 24px 0;
    font-size: 14px;
    color: #1e3a8a;
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

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin: 16px 0;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--clr-border);
}
.data-table th {
    background: var(--clr-surface-2);
    padding: 12px 16px;
    text-align: left;
    font-weight: 700;
    color: var(--clr-text-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.data-table td {
    padding: 12px 16px;
    border-top: 1px solid var(--clr-border);
    color: var(--clr-text);
    line-height: 1.6;
}
.data-table tr:last-child td { border-bottom: none; }
</style>
@endpush

@section('content')
<section class="info-page">
    <div class="info-page-inner">

        <div class="info-breadcrumb">
            <a href="{{ route('shop.index') }}">🏠 Beranda</a>
            <span>/</span>
            <span>Kebijakan Privasi</span>
        </div>

        <div class="info-page-header">
            <div class="page-icon">🔒</div>
            <h1>Kebijakan Privasi</h1>
            <p>Berlaku sejak 1 Januari 2024 · Terakhir diperbarui {{ date('d F Y') }}</p>
        </div>

        <div class="info-highlight">
            <strong>Privasi Anda penting bagi kami.</strong> Kebijakan ini menjelaskan bagaimana <strong>Pusat Kurma Cianjur</strong>
            mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda saat menggunakan layanan kami.
        </div>

        <div class="info-section">
            <h2>1. Informasi yang Kami Kumpulkan</h2>
            <p>Saat Anda melakukan pembelian atau menghubungi kami, kami mengumpulkan informasi berikut:</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Jenis Data</th>
                        <th>Contoh</th>
                        <th>Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Data Identitas</strong></td>
                        <td>Nama lengkap</td>
                        <td>Konfirmasi pesanan, nota pengiriman</td>
                    </tr>
                    <tr>
                        <td><strong>Data Kontak</strong></td>
                        <td>No. telepon, email</td>
                        <td>Konfirmasi pesanan, notifikasi pengiriman</td>
                    </tr>
                    <tr>
                        <td><strong>Data Pengiriman</strong></td>
                        <td>Alamat lengkap, kota, kode pos</td>
                        <td>Proses pengiriman barang</td>
                    </tr>
                    <tr>
                        <td><strong>Data Transaksi</strong></td>
                        <td>Produk dibeli, jumlah, total bayar</td>
                        <td>Administrasi dan laporan keuangan</td>
                    </tr>
                    <tr>
                        <td><strong>Data Teknis</strong></td>
                        <td>IP address, browser, waktu kunjungan</td>
                        <td>Keamanan sistem, perbaikan layanan</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="info-section">
            <h2>2. Cara Kami Menggunakan Data Anda</h2>
            <ul>
                <li>Memproses dan menyelesaikan pesanan Anda.</li>
                <li>Mengirimkan konfirmasi pesanan dan informasi pengiriman via WhatsApp atau email.</li>
                <li>Menghubungi Anda jika ada masalah dengan pesanan (stok habis, alamat tidak valid, dsb).</li>
                <li>Meningkatkan kualitas layanan dan pengalaman berbelanja di website kami.</li>
                <li>Memenuhi kewajiban hukum dan perpajakan yang berlaku.</li>
                <li><strong>Kami tidak akan menjual atau menyewakan data pribadi Anda kepada pihak ketiga.</strong></li>
            </ul>
        </div>

        <div class="info-section">
            <h2>3. Berbagi Data dengan Pihak Ketiga</h2>
            <p>Kami hanya berbagi data Anda dengan pihak ketiga yang diperlukan untuk menyelesaikan pesanan:</p>
            <ul>
                <li><strong>Penyedia Pengiriman (JNE, J&T, SiCepat, dsb)</strong> — nama dan alamat pengiriman untuk keperluan pengiriman paket.</li>
                <li><strong>DOKU (Payment Gateway)</strong> — data transaksi untuk memproses pembayaran secara aman. DOKU memiliki kebijakan privasi tersendiri yang dapat Anda akses di <a href="https://doku.com" target="_blank" style="color:var(--clr-primary);">doku.com</a>.</li>
                <li><strong>Instansi Pemerintah / Hukum</strong> — jika diwajibkan oleh peraturan perundang-undangan yang berlaku.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>4. Keamanan Data</h2>
            <ul>
                <li>Website kami menggunakan koneksi terenkripsi (HTTPS/SSL) untuk melindungi data yang dikirimkan.</li>
                <li>Data pembayaran Anda diproses langsung oleh DOKU — kami <strong>tidak</strong> menyimpan nomor kartu kredit, nomor rekening, atau PIN Anda.</li>
                <li>Akses ke data pelanggan dibatasi hanya untuk karyawan yang membutuhkan akses tersebut untuk menyelesaikan pesanan.</li>
                <li>Meskipun demikian, tidak ada sistem yang 100% aman. Kami berkomitmen untuk terus meningkatkan keamanan sistem kami.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>5. Cookie &amp; Penyimpanan Lokal</h2>
            <ul>
                <li>Website kami menggunakan <strong>Local Storage</strong> browser Anda untuk menyimpan data keranjang belanja sementara (berlaku 24 jam).</li>
                <li>Data ini tersimpan hanya di perangkat Anda dan tidak dikirimkan ke server kami hingga Anda melakukan checkout.</li>
                <li>Kami juga menggunakan cookie sesi untuk keamanan formulir (CSRF protection).</li>
                <li>Anda dapat menghapus data ini kapan saja melalui pengaturan browser Anda.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>6. Hak Anda Atas Data Pribadi</h2>
            <p>Sesuai peraturan perlindungan data yang berlaku, Anda memiliki hak untuk:</p>
            <ul>
                <li><strong>Mengetahui</strong> data pribadi apa saja yang kami miliki tentang Anda.</li>
                <li><strong>Meminta koreksi</strong> jika data Anda tidak akurat.</li>
                <li><strong>Meminta penghapusan</strong> data Anda (dengan catatan, kami mungkin perlu mempertahankan data tertentu untuk kewajiban hukum).</li>
                <li><strong>Menarik persetujuan</strong> kapan saja untuk penggunaan data yang berdasar pada persetujuan.</li>
            </ul>
            <p style="margin-top:12px;">Untuk menggunakan hak-hak tersebut, hubungi kami via WhatsApp di <a href="https://wa.me/{{ \App\Models\Setting::get('shop_whatsapp', '6281234567890') }}" target="_blank" style="color:var(--clr-primary);font-weight:600;">{{ \App\Models\Setting::get('shop_phone', '+62 812-3456-7890') }}</a>.</p>
        </div>

        <div class="info-section">
            <h2>7. Retensi Data</h2>
            <p>Kami menyimpan data transaksi selama minimal 5 (lima) tahun sesuai ketentuan perpajakan Indonesia. Data yang tidak lagi diperlukan akan dihapus secara aman.</p>
        </div>

        <div class="info-section">
            <h2>8. Perubahan Kebijakan Privasi</h2>
            <p>Kami dapat memperbarui kebijakan privasi ini sewaktu-waktu. Perubahan signifikan akan kami informasikan melalui notifikasi di website. Tanggal pembaruan terakhir selalu tertera di bagian atas halaman ini.</p>
        </div>

        <div class="info-section">
            <h2>9. Hubungi Kami</h2>
            <p>Pertanyaan terkait privasi data Anda? Hubungi kami:</p>
            <ul>
                <li>📍 {{ \App\Models\Setting::get('shop_address', 'Cianjur, Jawa Barat') }}</li>
                <li>📱 WhatsApp: <a href="https://wa.me/{{ \App\Models\Setting::get('shop_whatsapp', '6281234567890') }}" target="_blank" style="color:var(--clr-primary); font-weight:600;">{{ \App\Models\Setting::get('shop_phone', '+62 812-3456-7890') }}</a></li>
                <li>⏰ {{ \App\Models\Setting::get('shop_operational_hours', 'Senin–Sabtu: 08.00–17.00 WIB') }}</li>
            </ul>
        </div>

        <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--clr-border); display:flex; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('shop.terms') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">📋 Syarat &amp; Ketentuan →</a>
            <a href="{{ route('shop.refund') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🔄 Kebijakan Pengembalian →</a>
            <a href="{{ route('shop.index') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🛒 Kembali Belanja →</a>
        </div>

    </div>
</section>
@endsection
