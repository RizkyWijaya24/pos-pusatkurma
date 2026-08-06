@extends('layouts.shop')

@section('title', 'Hampers Builder — Buat Paket Hampers Sendiri')
@section('meta_description', 'Buat paket hampers kurma premium sesuai keinginan Anda. Pilih produk, tentukan jumlah, dan masukkan ke keranjang langsung.')

@push('styles')
<style>
    .hampers-hero {
        background: linear-gradient(135deg, var(--clr-primary-dark) 0%, #7c3aed 100%);
        padding: 52px 24px 72px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    .hampers-hero::before {
        content:''; position:absolute; inset:0;
        background: radial-gradient(ellipse 700px 400px at 50% 100%, rgba(217,119,6,.2) 0%, transparent 60%);
        pointer-events:none;
    }
    .hampers-hero-badge {
        display:inline-flex; align-items:center; gap:7px;
        background:rgba(251,191,36,.15); border:1px solid rgba(251,191,36,.35);
        color:var(--clr-gold-light); font-size:12px; font-weight:600;
        letter-spacing:.8px; text-transform:uppercase;
        padding:6px 14px; border-radius:99px; margin-bottom:20px;
    }
    .hampers-hero h1 {
        font-family: var(--font-heading); font-size: clamp(28px,4vw,48px);
        color:#fff; line-height:1.15; margin-bottom:12px; position:relative; z-index:1;
    }
    .hampers-hero p { font-size:15px; color:rgba(255,255,255,.75); max-width:480px; margin:0 auto; position:relative; z-index:1; }

    /* Layout */
    .hampers-section { padding: 40px 24px 80px; }
    .hampers-inner {
        max-width: 1280px; margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 32px;
        align-items: start;
    }

    /* Category Tabs */
    .cat-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
    .cat-tab {
        padding:7px 16px; border-radius:99px; font-size:13px; font-weight:600;
        border:1.5px solid var(--clr-border); color:var(--clr-text-muted);
        background:#fff; cursor:pointer; transition:var(--transition);
    }
    .cat-tab:hover { border-color:var(--clr-primary-light); color:var(--clr-primary); }
    .cat-tab.active { background:var(--clr-primary); color:#fff; border-color:var(--clr-primary); }

    /* Product Grid */
    .hampers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }
    .hampers-product-card {
        background:#fff;
        border-radius:var(--radius-md);
        border:2px solid var(--clr-border);
        overflow:hidden;
        cursor:pointer;
        transition:var(--transition);
        position:relative;
    }
    .hampers-product-card:hover { border-color:var(--clr-primary-light); transform:translateY(-2px); box-shadow:var(--shadow-md); }
    .hampers-product-card.selected { border-color:var(--clr-primary); box-shadow:0 0 0 3px rgba(5,150,105,.15); }

    .hampers-card-check {
        position:absolute; top:10px; right:10px; z-index:2;
        width:28px; height:28px; border-radius:50%;
        background:#fff; border:2px solid var(--clr-border);
        display:flex; align-items:center; justify-content:center;
        font-size:12px; color:transparent;
        transition:var(--transition);
    }
    .hampers-product-card.selected .hampers-card-check {
        background:var(--clr-primary); border-color:var(--clr-primary); color:#fff;
    }

    .hampers-card-img {
        width:100%; aspect-ratio:1/1; object-fit:cover;
        background:linear-gradient(135deg,#d1fae5,#a7f3d0);
    }
    .hampers-card-placeholder {
        width:100%; aspect-ratio:1/1;
        background:linear-gradient(135deg,#d1fae5,#a7f3d0);
        display:flex; align-items:center; justify-content:center;
        font-size:40px; color:var(--clr-primary); opacity:.5;
    }
    .hampers-card-body { padding:14px; }
    .hampers-card-name { font-size:14px; font-weight:700; color:var(--clr-text); margin-bottom:4px; line-height:1.3; }
    .hampers-card-price { font-size:13px; color:var(--clr-primary); font-weight:700; }
    .hampers-card-stock { font-size:11px; color:var(--clr-text-muted); margin-top:2px; }
    .hampers-card-out   { opacity:.5; pointer-events:none; }

    /* Qty control inside card */
    .hampers-qty-control {
        display:none;
        align-items:center;
        justify-content:space-between;
        margin-top:10px;
        background:var(--clr-surface-2);
        border-radius:8px;
        overflow:hidden;
        border:1px solid var(--clr-border);
    }
    .hampers-product-card.selected .hampers-qty-control { display:flex; }
    .hqty-btn {
        width:32px; height:32px; border:none; background:none;
        display:flex; align-items:center; justify-content:center;
        font-size:13px; color:var(--clr-text-muted); cursor:pointer; transition:var(--transition);
    }
    .hqty-btn:hover { background:var(--clr-border); color:var(--clr-text); }
    .hqty-val {
        flex:1; text-align:center; font-size:14px; font-weight:700;
        border:none; background:none; outline:none;
    }

    /* Summary Panel */
    .hampers-summary {
        background:#fff;
        border-radius:var(--radius-lg);
        border:1px solid var(--clr-border);
        box-shadow:var(--shadow-md);
        position:sticky;
        top:80px;
        overflow:hidden;
    }
    .hampers-summary-header {
        background:linear-gradient(135deg, var(--clr-primary-dark), var(--clr-primary));
        padding:20px 22px;
        color:#fff;
    }
    .hampers-summary-header h3 { font-family:var(--font-heading); font-size:19px; margin-bottom:4px; }
    .hampers-summary-header p  { font-size:13px; color:rgba(255,255,255,.75); }

    .hampers-summary-body { padding:20px 22px; }
    .hampers-empty-state {
        text-align:center; padding:32px 0; color:var(--clr-text-muted);
    }
    .hampers-empty-state i { font-size:48px; color:var(--clr-border); display:block; margin-bottom:12px; }

    .hampers-item-list { display:flex; flex-direction:column; gap:10px; max-height:360px; overflow-y:auto; }
    .hampers-item {
        display:flex; gap:10px; align-items:center;
        padding:8px; border-radius:8px; background:var(--clr-surface-2);
    }
    .hampers-item-img {
        width:40px; height:40px; border-radius:8px;
        object-fit:cover; background:linear-gradient(135deg,#d1fae5,#a7f3d0);
        flex-shrink:0;
    }
    .hampers-item-info { flex:1; min-width:0; }
    .hampers-item-name  { font-size:12px; font-weight:700; color:var(--clr-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .hampers-item-price { font-size:11px; color:var(--clr-primary); font-weight:600; }
    .hampers-item-remove {
        background:none; border:none; color:var(--clr-text-muted); font-size:13px;
        cursor:pointer; transition:var(--transition); padding:4px; flex-shrink:0;
    }
    .hampers-item-remove:hover { color:var(--clr-danger); }

    .hampers-total-section {
        border-top:1px solid var(--clr-border);
        padding-top:14px;
        margin-top:16px;
    }
    .hampers-total-row { display:flex; justify-content:space-between; font-size:13px; padding:3px 0; color:var(--clr-text-muted); }
    .hampers-total-row.grand { font-size:17px; font-weight:800; color:var(--clr-primary-dark); border-top:1px solid var(--clr-border); padding-top:12px; margin-top:8px; }

    .hampers-add-btn {
        width:100%; padding:13px; border-radius:var(--radius-md);
        background:linear-gradient(135deg, var(--clr-primary), var(--clr-primary-light));
        color:#fff; font-size:15px; font-weight:700; border:none;
        display:flex; align-items:center; justify-content:center; gap:8px;
        margin-top:16px; transition:var(--transition);
        box-shadow:0 4px 14px rgba(6,95,70,.3);
    }
    .hampers-add-btn:hover { transform:translateY(-1px); box-shadow:0 8px 24px rgba(6,95,70,.4); }
    .hampers-add-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }

    @media (max-width: 900px) {
        .hampers-inner { grid-template-columns: 1fr; }
        .hampers-summary { position:static; }
    }
</style>
@endpush

@section('content')
<div class="hampers-hero">
    <div class="hampers-hero-badge"><i class="fas fa-gift"></i> Hampers Builder</div>
    <h1>Buat Hampers Impian Anda</h1>
    <p>Pilih produk kurma favorit, tentukan jumlah, dan jadikan satu paket hampers spesial</p>
</div>

<section class="hampers-section">
    <div class="hampers-inner">
        {{-- Left: Product Selector --}}
        <div>
            {{-- Category Filter --}}
            <div class="cat-tabs">
                <button class="cat-tab active" data-cat="all">Semua</button>
                @foreach($categories as $cat)
                    <button class="cat-tab" data-cat="{{ $cat }}">{{ $cat }}</button>
                @endforeach
            </div>

            <div class="hampers-grid" id="hampersGrid">
                @foreach($products as $product)
                @php $outOfStock = $product->display_stock <= 0; @endphp
                <div
                    class="hampers-product-card {{ $outOfStock ? 'hampers-card-out' : '' }}"
                    data-id="{{ $product->id }}"
                    data-name="{{ $product->name }}"
                    data-price="{{ $product->selling_price }}"
                    data-unit="{{ $product->price_unit }}"
                    data-stock="{{ $product->display_stock }}"
                    data-cat="{{ $product->category }}"
                    data-img="{{ $product->image_path ? Storage::url($product->image_path) : '' }}"
                    onclick="toggleHampersItem(this)"
                >
                    <div class="hampers-card-check"><i class="fas fa-check"></i></div>

                    @if($product->image_path)
                        <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="hampers-card-img" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="hampers-card-placeholder" style="display:none;"><i class="fas fa-seedling"></i></div>
                    @else
                        <div class="hampers-card-placeholder"><i class="fas fa-seedling"></i></div>
                    @endif

                    <div class="hampers-card-body">
                        <div class="hampers-card-name">{{ $product->name }}</div>
                        <div class="hampers-card-price">Rp {{ number_format($product->selling_price, 0, ',', '.') }} / {{ $product->price_unit }}</div>
                        <div class="hampers-card-stock">
                            @if($outOfStock)
                                <span style="color:var(--clr-danger);">Stok habis</span>
                            @else
                                Stok: {{ number_format($product->display_stock, 0) }} {{ $product->price_unit }}
                            @endif
                        </div>

                        <div class="hampers-qty-control" onclick="event.stopPropagation()">
                            <button class="hqty-btn" onclick="changeHampersQty(this, -1)"><i class="fas fa-minus"></i></button>
                            <input type="number" class="hqty-val" value="1" min="1" max="{{ $product->display_stock }}" onchange="updateHampersSummary()">
                            <button class="hqty-btn" onclick="changeHampersQty(this, 1)"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Right: Summary Panel --}}
        <div class="hampers-summary">
            <div class="hampers-summary-header">
                <h3><i class="fas fa-gift" style="color:var(--clr-gold-light);margin-right:8px;"></i>Paket Hampers Anda</h3>
                <p id="selectedCount">0 produk dipilih</p>
            </div>
            <div class="hampers-summary-body">
                <div class="hampers-empty-state" id="hampersEmptyState">
                    <i class="fas fa-box-open"></i>
                    <p>Pilih produk dari kiri untuk memulai membuat hampers</p>
                </div>
                <div class="hampers-item-list" id="hampersItemList" style="display:none;"></div>

                <div class="hampers-total-section" id="hampersTotalSection" style="display:none;">
                    <div class="hampers-total-row grand">
                        <span>Total Estimasi</span>
                        <span id="hampersGrandTotal">Rp 0</span>
                    </div>
                </div>

                <button class="hampers-add-btn" id="hampersAddBtn" onclick="addHampersToCart()" disabled>
                    <i class="fas fa-shopping-cart"></i>
                    Masukkan ke Keranjang
                </button>
                <p style="text-align:center;font-size:12px;color:var(--clr-text-muted);margin-top:10px;">
                    Harga akhir dihitung saat checkout sesuai jumlah pembelian
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    let hampersItems = {}; // { id: { name, price, unit, qty, img } }

    function toggleHampersItem(card) {
        const id = card.dataset.id;
        const isSelected = card.classList.toggle('selected');

        if (isSelected) {
            hampersItems[id] = {
                id:    id,
                name:  card.dataset.name,
                price: parseInt(card.dataset.price),
                unit:  card.dataset.unit,
                stock: parseFloat(card.dataset.stock),
                qty:   1,
                img:   card.dataset.img,
            };
        } else {
            delete hampersItems[id];
        }
        updateHampersSummary();
    }

    function changeHampersQty(btn, delta) {
        const card  = btn.closest('.hampers-product-card');
        const input = card.querySelector('.hqty-val');
        const id    = card.dataset.id;
        let val = parseFloat(input.value) + delta;
        val = Math.max(1, Math.min(parseFloat(card.dataset.stock), val));
        input.value = val;
        if (hampersItems[id]) {
            hampersItems[id].qty = val;
        }
        updateHampersSummary();
    }

    function updateHampersSummary() {
        // Sync qty from inputs
        document.querySelectorAll('.hampers-product-card.selected').forEach(card => {
            const id  = card.dataset.id;
            const qty = parseFloat(card.querySelector('.hqty-val').value) || 1;
            if (hampersItems[id]) hampersItems[id].qty = qty;
        });

        const items = Object.values(hampersItems);
        const count = items.length;

        document.getElementById('selectedCount').textContent = count + ' produk dipilih';

        const emptyState  = document.getElementById('hampersEmptyState');
        const itemList    = document.getElementById('hampersItemList');
        const totalSec    = document.getElementById('hampersTotalSection');
        const addBtn      = document.getElementById('hampersAddBtn');

        if (count === 0) {
            emptyState.style.display = 'block';
            itemList.style.display = 'none';
            totalSec.style.display = 'none';
            addBtn.disabled = true;
            return;
        }

        emptyState.style.display = 'none';
        itemList.style.display = 'flex';
        totalSec.style.display = 'block';
        addBtn.disabled = false;

        let html = '';
        let total = 0;
        items.forEach(item => {
            const lineTotal = item.price * item.qty;
            total += lineTotal;
            const imgHtml = item.img
                ? `<img src="${item.img}" class="hampers-item-img" onerror="this.style.background='#d1fae5'">`
                : `<div class="hampers-item-img" style="display:flex;align-items:center;justify-content:center;font-size:18px;color:#065f46;background:linear-gradient(135deg,#d1fae5,#a7f3d0)"><i class="fas fa-seedling"></i></div>`;
            html += `
                <div class="hampers-item">
                    ${imgHtml}
                    <div class="hampers-item-info">
                        <div class="hampers-item-name">${item.name}</div>
                        <div class="hampers-item-price">${item.qty} ${item.unit} × Rp ${item.price.toLocaleString('id-ID')} = Rp ${lineTotal.toLocaleString('id-ID')}</div>
                    </div>
                    <button class="hampers-item-remove" onclick="removeHampersItem('${item.id}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
        });
        itemList.innerHTML = html;
        document.getElementById('hampersGrandTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    function removeHampersItem(id) {
        delete hampersItems[id];
        const card = document.querySelector(`.hampers-product-card[data-id="${id}"]`);
        if (card) card.classList.remove('selected');
        updateHampersSummary();
    }

    function addHampersToCart() {
        const items = Object.values(hampersItems);
        if (items.length === 0) return;

        // Add all items to the global cart (CartManager from shop layout)
        items.forEach(item => {
            if (typeof CartManager !== 'undefined') {
                CartManager.addItem({
                    id:    parseInt(item.id),
                    name:  item.name,
                    price: item.price,
                    unit:  item.unit,
                    stock: item.stock,
                    qty:   item.qty,
                    img:   item.img,
                });
            }
        });

        // Toast
        showToast('🎁 ' + items.length + ' produk berhasil ditambahkan ke keranjang!', 'success');

        // Redirect to cart / checkout after short delay
        setTimeout(() => {
            if (typeof openCart === 'function') openCart();
        }, 800);
    }

    function showToast(msg, type = 'success') {
        const t = document.createElement('div');
        t.style.cssText = `
            position:fixed;bottom:100px;left:50%;transform:translateX(-50%);
            background:${type === 'success' ? '#065f46' : '#991b1b'};
            color:#fff;padding:12px 24px;border-radius:99px;
            font-size:14px;font-weight:700;z-index:9999;
            box-shadow:0 8px 24px rgba(0,0,0,.2);
            animation:fadeInUp .3s ease;
        `;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }

    // Category filter
    document.querySelectorAll('.cat-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const cat = tab.dataset.cat;
            document.querySelectorAll('.hampers-product-card').forEach(card => {
                if (cat === 'all' || card.dataset.cat === cat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
</script>
@endpush
