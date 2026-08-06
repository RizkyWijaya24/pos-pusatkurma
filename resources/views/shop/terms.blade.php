@extends('layouts.shop')

@section('title', 'Syarat & Ketentuan')
@section('meta_description', 'Syarat dan Ketentuan pembelian produk kurma di Pusat Kurma Cianjur. Baca selengkapnya sebelum melakukan transaksi.')

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
    background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #fff;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(6,95,70,.2);
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
    border-left: 4px solid var(--clr-primary-light);
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
    background: rgba(6,95,70,.06);
    border: 1px solid rgba(6,95,70,.15);
    border-radius: 14px;
    padding: 20px 24px;
    margin: 24px 0;
    font-size: 14px;
    color: var(--clr-primary-dark);
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
</style>
@endpush

@section('content')
<section class="info-page">
    <div class="info-page-inner">

        <div class="info-breadcrumb">
            <a href="{{ route('shop.index') }}">🏠 Beranda</a>
            <span>/</span>
            <span>Syarat &amp; Ketentuan</span>
        </div>

        <div class="info-page-header">
            <div class="page-icon">📋</div>
            <h1>Syarat &amp; Ketentuan</h1>
            <p>Berlaku sejak 1 Januari 2024 · Terakhir diperbarui {{ date('d F Y') }}</p>
        </div>

        <div class="info-highlight">
            Dengan melakukan pembelian di website <strong>Pusat Kurma Cianjur</strong>, Anda dianggap telah membaca,
            memahami, dan menyetujui seluruh syarat dan ketentuan yang berlaku berikut ini.
        </div>

        <div class="info-section">
            <h2>1. Definisi</h2>
            <ul>
                <li><strong>Pusat Kurma Cianjur</strong> ("kami") adalah pemilik dan pengelola toko online ini.</li>
                <li><strong>Pembeli / Pelanggan</strong> ("Anda") adalah siapa pun yang menggunakan dan melakukan transaksi di website ini.</li>
                <li><strong>Produk</strong> adalah seluruh jenis kurma dan produk turunan yang dijual di website ini.</li>
                <li><strong>Pesanan</strong> adalah permintaan pembelian yang dikirimkan oleh Pelanggan melalui halaman Checkout.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>2. Cara Pemesanan</h2>
            <ul>
                <li>Pilih produk dari katalog, tentukan jumlah, lalu masukkan ke keranjang belanja.</li>
                <li>Klik <strong>"Lanjut ke Pembayaran"</strong> dan isi data diri serta alamat pengiriman yang lengkap dan benar.</li>
                <li>Pilih ekspedisi pengiriman dan layanan yang diinginkan.</li>
                <li>Selesaikan pembayaran sesuai instruksi yang diberikan.</li>
                <li>Pesanan Anda akan diproses setelah pembayaran dikonfirmasi.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>3. Harga &amp; Pembayaran</h2>
            <ul>
                <li>Harga yang tercantum di website adalah harga dalam Rupiah (IDR) dan sudah termasuk PPN bila berlaku.</li>
                <li>Harga belum termasuk biaya ongkos kirim. Biaya ongkir akan dihitung otomatis saat proses checkout.</li>
                <li>Kami berhak mengubah harga kapan saja tanpa pemberitahuan terlebih dahulu. Harga yang berlaku adalah harga saat pesanan dibuat.</li>
                <li>Pembayaran dapat dilakukan melalui metode yang tersedia pada halaman checkout (transfer bank, e-wallet, QRIS, dsb).</li>
                <li>Pesanan belum akan diproses hingga pembayaran diterima dan dikonfirmasi oleh sistem.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>4. Pengiriman</h2>
            <ul>
                <li>Kami mengirimkan produk ke seluruh wilayah Indonesia melalui ekspedisi pilihan Anda (JNE, J&T, SiCepat, Pos Indonesia, dsb).</li>
                <li>Estimasi waktu pengiriman bergantung pada jasa ekspedisi dan tujuan pengiriman.</li>
                <li>Pesanan akan dikemas dan dikirim dalam 1–2 hari kerja setelah pembayaran dikonfirmasi.</li>
                <li>Kami tidak bertanggung jawab atas keterlambatan pengiriman yang disebabkan oleh pihak ekspedisi atau kondisi luar biasa (bencana alam, hari libur nasional, dsb).</li>
                <li>Pastikan alamat pengiriman yang Anda masukkan sudah benar. Kami tidak bertanggung jawab jika pengiriman gagal akibat kesalahan alamat dari Pelanggan.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>5. Ketersediaan Stok</h2>
            <ul>
                <li>Stok produk yang ditampilkan di website adalah stok yang diperbarui secara berkala dan dapat berubah sewaktu-waktu.</li>
                <li>Apabila produk yang Anda pesan ternyata habis stok, kami akan menghubungi Anda melalui WhatsApp atau email untuk memberikan solusi (penggantian produk setara atau pembatalan pesanan dengan pengembalian dana penuh).</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>6. Pembatalan Pesanan</h2>
            <ul>
                <li>Pembatalan hanya dapat dilakukan sebelum pesanan dikemas/dikirim.</li>
                <li>Untuk membatalkan pesanan, hubungi kami segera via WhatsApp dengan menyebutkan kode pesanan Anda.</li>
                <li>Jika pembayaran sudah dilakukan, pengembalian dana akan diproses sesuai kebijakan refund kami.</li>
                <li>Kami berhak membatalkan pesanan yang diduga bersifat penipuan, manipulatif, atau melanggar ketentuan ini.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>7. Hak Kekayaan Intelektual</h2>
            <p>Seluruh konten pada website ini — termasuk gambar produk, teks, logo, dan desain — adalah milik Pusat Kurma Cianjur dan dilindungi hak cipta. Dilarang menyalin, mendistribusikan, atau menggunakan konten ini tanpa izin tertulis dari kami.</p>
        </div>

        <div class="info-section">
            <h2>8. Perubahan Syarat &amp; Ketentuan</h2>
            <p>Kami berhak mengubah syarat dan ketentuan ini kapan saja. Perubahan akan berlaku segera setelah dipublikasikan di halaman ini. Penggunaan layanan kami setelah perubahan dipublikasikan dianggap sebagai persetujuan Anda terhadap syarat yang baru.</p>
        </div>

        <div class="info-section">
            <h2>9. Hubungi Kami</h2>
            <p>Jika ada pertanyaan mengenai syarat dan ketentuan ini, silakan hubungi kami:</p>
            <ul>
                <li>📍 {{ \App\Models\Setting::get('shop_address', 'Cianjur, Jawa Barat') }}</li>
                <li>📱 WhatsApp: <a href="https://wa.me/{{ \App\Models\Setting::get('shop_whatsapp', '6281234567890') }}" target="_blank" style="color:var(--clr-primary); font-weight:600;">{{ \App\Models\Setting::get('shop_phone', '+62 812-3456-7890') }}</a></li>
                <li>⏰ {{ \App\Models\Setting::get('shop_operational_hours', 'Senin–Sabtu: 08.00–17.00 WIB') }}</li>
            </ul>
        </div>

        <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--clr-border); display:flex; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('shop.privacy') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🔒 Kebijakan Privasi →</a>
            <a href="{{ route('shop.refund') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🔄 Kebijakan Pengembalian →</a>
            <a href="{{ route('shop.index') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🛒 Kembali Belanja →</a>
        </div>

    </div>
</section>
@endsection
