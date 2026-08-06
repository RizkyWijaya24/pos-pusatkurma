@extends('layouts.shop')

@section('title', 'Tentang Kami')
@section('meta_description', 'Tentang Pusat Kurma Cianjur — distributor kurma premium terpercaya langsung dari importir dengan berbagai pilihan kurma berkualitas.')

@push('styles')
<style>
.info-page { padding: 60px 24px 80px; }
.info-page-inner { max-width: 820px; margin: 0 auto; }

.about-hero {
    background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-primary-dark) 100%);
    border-radius: 24px;
    padding: 48px 40px;
    color: #fff;
    text-align: center;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
}
.about-hero::before {
    content: '🌴';
    position: absolute;
    font-size: 120px;
    opacity: .08;
    right: -20px;
    top: -20px;
}
.about-hero::after {
    content: '🌴';
    position: absolute;
    font-size: 80px;
    opacity: .06;
    left: -10px;
    bottom: -10px;
    transform: scaleX(-1);
}
.about-hero h1 {
    font-family: var(--font-heading);
    font-size: 34px;
    margin-bottom: 12px;
}
.about-hero p {
    font-size: 15px;
    opacity: .85;
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.7;
}

.about-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 48px;
}
.stat-card {
    background: var(--clr-surface);
    border: 1px solid var(--clr-border);
    border-radius: 20px;
    padding: 28px 20px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}
.stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.stat-number {
    font-family: var(--font-heading);
    font-size: 36px;
    font-weight: 700;
    color: var(--clr-primary);
    margin-bottom: 4px;
}
.stat-label { font-size: 13px; color: var(--clr-text-muted); font-weight: 600; }

.info-section { margin-bottom: 36px; }
.info-section h2 {
    font-size: 17px;
    font-weight: 700;
    color: var(--clr-primary-dark);
    margin-bottom: 12px;
    padding-left: 14px;
    border-left: 4px solid var(--clr-gold);
}
.info-section p, .info-section li {
    font-size: 14px;
    color: var(--clr-text);
    line-height: 1.8;
    margin-bottom: 8px;
}
.info-section ul { padding-left: 20px; }
.info-section ul li { list-style: disc; }

.value-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin: 16px 0;
}
.value-card {
    background: var(--clr-surface-2);
    border-radius: 16px;
    padding: 20px;
}
.value-card .icon { font-size: 28px; margin-bottom: 10px; }
.value-card h3 { font-size: 14px; font-weight: 700; color: var(--clr-primary-dark); margin-bottom: 4px; }
.value-card p { font-size: 13px; color: var(--clr-text-muted); margin: 0; line-height: 1.5; }

.contact-card {
    background: linear-gradient(135deg, rgba(6,95,70,.06), rgba(5,150,105,.04));
    border: 1px solid rgba(6,95,70,.15);
    border-radius: 20px;
    padding: 32px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.contact-item { display: flex; gap: 14px; align-items: flex-start; }
.contact-item .icon {
    width: 40px; height: 40px;
    border-radius: 12px;
    background: var(--clr-primary);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.contact-item .text-label { font-size: 11px; color: var(--clr-text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
.contact-item .text-val { font-size: 14px; color: var(--clr-text); font-weight: 600; line-height: 1.4; }
.contact-item a { color: var(--clr-primary); text-decoration: none; }
.contact-item a:hover { text-decoration: underline; }

.info-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--clr-text-muted);
    margin-bottom: 32px;
}
.info-breadcrumb a { color: var(--clr-primary); text-decoration: none; }
.info-breadcrumb a:hover { text-decoration: underline; }
.info-breadcrumb span { color: var(--clr-border); }

@media (max-width: 640px) {
    .about-stats { grid-template-columns: 1fr 1fr; }
    .value-grid { grid-template-columns: 1fr; }
    .contact-card { grid-template-columns: 1fr; }
    .about-hero { padding: 36px 24px; }
    .about-hero h1 { font-size: 26px; }
}
</style>
@endpush

@section('content')
<section class="info-page">
    <div class="info-page-inner">

        <div class="info-breadcrumb">
            <a href="{{ route('shop.index') }}">🏠 Beranda</a>
            <span>/</span>
            <span>Tentang Kami</span>
        </div>

        {{-- Hero --}}
        <div class="about-hero">
            <h1>{{ \App\Models\Setting::get('shop_name', 'Pusat Kurma Cianjur') }}</h1>
            <p>{{ \App\Models\Setting::get('shop_description', 'Distributor kurma premium terpercaya. Langsung dari importir, kualitas terjamin, harga bersaing untuk ritel dan grosir.') }}</p>
        </div>

        {{-- Stats --}}
        <div class="about-stats">
            <div class="stat-card">
                <div class="stat-number">10+</div>
                <div class="stat-label">Tahun Pengalaman</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">50+</div>
                <div class="stat-label">Jenis Kurma</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Kurma Asli Premium</div>
            </div>
        </div>

        <div class="info-section">
            <h2>Siapa Kami?</h2>
            <p>
                <strong>{{ \App\Models\Setting::get('shop_name', 'Pusat Kurma Cianjur') }}</strong> adalah distributor dan pengecer kurma premium yang berbasis di
                Cianjur, Jawa Barat. Kami menghadirkan berbagai jenis kurma berkualitas tinggi langsung
                dari importir terpercaya, mulai dari kurma Ajwa, Medjool, Sukari, Ruthab, hingga berbagai
                olahan kurma lainnya.
            </p>
            <p>
                Didirikan lebih dari satu dekade yang lalu, kami telah melayani ribuan pelanggan dari
                seluruh Indonesia — baik pembeli ritel perorangan maupun grosir untuk reseller dan agen.
                Kepercayaan pelanggan adalah motivasi terbesar kami untuk terus menghadirkan produk terbaik.
            </p>
        </div>

        <div class="info-section">
            <h2>Nilai-Nilai Kami</h2>
            <div class="value-grid">
                <div class="value-card">
                    <div class="icon">🏅</div>
                    <h3>Kualitas Terjamin</h3>
                    <p>Setiap produk dipilih langsung dari sumber terpercaya dan melewati proses seleksi ketat.</p>
                </div>
                <div class="value-card">
                    <div class="icon">💯</div>
                    <h3>Harga Transparan</h3>
                    <p>Harga jelas, tidak ada biaya tersembunyi. Harga grosir tersedia untuk pembelian dalam jumlah besar.</p>
                </div>
                <div class="value-card">
                    <div class="icon">🚚</div>
                    <h3>Pengiriman Cepat</h3>
                    <p>Pesanan dikemas dan dikirim dalam 1–2 hari kerja ke seluruh Indonesia.</p>
                </div>
                <div class="value-card">
                    <div class="icon">💬</div>
                    <h3>Layanan Responsif</h3>
                    <p>Tim CS kami siap membantu via WhatsApp di jam operasional untuk memastikan kepuasan Anda.</p>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h2>Mengapa Belanja di Sini?</h2>
            <ul>
                <li>✅ Kurma langsung dari importir — lebih segar, lebih hemat.</li>
                <li>✅ Pilihan terlengkap: kurma basah (ruthab), kurma kering, kurma olahan, dan kurma premium gift.</li>
                <li>✅ Tersedia harga grosir untuk pembelian mulai dari 5 kg.</li>
                <li>✅ Kemasan higienis dan aman untuk perjalanan jauh.</li>
                <li>✅ Pengiriman ke seluruh Indonesia dengan ekspedisi terpercaya.</li>
                <li>✅ Pembayaran mudah dan aman melalui berbagai metode pembayaran.</li>
            </ul>
        </div>

        <div class="info-section">
            <h2>Hubungi Kami</h2>
            <div class="contact-card">
                <div class="contact-item">
                    <div class="icon">📍</div>
                    <div>
                        <div class="text-label">Alamat</div>
                        <div class="text-val">{!! nl2br(e(\App\Models\Setting::get('shop_address', 'Cianjur, Jawa Barat'))) !!}</div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="icon">📱</div>
                    <div>
                        <div class="text-label">WhatsApp</div>
                        <div class="text-val">
                            <a href="https://wa.me/{{ \App\Models\Setting::get('shop_whatsapp', '6281234567890') }}" target="_blank">
                                {{ \App\Models\Setting::get('shop_phone', '+62 812-3456-7890') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="icon">⏰</div>
                    <div>
                        <div class="text-label">Jam Operasional</div>
                        <div class="text-val">{{ \App\Models\Setting::get('shop_operational_hours', 'Senin–Sabtu: 08.00–17.00 WIB') }}</div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="icon">📱</div>
                    <div>
                        <div class="text-label">Instagram</div>
                        <div class="text-val">
                            <a href="{{ \App\Models\Setting::get('shop_social_instagram', '#') }}" target="_blank">
                                {{ \App\Models\Setting::get('shop_social_instagram', '@pusatkurma_cianjur') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--clr-border); display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <a href="{{ route('shop.index') }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,var(--clr-primary),var(--clr-primary-light));color:#fff;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;">
                🛒 Belanja Sekarang
            </a>
            <a href="{{ route('shop.terms') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">📋 Syarat &amp; Ketentuan →</a>
            <a href="{{ route('shop.privacy') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🔒 Kebijakan Privasi →</a>
            <a href="{{ route('shop.refund') }}" style="color:var(--clr-primary);font-weight:600;font-size:14px;">🔄 Kebijakan Pengembalian →</a>
        </div>

    </div>
</section>
@endsection
