@extends('layouts.shop')

@section('title', 'Checkout Pembayaran')
@section('meta_description', 'Selesaikan pembelian Anda dengan aman. Pilih ekspedisi dan metode pembayaran.')

@push('styles')
<style>
    .checkout-section { padding: 48px 24px 80px; }
    .checkout-inner {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 40px;
        align-items: start;
    }
    .checkout-card {
        background: var(--clr-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--clr-border);
        box-shadow: var(--shadow-sm);
        padding: 30px;
    }
    .checkout-card h2 {
        font-family: var(--font-heading);
        font-size: 20px;
        font-weight: 700;
        color: var(--clr-primary-dark);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .checkout-card h2 i { color: var(--clr-gold); }

    /* Stepper */
    .checkout-stepper {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 28px;
    }
    .step {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--clr-text-muted);
        flex: 1;
    }
    .step.active { color: var(--clr-primary); }
    .step.done   { color: var(--clr-success); }
    .step-num {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: var(--clr-border);
        color: var(--clr-text-muted);
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: var(--transition);
    }
    .step.active .step-num { background: var(--clr-primary); color: #fff; }
    .step.done .step-num   { background: var(--clr-success); color: #fff; }
    .step-line {
        height: 2px;
        background: var(--clr-border);
        flex: 1;
        margin: 0 6px;
    }
    .step-line.done { background: var(--clr-success); }

    /* Form Styles */
    .form-group { margin-bottom: 18px; }
    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--clr-text-muted);
        margin-bottom: 6px;
    }
    .form-label span.req { color: var(--clr-danger); margin-left: 2px; }
    .form-control {
        width: 100%;
        padding: 11px 14px;
        border-radius: 10px;
        border: 1.5px solid var(--clr-border);
        font-family: inherit;
        font-size: 14px;
        outline: none;
        transition: var(--transition);
        background: var(--clr-surface-2);
        color: var(--clr-text);
    }
    .form-control:focus {
        border-color: var(--clr-primary-light);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(5,150,105,.12);
    }

    /* City Autocomplete */
    .city-autocomplete-wrap { position: relative; }
    .city-results {
        position: absolute;
        left: 0; right: 0; top: calc(100% + 4px);
        background: #fff;
        border: 1.5px solid var(--clr-primary-light);
        border-radius: 12px;
        max-height: 220px;
        overflow-y: auto;
        z-index: 100;
        box-shadow: var(--shadow-md);
    }
    .city-option {
        padding: 10px 14px;
        font-size: 13px;
        cursor: pointer;
        border-bottom: 1px solid var(--clr-surface-2);
        transition: background .15s;
    }
    .city-option:last-child { border-bottom: none; }
    .city-option:hover, .city-option.highlighted { background: rgba(5,150,105,.08); }
    .city-option .city-type { font-size: 10px; font-weight: 700; color: var(--clr-primary-light); text-transform: uppercase; }
    .city-option .city-province { font-size: 11px; color: var(--clr-text-muted); }

    /* Courier Selection */
    .courier-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 16px;
    }
    .courier-chip {
        border: 1.5px solid var(--clr-border);
        border-radius: 10px;
        padding: 8px 6px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        font-size: 11px;
        font-weight: 700;
        color: var(--clr-text-muted);
        background: var(--clr-surface-2);
        user-select: none;
    }
    .courier-chip:hover { border-color: var(--clr-primary-light); color: var(--clr-primary); }
    .courier-chip.selected {
        border-color: var(--clr-primary);
        background: rgba(5,150,105,.08);
        color: var(--clr-primary);
    }

    /* Shipping Options */
    .shipping-option {
        border: 1.5px solid var(--clr-border);
        border-radius: 12px;
        padding: 12px 16px;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        background: var(--clr-surface-2);
    }
    .shipping-option:hover { border-color: var(--clr-primary-light); }
    .shipping-option.selected {
        border-color: var(--clr-primary);
        background: rgba(5,150,105,.06);
    }
    .shipping-service-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--clr-text);
    }
    .shipping-etd {
        font-size: 11px;
        color: var(--clr-text-muted);
        margin-top: 2px;
    }
    .shipping-price {
        font-size: 15px;
        font-weight: 800;
        color: var(--clr-primary);
    }
    .shipping-loading {
        text-align: center;
        padding: 20px;
        color: var(--clr-text-muted);
        font-size: 13px;
    }
    .shipping-error {
        background: rgba(239,68,68,.08);
        border: 1px solid rgba(239,68,68,.2);
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 13px;
        color: #991b1b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Cart Summary */
    .summary-item {
        display: flex;
        gap: 12px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--clr-surface-2);
        margin-bottom: 14px;
        align-items: center;
    }
    .summary-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .summary-img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--clr-border); }
    .summary-img-placeholder {
        width: 48px; height: 48px; border-radius: 8px;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: var(--clr-primary); display: flex; align-items: center; justify-content: center;
        font-size: 16px; border: 1px solid var(--clr-border); flex-shrink: 0;
    }
    .summary-info { flex: 1; }
    .summary-name { font-size: 13px; font-weight: 700; color: var(--clr-text); line-height: 1.3; }
    .summary-qty { font-size: 11px; color: var(--clr-text-muted); margin-top: 1px; }
    .summary-price { font-size: 13px; font-weight: 700; color: var(--clr-primary); }

    /* Total */
    .total-section { background: var(--clr-surface-2); border-radius: 12px; padding: 18px; margin-top: 20px; }
    .total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 14px; }
    .total-row.grand-total {
        margin-bottom: 0; padding-top: 10px;
        border-top: 1px solid var(--clr-border);
        font-weight: 800; font-size: 18px; color: var(--clr-primary-dark);
    }
    .ongkir-row { color: var(--clr-gold-dark); }
    .ongkir-row .ongkir-val { color: var(--clr-gold); font-weight: 700; }

    /* Buttons */
    .btn-checkout-submit {
        width: 100%; padding: 15px 24px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
        color: #fff; font-size: 15px; font-weight: 700;
        border: none; display: flex; align-items: center; justify-content: center;
        gap: 8px; cursor: pointer; transition: var(--transition);
        box-shadow: 0 6px 20px rgba(6,95,70,.25); margin-top: 24px;
    }
    .btn-checkout-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(6,95,70,.35); }
    .btn-checkout-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

    /* Payment Method Cards */
    .payment-methods-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 18px;
    }
    .payment-method-card {
        border: 2px solid var(--clr-border);
        border-radius: 14px;
        padding: 14px 12px;
        cursor: pointer;
        transition: var(--transition);
        background: var(--clr-surface-2);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 7px;
        text-align: center;
        user-select: none;
    }
    .payment-method-card:hover { border-color: var(--clr-primary-light); background: rgba(5,150,105,.04); }
    .payment-method-card.selected {
        border-color: var(--clr-primary);
        background: rgba(5,150,105,.07);
        box-shadow: 0 0 0 3px rgba(5,150,105,.12);
    }
    .payment-method-card .pm-icon {
        font-size: 22px;
        line-height: 1;
    }
    .payment-method-card .pm-name {
        font-size: 12px;
        font-weight: 800;
        color: var(--clr-text);
        line-height: 1.2;
    }
    .payment-method-card .pm-fee {
        font-size: 11px;
        font-weight: 700;
        color: var(--clr-primary);
        background: rgba(5,150,105,.1);
        border-radius: 20px;
        padding: 2px 8px;
    }
    .payment-method-card .pm-note {
        font-size: 10px;
        color: var(--clr-text-muted);
        line-height: 1.3;
    }

    /* Geolocation Button */
    .btn-location {
        background: none;
        border: none;
        color: var(--clr-primary);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        transition: var(--transition);
    }
    .btn-location:hover {
        background: rgba(5,150,105,.08);
        color: var(--clr-primary-dark);
    }
    .btn-location:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .section-divider { border: none; border-top: 1px solid var(--clr-border); margin: 24px 0; }

    /* Address Help System */
    .address-help-wrap { position: relative; }
    .address-autocomplete-wrap { position: relative; }
    .address-results {
        position: absolute;
        left: 0; right: 0; top: calc(100% + 4px);
        background: #fff;
        border: 1.5px solid var(--clr-primary-light);
        border-radius: 12px;
        max-height: 220px;
        overflow-y: auto;
        z-index: 100;
        box-shadow: var(--shadow-md);
    }
    .address-option {
        padding: 10px 14px;
        font-size: 13px;
        cursor: pointer;
        border-bottom: 1px solid var(--clr-surface-2);
        transition: background .15s;
        text-align: left;
    }
    .address-option:last-child { border-bottom: none; }
    .address-option:hover, .address-option.highlighted { background: rgba(5,150,105,.08); }
    .address-option .address-title { font-weight: 700; color: var(--clr-primary-dark); font-size: 13px; }
    .address-option .address-subtitle { font-size: 11px; color: var(--clr-text-muted); margin-top: 2px; line-height: 1.4; }
    .address-guide-btn {
        background: none;
        border: none;
        color: var(--clr-primary);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        transition: var(--transition);
        text-decoration: none;
    }
    .address-guide-btn:hover { background: rgba(5,150,105,.08); }

    /* Popover panduan */
    .address-guide-popover {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1.5px solid var(--clr-primary-light);
        border-radius: 14px;
        padding: 16px;
        z-index: 200;
        box-shadow: 0 8px 30px rgba(0,0,0,.12);
        font-size: 13px;
    }
    .address-guide-popover h4 {
        font-size: 13px;
        font-weight: 800;
        color: var(--clr-primary-dark);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .address-example {
        background: var(--clr-surface-2);
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 12px;
        color: var(--clr-text);
        line-height: 1.6;
        border-left: 3px solid var(--clr-primary-light);
        margin-bottom: 10px;
    }
    .address-format-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 12px;
    }
    .address-format-list li {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        font-size: 12px;
        color: var(--clr-text-muted);
    }
    .address-format-list li i {
        color: var(--clr-primary);
        font-size: 10px;
        margin-top: 3px;
        flex-shrink: 0;
    }
    .btn-prefill-example {
        width: 100%;
        padding: 8px 12px;
        border-radius: 8px;
        background: rgba(5,150,105,.08);
        border: 1px dashed var(--clr-primary-light);
        color: var(--clr-primary);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    .btn-prefill-example:hover { background: rgba(5,150,105,.14); }

    /* Address quality hints */
    .address-hints {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .address-hint {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
        transition: var(--transition);
    }
    .address-hint.ok {
        color: var(--clr-success);
        background: rgba(16,185,129,.06);
    }
    .address-hint.warn {
        color: #b45309;
        background: rgba(217,119,6,.07);
    }
    .address-char-count {
        text-align: right;
        font-size: 11px;
        color: var(--clr-text-muted);
        margin-top: 4px;
    }
    .address-char-count.warn { color: #b45309; }

    @media (max-width: 850px) {
        .checkout-inner { grid-template-columns: 1fr; }
        .courier-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 480px) {
        .checkout-inner { grid-template-columns: 1fr; }
        .checkout-section { padding: 24px 12px 64px; }
        .checkout-card { padding: 20px 16px; }
        .courier-grid { grid-template-columns: repeat(2, 1fr); }
        .address-guide-popover { font-size: 12px; }
    }
</style>
@endpush

@section('content')
<section class="checkout-section" x-data="checkoutState">
    <div class="checkout-inner">

        <!-- Kiri: Form -->
        <div>
            <div class="checkout-card">
                <h2><i class="fa-solid fa-truck-fast"></i> Informasi Pengiriman</h2>

                {{-- Stepper --}}
                <div class="checkout-stepper">
                    <div class="step" :class="step >= 1 ? (step > 1 ? 'done' : 'active') : ''">
                        <div class="step-num" x-text="step > 1 ? '✓' : '1'"></div>
                        <span>Data Diri</span>
                    </div>
                    <div class="step-line" :class="step > 1 ? 'done' : ''"></div>
                    <div class="step" :class="step >= 2 ? (step > 2 ? 'done' : 'active') : ''">
                        <div class="step-num" x-text="step > 2 ? '✓' : '2'"></div>
                        <span>Ongkir</span>
                    </div>
                    <div class="step-line" :class="step > 2 ? 'done' : ''"></div>
                    <div class="step" :class="step >= 3 ? 'active' : ''">
                        <div class="step-num">3</div>
                        <span>Bayar</span>
                    </div>
                </div>

                <form @submit.prevent="submitOrder">

                    {{-- ── STEP 1: Data Diri ── --}}
                    <div x-show="step === 1">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                            <input type="text" class="form-control" placeholder="Nama penerima paket..."
                                   required x-model="form.name" id="checkout-name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp / HP <span class="req">*</span></label>
                            <input type="tel" class="form-control" placeholder="Contoh: 081234567890..."
                                   required x-model="form.phone" id="checkout-phone">
                        </div>
                        <!-- Hidden email field to satisfy backend validations for non-technical customer base -->
                        <input type="hidden" x-model="form.email" id="checkout-email">
                        <div class="form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <label class="form-label" style="margin-bottom:0;">Kota / Kabupaten Tujuan <span class="req">*</span></label>
                                <button type="button" @click="useCurrentLocation" class="btn-location" :disabled="locationLoading" id="btn-use-location">
                                    <span x-show="!locationLoading"><i class="fa-solid fa-location-crosshairs"></i> Gunakan Lokasi Saat Ini</span>
                                    <span x-show="locationLoading"><i class="fa-solid fa-spinner fa-spin"></i> Mencari...</span>
                                </button>
                            </div>
                            <div class="city-autocomplete-wrap">
                                <input type="text" class="form-control" id="checkout-city-input"
                                       placeholder="Ketik nama kota tujuan..."
                                       x-model="citySearch"
                                       @input.debounce.400ms="searchCities"
                                       @focus="if(citySearch.length > 1) showCityResults = true"
                                       @click.stop
                                       autocomplete="off">
                                <div class="city-results" x-show="showCityResults && cityResults.length > 0"
                                     @click.stop style="display:none;">
                                    <template x-for="city in cityResults" :key="city.city_id">
                                        <div class="city-option" @click="selectCity(city)">
                                            <div>
                                                <span x-text="city.city_name"></span>
                                                <span class="city-type" x-text="' — ' + city.type"></span>
                                            </div>
                                            <div class="city-province" x-text="city.province"></div>
                                        </div>
                                    </template>
                                </div>
                                <div class="city-results" x-show="showCityResults && cityResults.length === 0 && citySearch.length > 2 && !cityLoading"
                                     style="display:none;">
                                    <div class="city-option" style="cursor:default; color: var(--clr-text-muted);">
                                        <i class="fa-solid fa-circle-exclamation"></i> Kota tidak ditemukan
                                    </div>
                                </div>
                            </div>
                            <template x-if="selectedCity">
                                <div style="margin-top: 8px; background: rgba(5,150,105,.08); border-radius: 8px; padding: 8px 12px; font-size: 12px; color: var(--clr-primary); font-weight: 600;">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Tujuan: <span x-text="selectedCity.type + ' ' + selectedCity.city_name + ', ' + selectedCity.province"></span>
                                </div>
                            </template>
                        </div>
                        <div class="form-group address-help-wrap" x-data="addressHelper">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <label class="form-label" style="margin-bottom:0;">Alamat Lengkap <span class="req">*</span></label>
                                <button type="button"
                                        class="address-guide-btn"
                                        @click.stop="showGuide = !showGuide"
                                        id="btn-address-guide">
                                    <i class="fa-solid fa-circle-question"></i>
                                    Panduan Isi Alamat
                                </button>
                            </div>

                            {{-- Popover Panduan --}}
                            <div class="address-guide-popover" x-show="showGuide" x-cloak @click.stop style="display:none;">
                                <h4><i class="fa-solid fa-map-pin" style="color:var(--clr-gold);"></i> Format Alamat yang Benar</h4>
                                <div class="address-example">
                                    Jl. Merdeka No. 12, RT 03/RW 05,<br>
                                    Kel. Bojongherang, Kec. Cianjur,<br>
                                    Cianjur, Jawa Barat 43211
                                </div>
                                <ul class="address-format-list">
                                    <li><i class="fa-solid fa-check-circle"></i> <span>Nama jalan lengkap + nomor rumah</span></li>
                                    <li><i class="fa-solid fa-check-circle"></i> <span>RT dan RW (contoh: RT 03/RW 05)</span></li>
                                    <li><i class="fa-solid fa-check-circle"></i> <span>Kelurahan / Desa</span></li>
                                    <li><i class="fa-solid fa-check-circle"></i> <span>Kecamatan</span></li>
                                    <li><i class="fa-solid fa-check-circle"></i> <span>Kota/Kabupaten & Kode Pos (opsional)</span></li>
                                </ul>
                                <button type="button" class="btn-prefill-example" @click="prefillExample">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    Gunakan Contoh Alamat
                                </button>
                                <button type="button"
                                    @click="showGuide = false"
                                    style="width:100%;margin-top:8px;padding:6px;border:none;background:none;color:var(--clr-text-muted);font-size:12px;cursor:pointer;">
                                    <i class="fa-solid fa-xmark"></i> Tutup
                                </button>
                            </div>

                            <div class="address-autocomplete-wrap">
                                <textarea class="form-control" rows="3"
                                          placeholder="Contoh: Jl. Merdeka No. 12, RT 03/RW 05, Kel. Bojongherang, Kec. Cianjur..."
                                          required
                                          x-model="form.address"
                                          id="checkout-address"
                                          @input.debounce.400ms="searchAddresses($event.target.value)"
                                          @focus="showGuide = false"
                                          @click.stop
                                ></textarea>

                                {{-- Address Autocomplete Dropdown --}}
                                <div class="address-results" x-show="showAddressResults && addressResults.length > 0"
                                     @click.stop style="display:none;">
                                    <template x-for="addr in addressResults" :key="addr.place_id">
                                        <div class="address-option" @click="selectAddress(addr)">
                                            <div class="address-title" x-text="addr.name"></div>
                                            <div class="address-subtitle" x-text="addr.display_name"></div>
                                        </div>
                                    </template>
                                </div>
                                <div class="address-results" x-show="showAddressResults && addressResults.length === 0 && form.address.length > 5 && !addressLoading"
                                     style="display:none;">
                                    <div class="address-option" style="cursor:default; color: var(--clr-text-muted); font-size:12px;">
                                        <i class="fa-solid fa-circle-info"></i> Ketik lebih detail untuk saran jalan/desa...
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Warning untuk melengkapi alamat --}}
                            <template x-if="form.address.includes('...')">
                                <div style="margin-top: 6px; color: var(--clr-danger); font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px;" id="address-warning">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Penting: Silakan ganti titik-titik [...] dengan Kampung, RT, RW, dan No. Rumah Anda.</span>
                                </div>
                            </template>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan Pengiriman <span style="color:var(--clr-text-muted);font-weight:400;">(opsional)</span></label>
                            <textarea class="form-control" rows="2"
                                      placeholder="Catatan untuk kurir..." x-model="form.notes"></textarea>
                        </div>
                        <button type="button" class="btn-checkout-submit" @click="goToStep2"
                                :disabled="!form.name || !form.phone || !form.email || !form.address || form.address.includes('...') || !selectedCity">
                            <i class="fa-solid fa-arrow-right"></i> Pilih Ekspedisi & Ongkir
                        </button>
                    </div>

                    {{-- ── STEP 2: Ongkir ── --}}
                    <div x-show="step === 2" style="display:none;">
                        <div style="margin-bottom: 16px;">
                            <button type="button" @click="step = 1"
                                    style="background:none;border:none;color:var(--clr-primary);font-size:13px;font-weight:600;cursor:pointer;padding:0;">
                                <i class="fa-solid fa-arrow-left"></i> Ubah Data Diri
                            </button>
                        </div>

                        <div style="background:rgba(5,150,105,.06);border:1px solid rgba(5,150,105,.15);border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:13px;">
                            <div style="font-weight:700;color:var(--clr-primary-dark);margin-bottom:2px;" x-text="form.name"></div>
                            <div style="color:var(--clr-text-muted);" x-text="selectedCity ? (selectedCity.type + ' ' + selectedCity.city_name + ', ' + selectedCity.province) : ''"></div>
                        </div>

                        <div class="form-label" style="margin-bottom:10px;">Pilih Ekspedisi:</div>
                        <div class="courier-grid">
                            <template x-for="(label, code) in couriers" :key="code">
                                <div class="courier-chip"
                                     :class="selectedCourier === code ? 'selected' : ''"
                                     @click="selectCourier(code)"
                                     x-text="label">
                                </div>
                            </template>
                        </div>

                        {{-- Shipping Options --}}
                        <div x-show="shippingLoading" class="shipping-loading">
                            <i class="fa-solid fa-spinner fa-spin"></i> Menghitung ongkir...
                        </div>
                        <div x-show="shippingError && !shippingLoading" class="shipping-error">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span x-text="shippingError"></span>
                        </div>
                        <div x-show="!shippingLoading && !shippingError && shippingOptions.length > 0">
                            <div class="form-label" style="margin-bottom:8px;">Pilih Layanan:</div>
                            <template x-for="opt in shippingOptions" :key="opt.courier + opt.service">
                                <div class="shipping-option"
                                     :class="selectedShipping && selectedShipping.service === opt.service && selectedShipping.courier === opt.courier ? 'selected' : ''"
                                     @click="selectedShipping = opt">
                                    <div>
                                        <div class="shipping-service-name" x-text="opt.courier + ' ' + opt.service + ' — ' + opt.description"></div>
                                        <div class="shipping-etd" x-text="'Estimasi: ' + opt.etd"></div>
                                    </div>
                                    <div class="shipping-price" x-text="'Rp ' + formatNumber(opt.cost)"></div>
                                </div>
                            </template>
                        </div>

                        <div x-show="!selectedCourier" style="color:var(--clr-text-muted);font-size:13px;text-align:center;padding:16px 0;">
                            <i class="fa-solid fa-hand-pointer"></i> Pilih ekspedisi di atas untuk melihat pilihan layanan
                        </div>

                        <button type="button" class="btn-checkout-submit" @click="goToStep3"
                                :disabled="!selectedShipping">
                            <i class="fa-solid fa-arrow-right"></i> Lanjut ke Pembayaran
                        </button>
                    </div>

                    {{-- ── STEP 3: Pilih Metode Bayar ── --}}
                    <div x-show="step === 3" style="display:none;">
                        <div style="margin-bottom: 16px;">
                            <button type="button" @click="step = 2"
                                    style="background:none;border:none;color:var(--clr-primary);font-size:13px;font-weight:600;cursor:pointer;padding:0;">
                                <i class="fa-solid fa-arrow-left"></i> Ubah Ekspedisi
                            </button>
                        </div>

                        {{-- Ringkasan pengiriman --}}
                        <div style="background:rgba(5,150,105,.06);border:1px solid rgba(5,150,105,.15);border-radius:12px;padding:14px;margin-bottom:20px;font-size:13px;display:flex;flex-direction:column;gap:8px;">
                            <div><i class="fa-solid fa-user" style="color:var(--clr-primary);width:16px;"></i> <strong x-text="form.name"></strong> — <span x-text="form.phone"></span></div>
                            <div><i class="fa-solid fa-location-dot" style="color:var(--clr-primary);width:16px;"></i> <span x-text="form.address + ', ' + (selectedCity ? selectedCity.city_name : '')"></span></div>
                            <div x-show="selectedShipping">
                                <i class="fa-solid fa-truck-fast" style="color:var(--clr-gold);width:16px;"></i>
                                <span x-text="selectedShipping ? (selectedShipping.courier + ' ' + selectedShipping.service + ' — Estimasi ' + selectedShipping.etd) : ''"></span>
                                — <strong x-text="selectedShipping ? ('Rp ' + formatNumber(selectedShipping.cost)) : ''"></strong>
                            </div>
                        </div>

                        {{-- Pilih Metode Pembayaran --}}
                        <div class="form-label" style="margin-bottom:10px;">
                            <i class="fa-solid fa-credit-card" style="color:var(--clr-gold);"></i>
                            Pilih Metode Pembayaran
                        </div>
                        <div class="payment-methods-grid">

                            {{-- QRIS --}}
                            <div class="payment-method-card" :class="selectedPaymentChannel === 'QRIS' ? 'selected' : ''"
                                 @click="selectedPaymentChannel = 'QRIS'">
                                <div class="pm-icon">📱</div>
                                <div class="pm-name">QRIS</div>
                                <div class="pm-fee" x-text="'+ Rp ' + formatNumber(calcFee('QRIS'))"></div>
                                <div class="pm-note">GoPay, OVO, Dana, ShopeePay, BSI, dll</div>
                            </div>

                            {{-- Transfer Bank --}}
                            <div class="payment-method-card" :class="selectedPaymentChannel === 'VIRTUAL_ACCOUNT' ? 'selected' : ''"
                                 @click="selectedPaymentChannel = 'VIRTUAL_ACCOUNT'">
                                <div class="pm-icon">🏦</div>
                                <div class="pm-name">Transfer Bank</div>
                                <div class="pm-fee" x-text="'+ Rp ' + formatNumber(calcFee('VIRTUAL_ACCOUNT'))"></div>
                                <div class="pm-note">BCA, BRI, BNI, Mandiri, dll</div>
                            </div>

                            {{-- E-Wallet --}}
                            <div class="payment-method-card" :class="selectedPaymentChannel === 'EMONEY' ? 'selected' : ''"
                                 @click="selectedPaymentChannel = 'EMONEY'">
                                <div class="pm-icon">💳</div>
                                <div class="pm-name">E-Wallet / OVO</div>
                                <div class="pm-fee" x-text="'+ Rp ' + formatNumber(calcFee('EMONEY'))"></div>
                                <div class="pm-note">OVO, DANA, LinkAja</div>
                            </div>

                            {{-- Minimarket --}}
                            <div class="payment-method-card" :class="selectedPaymentChannel === 'RETAIL' ? 'selected' : ''"
                                 @click="selectedPaymentChannel = 'RETAIL'">
                                <div class="pm-icon">🏪</div>
                                <div class="pm-name">Minimarket</div>
                                <div class="pm-fee" x-text="'+ Rp ' + formatNumber(calcFee('RETAIL'))"></div>
                                <div class="pm-note">Alfamart, Indomaret</div>
                            </div>

                        </div>

                        {{-- Info biaya --}}
                        <template x-if="selectedPaymentChannel">
                            <div style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:10px;padding:10px 14px;font-size:12px;color:#1e40af;margin-bottom:16px;">
                                <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                                Biaya layanan <strong x-text="'Rp ' + formatNumber(calcFee(selectedPaymentChannel))"></strong>
                                ditambahkan sesuai metode yang Anda pilih (tarif resmi DOKU).
                            </div>
                        </template>

                        {{-- ── Kode Promo / Referral ── --}}
                        <div style="border:1px solid var(--clr-border);border-radius:12px;padding:16px;margin-bottom:16px;background:var(--clr-surface-2);">
                            <div style="font-size:13px;font-weight:700;color:var(--clr-primary-dark);margin-bottom:12px;">
                                <i class="fa-solid fa-tags" style="color:var(--clr-gold);margin-right:6px;"></i>Kode Promo & Referral
                            </div>

                            {{-- Kupon --}}
                            <div style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--clr-text-muted);display:block;margin-bottom:5px;">Kode Kupon</label>
                                <div style="display:flex;gap:8px;">
                                    <input type="text" x-model="couponInput" placeholder="Contoh: KURMA20"
                                           style="flex:1;height:40px;border:1.5px solid var(--clr-border);border-radius:8px;padding:0 12px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase;"
                                           :class="couponError ? 'border-red-400' : couponDiscount > 0 ? 'border-green-500' : ''"
                                           @focus="$el.style.borderColor='var(--clr-primary-light)'"
                                           @blur="$el.style.borderColor=''">
                                    <button type="button" @click="applyCoupon()" :disabled="!couponInput || couponLoading"
                                            style="height:40px;padding:0 14px;border-radius:8px;background:var(--clr-primary);color:#fff;font-size:12px;font-weight:700;border:none;cursor:pointer;white-space:nowrap;">
                                        <span x-show="!couponLoading">Pakai</span>
                                        <span x-show="couponLoading"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    </button>
                                </div>
                                <div x-show="couponError" x-text="couponError" style="font-size:11px;color:#dc2626;margin-top:4px;"></div>
                                <div x-show="couponDiscount > 0" style="font-size:11px;color:var(--clr-success);margin-top:4px;font-weight:700;">
                                    ✓ Hemat Rp <span x-text="formatNumber(couponDiscount)"></span>!
                                </div>
                            </div>

                            {{-- Referral --}}
                            <div>
                                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--clr-text-muted);display:block;margin-bottom:5px;">Kode Referral</label>
                                <div style="display:flex;gap:8px;">
                                    <input type="text" x-model="referralInput" placeholder="Contoh: AGEN-BUDI"
                                           style="flex:1;height:40px;border:1.5px solid var(--clr-border);border-radius:8px;padding:0 12px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase;"
                                           @focus="$el.style.borderColor='var(--clr-primary-light)'"
                                           @blur="$el.style.borderColor=''">
                                    <button type="button" @click="applyReferral()" :disabled="!referralInput || referralLoading"
                                            style="height:40px;padding:0 14px;border-radius:8px;background:var(--clr-primary);color:#fff;font-size:12px;font-weight:700;border:none;cursor:pointer;white-space:nowrap;">
                                        <span x-show="!referralLoading">Pakai</span>
                                        <span x-show="referralLoading"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    </button>
                                </div>
                                <div x-show="referralError" x-text="referralError" style="font-size:11px;color:#dc2626;margin-top:4px;"></div>
                                <div x-show="referralDiscount > 0" style="font-size:11px;color:var(--clr-success);margin-top:4px;font-weight:700;">
                                    ✓ Hemat Rp <span x-text="formatNumber(referralDiscount)"></span>!
                                </div>
                            </div>
                        </div>
                        {{-- End Kode Promo --}}

                        <button type="submit" class="btn-checkout-submit"
                                :disabled="submitting || items.length === 0 || !selectedShipping || !selectedPaymentChannel">
                            <span x-show="!submitting"><i class="fa-solid fa-shield-halved"></i> Bayar Sekarang — <span x-text="'Rp ' + formatNumber(grandTotal)"></span></span>
                            <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin"></i> Memproses...</span>
                        </button>
                        <div x-show="!selectedPaymentChannel" style="text-align:center;font-size:12px;color:var(--clr-text-muted);margin-top:8px;">Pilih metode pembayaran untuk melanjutkan</div>
                    </div>


                </form>
            </div>
        </div>


        <!-- Kanan: Ringkasan Belanja -->
        <div class="checkout-card" style="position:sticky;top:90px;">
            <h2><i class="fa-solid fa-receipt"></i> Ringkasan Belanja</h2>

            <div style="max-height: 260px; overflow-y: auto; margin-bottom: 16px; padding-right: 6px;">
                <template x-for="item in items" :key="item.id">
                    <div class="summary-item">
                        <template x-if="item.image_path">
                            <img :src="'/storage/' + item.image_path" :alt="item.name" class="summary-img"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        </template>
                        <div class="summary-img-placeholder" x-show="!item.image_path" style="display:none;">
                            <i class="fa-solid fa-seedling"></i>
                        </div>
                        <div class="summary-info">
                            <div class="summary-name" x-text="item.name"></div>
                            <div class="summary-qty" x-text="item.qty + ' ' + item.price_unit + ' × Rp ' + formatNumber(getItemPrice(item))"></div>
                        </div>
                        <div class="summary-price" x-text="'Rp ' + formatNumber(getItemPrice(item) * item.qty)"></div>
                    </div>
                </template>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span style="color:var(--clr-text-muted);">Subtotal Produk</span>
                    <span x-text="'Rp ' + formatNumber(subtotal)"></span>
                </div>
                <div class="total-row ongkir-row">
                    <span><i class="fa-solid fa-truck-fast" style="margin-right:4px;"></i> Ongkir
                        <template x-if="selectedShipping">
                            <span style="font-size:11px;font-weight:500;" x-text="'(' + selectedShipping.courier + ' ' + selectedShipping.service + ')'"></span>
                        </template>
                    </span>
                    <span class="ongkir-val" x-text="selectedShipping ? 'Rp ' + formatNumber(selectedShipping.cost) : '—'"></span>
                </div>

                {{-- Diskon kupon --}}
                <template x-if="couponDiscount > 0">
                    <div class="total-row" style="color:var(--clr-success);font-size:13px;">
                        <span><i class="fa-solid fa-tag" style="margin-right:4px;"></i> Kupon (<span x-text="appliedCoupon"></span>)</span>
                        <span x-text="'- Rp ' + formatNumber(couponDiscount)"></span>
                    </div>
                </template>
                {{-- Diskon referral --}}
                <template x-if="referralDiscount > 0">
                    <div class="total-row" style="color:var(--clr-success);font-size:13px;">
                        <span><i class="fa-solid fa-link" style="margin-right:4px;"></i> Referral (<span x-text="appliedReferral"></span>)</span>
                        <span x-text="'- Rp ' + formatNumber(referralDiscount)"></span>
                    </div>
                </template>

                {{-- Baris biaya transaksi DOKU --}}
                <template x-if="paymentFee > 0">
                    <div class="total-row" style="font-size:13px; color:var(--clr-text-muted);">
                        <span>
                            <i class="fa-solid fa-shield-halved" style="margin-right:4px; color:var(--clr-gold);"></i>
                            Biaya Transaksi
                            <span style="font-size:11px; display:block; margin-top:1px; color:var(--clr-text-muted); font-weight:400;">Biaya layanan pembayaran digital</span>
                        </span>
                        <span x-text="'Rp ' + formatNumber(paymentFee)" style="font-weight:600; color:var(--clr-text);"></span>
                    </div>
                </template>
                <div class="total-row grand-total">
                    <span>Total Pembayaran</span>
                    <span x-text="'Rp ' + formatNumber(grandTotal)"></span>
                </div>
            </div>

            <a href="{{ route('shop.index') }}" style="margin-top:14px;width:100%;display:flex;justify-content:center;align-items:center;height:40px;font-weight:600;border-radius:10px;font-size:13px;color:var(--clr-text-muted);border:1px solid var(--clr-border);border-radius:10px;text-decoration:none;gap:6px;">
                <i class="fa-solid fa-arrow-left"></i> Ubah Keranjang
            </a>
        </div>

    </div>
</section>
@endsection

@push('scripts')
<script>
// ── Konfigurasi biaya transaksi DOKU dari Settings admin ──────────────────
@php
    $feeEnabled = \App\Models\Setting::get('payment_fee_enabled', '1');
    $feeType    = \App\Models\Setting::get('payment_fee_type', 'flat');
    $feeValue   = (float) \App\Models\Setting::get('payment_fee_value', '4000');
    $freeShippingIds = json_decode($shop_settings['free_shipping_product_ids'] ?? '[]', true) ?: [];
@endphp
window._feeEnabled = {{ $feeEnabled === '1' ? 'true' : 'false' }};
window._feeType    = '{{ $feeType }}';
window._feeValue   = {{ $feeValue }};
window._freeShippingIds = {!! json_encode(array_map('intval', $freeShippingIds)) !!};
document.addEventListener('alpine:init', () => {
    Alpine.data('checkoutState', () => ({
        step: 1,
        form: { name: '', phone: '', email: 'customer@pusatkurma.com', address: '', notes: '' },
        submitting: false,
        locationLoading: false,

        // City autocomplete
        citySearch: '',
        cityResults: [],
        showCityResults: false,
        cityLoading: false,
        selectedCity: null,

        // Courier & shipping
        couriers: {!! json_encode(config('rajaongkir.couriers', [
            'jne' => 'JNE',
            'jnt' => 'J&T Express',
            'jntcargo' => 'J&T Cargo',
            'sicepat' => 'SiCepat',
            'pos' => 'Pos Indonesia',
            'tiki' => 'TIKI',
            'anteraja' => 'AnterAja',
        ])) !!},
        selectedCourier: null,
        shippingOptions: [],
        selectedShipping: null,
        shippingLoading: false,
        shippingError: null,

        // Payment method selection
        selectedPaymentChannel: null,

        init() {
            if (this.items.length === 0) {
                alert('Keranjang Anda kosong. Anda akan dialihkan ke katalog.');
                window.location.href = '{{ route("shop.index") }}';
            }
            // Close city results when clicking outside
            document.addEventListener('click', () => { this.showCityResults = false; });

            // Scroll to top when step changes (wait for DOM updates and reflow)
            this.$watch('step', () => {
                this.$nextTick(() => {
                    setTimeout(() => {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }, 50);
                });
            });
        },

        async searchCities() {
            if (this.citySearch.length < 2) {
                this.cityResults = [];
                this.showCityResults = false;
                return;
            }
            this.cityLoading = true;
            this.showCityResults = true;
            try {
                const res = await fetch(`{{ route('shop.shipping.cities') }}?search=${encodeURIComponent(this.citySearch)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.cityResults = data.cities || [];
            } catch (e) {
                this.cityResults = [];
            } finally {
                this.cityLoading = false;
            }
        },

        get isAllFreeShipping() {
            if (!this.items || this.items.length === 0) return false;
            return this.items.every(item => window._freeShippingIds && window._freeShippingIds.includes(parseInt(item.id)));
        },

        selectCity(city) {
            this.selectedCity = city;
            this.citySearch = city.city_name + ' — ' + city.province;
            this.showCityResults = false;
            
            if (this.isAllFreeShipping) {
                this.selectedCourier = 'BEBAS ONGKIR';
                this.shippingOptions = [{
                    courier: 'TOKO',
                    service: 'BEBAS ONGKIR',
                    description: 'Gratis Ongkos Kirim (Bebas Ongkir Toko)',
                    cost: 0,
                    etd: '1-3 Hari',
                }];
                this.selectedShipping = this.shippingOptions[0];
            } else {
                this.selectedCourier = null;
                this.shippingOptions = [];
                this.selectedShipping = null;
            }
        },

        async useCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Geolokasi tidak didukung oleh browser Anda.');
                return;
            }
            this.locationLoading = true;
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&accept-language=id`, {
                            headers: {
                                'Accept': 'application/json',
                                'User-Agent': 'PusatKurmaKasirApp/1.0'
                            }
                        });
                        if (!res.ok) throw new Error('Gagal menghubungi layanan geocoding.');
                        const data = await res.json();
                        
                        if (data && data.address) {
                            const addr = data.address;
                            // Cari nama kota/kabupaten terdekat
                            const rawCity = addr.city || addr.town || addr.county || addr.city_district || '';
                            const cleanCity = rawCity.replace(/Kota|Kabupaten|Regency/gi, '').trim();
                            
                            // Isi alamat lengkap (jalan, desa, dsb) dengan format Indonesia & prefix RT/RW kosong
                            const road = addr.road || '';
                            const kelurahan = addr.village || addr.suburb || addr.hamlet || addr.neighbourhood || '';
                            const kecamatan = addr.city_district || '';
                            
                            let formattedAddress = '[Kp. ... RT ... / RW ... No. ...]';
                            let addressParts = [];
                            
                            if (road) {
                                addressParts.push(road);
                            }
                            if (kelurahan) {
                                const cleanKel = kelurahan.replace(/Kelurahan|Desa/gi, '').trim();
                                if (cleanKel && !road.toLowerCase().includes(cleanKel.toLowerCase())) {
                                    addressParts.push(`Kel. ${cleanKel}`);
                                }
                            }
                            if (kecamatan) {
                                const cleanKec = kecamatan.replace(/Kecamatan/gi, '').trim();
                                if (cleanKec) {
                                    addressParts.push(`Kec. ${cleanKec}`);
                                }
                            }
                            
                            if (addressParts.length > 0) {
                                formattedAddress += ' ' + addressParts.join(', ');
                            } else {
                                formattedAddress = data.display_name || '';
                            }
                            
                            this.form.address = formattedAddress;
                            
                            if (cleanCity) {
                                this.citySearch = cleanCity;
                                this.cityLoading = true;
                                this.showCityResults = false;
                                
                                const cityRes = await fetch(`{{ route('shop.shipping.cities') }}?search=${encodeURIComponent(cleanCity)}`, {
                                    headers: { 'Accept': 'application/json' }
                                });
                                const cityData = await cityRes.json();
                                const cities = cityData.cities || [];
                                
                                if (cities.length > 0) {
                                    this.selectCity(cities[0]);
                                } else {
                                    alert(`Lokasi terdeteksi di "${cleanCity}", namun kota tidak terdaftar di ekspedisi RajaOngkir. Silakan ketik nama kota secara manual.`);
                                }
                            }
                        } else {
                            alert('Gagal mendeteksi detail alamat untuk lokasi Anda.');
                        }
                    } catch (e) {
                        alert('Gagal mendeteksi nama lokasi. Silakan pilih secara manual.');
                    } finally {
                        this.locationLoading = false;
                    }
                },
                (error) => {
                    this.locationLoading = false;
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            alert('Izin akses lokasi ditolak. Silakan aktifkan izin lokasi di pengaturan browser Anda.');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            alert('Informasi lokasi tidak tersedia.');
                            break;
                        case error.TIMEOUT:
                            alert('Waktu pengambilan lokasi habis.');
                            break;
                        default:
                            alert('Terjadi kesalahan saat mengambil lokasi.');
                    }
                },
                { timeout: 8000 }
            );
        },

        selectCourier(code) {
            this.selectedCourier = code;
            this.selectedShipping = null;
            this.fetchShippingCost();
        },

        async fetchShippingCost() {
            if (!this.selectedCity || !this.selectedCourier) return;
            this.shippingLoading = true;
            this.shippingError = null;
            this.shippingOptions = [];

            // ── Hitung total berat dari keranjang ──
            // Fungsi helper: ekstrak berat (gram) dari nama produk
            // Contoh: "Abukhas 5kg" → 5000, "Kurma Sukari 500g" → 500, "Madu 1.5kg" → 1500
            const extractWeightFromName = (name) => {
                const str = name.toLowerCase();
                // Cek pola: angka diikuti "kg"
                const kgMatch = str.match(/(\d+(?:[.,]\d+)?)\s*kg/);
                if (kgMatch) return Math.round(parseFloat(kgMatch[1].replace(',', '.')) * 1000);
                // Cek pola: angka diikuti "gr" atau "gram"
                const gMatch = str.match(/(\d+(?:[.,]\d+)?)\s*gr(?:am)?/);
                if (gMatch) return Math.round(parseFloat(gMatch[1].replace(',', '.')));
                // Cek pola: angka diikuti "g" (standalone, not followed by letter)
                const gShortMatch = str.match(/(\d+(?:[.,]\d+)?)\s*g(?!\w)/);
                if (gShortMatch) return Math.round(parseFloat(gShortMatch[1].replace(',', '.')));
                return null; // Tidak ada info berat di nama
            };

            const weightGrams = this.items.reduce((total, item) => {
                // Abaikan berat produk yang terdaftar sebagai Gratis Ongkir
                if (window._freeShippingIds && window._freeShippingIds.includes(parseInt(item.id))) {
                    return total;
                }
                const qty = parseFloat(item.qty) || 0;
                // Produk satuan gram: berat = qty itu sendiri
                if (item.price_unit === 'gram') {
                    return total + qty;
                }
                // Coba ekstrak berat dari nama produk terlebih dahulu
                const fromName = extractWeightFromName(item.name || '');
                if (fromName && fromName > 0) {
                    return total + fromName * qty;
                }
                // Fallback ke weight_grams dari database (jika > 0 dan bukan default 500)
                const dbWeight = parseFloat(item.weight_grams) || 0;
                if (dbWeight > 0) {
                    return total + dbWeight * qty;
                }
                // Last resort: asumsikan 1kg per item (lebih aman dari 500g)
                return total + 1000 * qty;
            }, 0);

            if (weightGrams === 0 && this.items.length > 0) {
                // Semua produk di keranjang gratis ongkir
                this.shippingOptions = [{
                    courier: (this.selectedCourier || 'EXPRESS').toUpperCase(),
                    service: 'FREE',
                    description: 'Gratis Ongkos Kirim (Bebas Ongkir Toko)',
                    cost: 0,
                    etd: '1-3 Hari',
                }];
                this.selectedShipping = this.shippingOptions[0];
                this.shippingLoading = false;
                return;
            }

            try {
                const res = await fetch('{{ route("shop.shipping.cost") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        destination_city_id: this.selectedCity.city_id,
                        weight_grams: Math.max(1000, Math.round(weightGrams)),
                        couriers: [this.selectedCourier],
                    }),
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    this.shippingOptions = data.results;
                    if (this.shippingOptions.length === 0) {
                        this.shippingError = 'Tidak ada layanan tersedia dari kurir ini untuk tujuan yang dipilih.';
                    }
                } else {
                    this.shippingError = data.message || 'Gagal mendapatkan ongkir.';
                }
            } catch (e) {
                this.shippingError = 'Gagal menghubungi layanan ongkir. Coba lagi atau pesan via WhatsApp.';
            } finally {
                this.shippingLoading = false;
            }
        },

        goToStep2() {
            if (!this.form.name || !this.form.phone || !this.form.email || !this.form.address || !this.selectedCity) {
                alert('Lengkapi semua data diri dan pilih kota tujuan terlebih dahulu.');
                return;
            }
            if (this.form.address.includes('...')) {
                alert('Silakan lengkapi data Kampung, RT, RW, dan Nomor Rumah Anda pada bagian [Kp. ... RT ... / RW ... No. ...] terlebih dahulu.');
                return;
            }
            if (this.isAllFreeShipping && !this.selectedShipping) {
                this.selectedCourier = 'BEBAS ONGKIR';
                this.shippingOptions = [{
                    courier: 'TOKO',
                    service: 'BEBAS ONGKIR',
                    description: 'Gratis Ongkos Kirim (Bebas Ongkir Toko)',
                    cost: 0,
                    etd: '1-3 Hari',
                }];
                this.selectedShipping = this.shippingOptions[0];
            }
            this.step = 2;
        },

        goToStep3() {
            if (!this.selectedShipping) {
                alert('Pilih layanan ekspedisi terlebih dahulu.');
                return;
            }
            this.step = 3;
        },

        get paymentFee() {
            return this.calcFee(this.selectedPaymentChannel);
        },

        // Hitung biaya transaksi berdasarkan channel pembayaran DOKU
        // Tarif resmi DOKU (belum PPN):
        //   QRIS            : 0,70% dari base
        //   Virtual Account : Rp 4.000 flat
        //   E-Wallet        : 1,5% dari base
        //   Minimarket      : Rp 5.000 flat
        calcFee(channel) {
            if (!window._feeEnabled) return 0;
            const base = this.subtotal + (this.selectedShipping ? this.selectedShipping.cost : 0);
            switch (channel) {
                case 'QRIS':            return Math.ceil(base * 0.007);      // 0,7%
                case 'VIRTUAL_ACCOUNT': return 4000;                          // Rp 4.000 flat
                case 'EMONEY':          return Math.ceil(base * 0.015);       // 1,5%
                case 'RETAIL':          return 5000;                          // Rp 5.000 flat
                default:                return window._feeValue;              // fallback setting
            }
        },

        get grandTotal() {
            return this.subtotal + (this.selectedShipping ? this.selectedShipping.cost : 0) + this.paymentFee;
        },

        async submitOrder() {
            if (!this.selectedShipping) {
                alert('Pilih layanan ekspedisi terlebih dahulu.');
                return;
            }
            this.submitting = true;

            const payload = {
                name:    this.form.name,
                phone:   this.form.phone,
                email:   this.form.email,
                address: this.form.address,
                notes:   this.form.notes,
                items:   this.items.map(item => ({ id: item.id, qty: item.qty })),
                // Shipping
                destination_city_id:   this.selectedCity.city_id,
                destination_city_name: this.selectedCity.city_name,
                shipping_courier:      this.selectedCourier,
                shipping_service:      this.selectedShipping.service,
                shipping_service_name: this.selectedShipping.description,
                shipping_cost:         this.selectedShipping.cost,
                shipping_etd:          this.selectedShipping.etd,
                // Payment channel (untuk perhitungan fee & DOKU)
                payment_channel:       this.selectedPaymentChannel,
            };

            try {
                const response = await fetch('{{ route("shop.checkout.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (!response.ok || data.status === 'error') {
                    throw new Error(data.message || 'Terjadi kesalahan.');
                }

                this.clearCart();
                window.location.href = `/shop/order/success/${data.order_id}`;

            } catch (error) {
                alert(error.message);
                this.submitting = false;
            }
        }
    }));
    // ─── Address Helper Component ───────────────────────────────────
    Alpine.data('addressHelper', () => ({
        showGuide: false,
        hints: [],
        addressResults: [],
        showAddressResults: false,
        addressLoading: false,

        init() {
            // Tutup popover & suggestion kalau klik di luar
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.showGuide = false;
                    this.showAddressResults = false;
                }
            });
        },

        async searchAddresses(query) {
            if (query.trim().length < 3) {
                this.addressResults = [];
                this.showAddressResults = false;
                return;
            }
            this.addressLoading = true;
            this.showAddressResults = true;

            // Append the selected city to the query to restrict suggestions to that city
            let finalQuery = query;
            if (this.selectedCity && this.selectedCity.city_name) {
                finalQuery += ' ' + this.selectedCity.city_name;
            }

            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(finalQuery)}&format=jsonv2&addressdetails=1&limit=5&countrycodes=id&accept-language=id`, {
                    headers: {
                        'Accept': 'application/json',
                        'User-Agent': 'PusatKurmaKasirApp/1.0'
                    }
                });
                const data = await res.json();
                this.addressResults = data || [];
            } catch (e) {
                this.addressResults = [];
            } finally {
                this.addressLoading = false;
            }
        },

        async selectAddress(addr) {
            // Isi form alamat lengkap dengan format Indonesia & prefix RT/RW kosong
            const addressDetails = addr.address || {};
            const road = addressDetails.road || '';
            const kelurahan = addressDetails.village || addressDetails.suburb || addressDetails.hamlet || addressDetails.neighbourhood || '';
            const kecamatan = addressDetails.city_district || '';
            
            let formattedAddress = '[Kp. ... RT ... / RW ... No. ...]';
            let addressParts = [];
            
            if (road) {
                addressParts.push(road);
            }
            if (kelurahan) {
                const cleanKel = kelurahan.replace(/Kelurahan|Desa/gi, '').trim();
                if (cleanKel && !road.toLowerCase().includes(cleanKel.toLowerCase())) {
                    addressParts.push(`Kel. ${cleanKel}`);
                }
            }
            if (kecamatan) {
                const cleanKec = kecamatan.replace(/Kecamatan/gi, '').trim();
                if (cleanKec) {
                    addressParts.push(`Kec. ${cleanKec}`);
                }
            }
            
            if (addressParts.length > 0) {
                formattedAddress += ' ' + addressParts.join(', ');
            } else {
                formattedAddress = addr.display_name || '';
            }
            
            this.form.address = formattedAddress;
            this.showAddressResults = false;

            // Cari kota terdekat dan pilih otomatis di RajaOngkir
            if (addressDetails) {
                const searchCityName = addressDetails.city || addressDetails.town || addressDetails.county || addressDetails.city_district || addressDetails.municipality || '';
                if (searchCityName) {
                    const cleanCityName = searchCityName.replace(/Kota|Kabupaten|Regency/gi, '').trim();
                    this.addressLoading = true;
                    try {
                        const cityRes = await fetch(`{{ route('shop.shipping.cities') }}?search=${encodeURIComponent(cleanCityName)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const cityData = await cityRes.json();
                        const cities = cityData.cities || [];
                        if (cities.length > 0) {
                            this.selectCity(cities[0]);
                        }
                    } catch (e) {
                        console.error('Auto select city error:', e);
                    } finally {
                        this.addressLoading = false;
                    }
                }
            }
        },

        prefillExample() {
            // Ambil nama kota yang sudah dipilih dari parent (checkoutState)
            const cityName = this.selectedCity
                ? (this.selectedCity.city_name || 'Cianjur')
                : 'Cianjur';

            this.form.address =
                `Jl. Merdeka No. 12, RT 03/RW 05, Kel. Bojongherang, Kec. ${cityName}, ${cityName}`;
            this.showGuide = false;
        }
    }));
});
</script>
@endpush
