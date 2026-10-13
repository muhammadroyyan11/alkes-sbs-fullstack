@extends('layouts.admin')

@section('title', 'Kasir (POS)')
@section('page-title', 'Kasir (POS)')

@section('content')
@php($errors = $errors ?? null)

@if($errors && $errors->any())
<div class="card" style="border-left:4px solid var(--primary);">
    <div class="card-body" style="padding:12px 16px;">
        @foreach($errors->all() as $error)
        <div style="color:var(--primary);font-weight:600;">⚠ {{ $error }}</div>
        @endforeach
    </div>
</div>
@endif

<form id="pos-form" method="POST" action="{{ route('admin.pos.checkout') }}">
    @csrf

    <div class="pos-layout">
        {{-- Kiri: cari & pilih barang --}}
        <div class="card">
            <div class="card-header">
                <h5><i class="fa-solid fa-barcode"></i> Pilih Barang</h5>
            </div>
            <div class="card-body">
                <input type="text" id="pos-search" class="form-control" placeholder="Cari nama produk / SKU / varian…" autocomplete="off" autofocus>

                <div id="pos-grid" class="pos-grid">
                    <div class="pos-empty">Memuat produk…</div>
                </div>
            </div>
        </div>

        {{-- Kanan: keranjang & pembayaran --}}
        <div class="card pos-cart">
            <div class="card-header">
                <h5><i class="fa-solid fa-basket-shopping"></i> Keranjang</h5>
                <button type="button" id="pos-clear" class="btn btn-sm btn-secondary">Kosongkan</button>
            </div>
            <div class="card-body">
                <div id="pos-cart-items" class="pos-cart-items">
                    <div class="pos-empty">Keranjang kosong</div>
                </div>

                <div class="pos-field">
                    <label>Pelanggan (opsional)</label>
                    <input type="text" name="customer_name" class="form-control" maxlength="100" placeholder="Nama pelanggan" value="{{ old('customer_name') }}">
                </div>

                <div class="pos-field">
                    <label>Diskon (Rp)</label>
                    <input type="number" id="pos-discount" name="discount" class="form-control" min="0" value="{{ old('discount', 0) }}">
                </div>

                <div class="pos-totals">
                    <div><span>Subtotal</span><strong id="pos-subtotal">Rp 0</strong></div>
                    <div><span>Diskon</span><strong id="pos-discount-view">Rp 0</strong></div>
                    <div class="pos-grand"><span>TOTAL</span><strong id="pos-total">Rp 0</strong></div>
                </div>

                <div class="pos-field">
                    <label>Metode Pembayaran</label>
                    <div class="pos-methods">
                        <label><input type="radio" name="payment_method" value="cash" checked> Tunai</label>
                        <label><input type="radio" name="payment_method" value="transfer"> Transfer</label>
                        <label><input type="radio" name="payment_method" value="qris"> QRIS</label>
                        <label><input type="radio" name="payment_method" value="ewallet"> E-Wallet</label>
                        <label><input type="radio" name="payment_method" value="card"> Kartu</label>
                    </div>
                </div>

                <div class="pos-field">
                    <label>Uang Diterima</label>
                    <input type="number" id="pos-paid" name="paid_amount" class="form-control" min="0" value="{{ old('paid_amount', 0) }}">
                    <div class="pos-quick">
                        <button type="button" class="btn btn-sm btn-secondary" data-cash="pas">Uang Pas</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-cash="50000">50rb</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-cash="100000">100rb</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-cash="200000">200rb</button>
                    </div>
                </div>

                <div class="pos-field">
                    <label>Catatan (opsional)</label>
                    <input type="text" name="notes" class="form-control" maxlength="300" value="{{ old('notes') }}">
                </div>

                <div class="pos-change">
                    <span>Kembalian</span>
                    <strong id="pos-change">Rp 0</strong>
                </div>

                <button type="submit" id="pos-pay" class="btn btn-primary pos-pay-btn" disabled>
                    <i class="fa-solid fa-check"></i> Bayar & Simpan
                </button>
            </div>
        </div>
    </div>
</form>

<div id="pos-source" data-url="{{ route('admin.pos.products') }}" style="display:none;"></div>
@endsection

@push('styles')
<style>
    .pos-layout { display: grid; grid-template-columns: 1fr 380px; gap: 16px; align-items: start; }
    .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin-top: 12px; max-height: 65vh; overflow-y: auto; }
    .pos-item { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; background: #fff; cursor: pointer; transition: .15s; }
    .pos-item:hover { border-color: var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .pos-item.out { opacity: .45; cursor: not-allowed; }
    .pos-item h6 { font-size: .85rem; margin: 0 0 4px; font-weight: 600; }
    .pos-item .sku { font-size: .7rem; color: #9ca3af; }
    .pos-item .price { font-size: .9rem; font-weight: 700; color: var(--primary); margin-top: 6px; }
    .pos-item .stock { font-size: .7rem; color: #6b7280; }
    .pos-empty { grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 24px 0; font-size: .9rem; }
    .pos-cart { position: sticky; top: 76px; }
    .pos-cart-items { max-height: 32vh; overflow-y: auto; margin-bottom: 12px; }
    .pos-line { display: grid; grid-template-columns: 1fr auto; gap: 4px 8px; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
    .pos-line .name { font-size: .85rem; font-weight: 600; }
    .pos-line .price { font-size: .78rem; color: #6b7280; }
    .pos-line .total { font-weight: 700; text-align: right; }
    .pos-line .qty { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .pos-line .qty button { width: 24px; height: 24px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; }
    .pos-line .qty input { width: 52px; text-align: center; border: 1px solid #d1d5db; border-radius: 4px; padding: 3px; }
    .pos-line .remove { border: none; background: none; color: var(--primary); cursor: pointer; font-size: .8rem; }
    .pos-field { margin-bottom: 10px; }
    .pos-field label { display: block; font-size: .78rem; font-weight: 600; color: #6b7280; margin-bottom: 4px; }
    .pos-methods { display: flex; flex-wrap: wrap; gap: 6px; }
    .pos-methods label { display: inline-flex; align-items: center; gap: 4px; font-size: .8rem; font-weight: 500; color: #374151;
        border: 1px solid #d1d5db; border-radius: 999px; padding: 5px 10px; cursor: pointer; margin: 0; }
    .pos-methods input { margin: 0; }
    .pos-totals { border-top: 1px solid #e5e7eb; padding-top: 8px; margin-bottom: 10px; }
    .pos-totals div { display: flex; justify-content: space-between; font-size: .88rem; padding: 2px 0; }
    .pos-totals .pos-grand { font-size: 1.15rem; font-weight: 800; color: var(--primary); border-top: 1px solid #e5e7eb; margin-top: 6px; padding-top: 6px; }
    .pos-quick { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
    .pos-change { display: flex; justify-content: space-between; align-items: center; background: #f0fdf4; border: 1px solid #bbf7d0;
        border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; font-size: .95rem; }
    .pos-change strong { color: #15803d; font-size: 1.1rem; }
    .pos-pay-btn { width: 100%; padding: 12px; font-size: 1rem; font-weight: 700; }
    .pos-pay-btn:disabled { opacity: .5; cursor: not-allowed; }
    @media (max-width: 992px) { .pos-layout { grid-template-columns: 1fr; } .pos-cart { position: static; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const fmt = n => 'Rp ' + Number(n).toLocaleString('id-ID');
    const source = document.getElementById('pos-source');
    const grid = document.getElementById('pos-grid');
    const cartBox = document.getElementById('pos-cart-items');
    const search = document.getElementById('pos-search');
    const discountInput = document.getElementById('pos-discount');
    const paidInput = document.getElementById('pos-paid');
    const form = document.getElementById('pos-form');
    const payBtn = document.getElementById('pos-pay');

    let items = []; // {key, product_id, variant_id, name, sku, price, stock, qty}
    let timer;

    const totals = () => {
        const subtotal = items.reduce((sum, it) => sum + it.price * it.qty, 0);
        const discount = Math.max(0, Number(discountInput.value) || 0);
        const total = Math.max(0, subtotal - discount);
        const paid = Math.max(0, Number(paidInput.value) || 0);
        document.getElementById('pos-subtotal').textContent = fmt(subtotal);
        document.getElementById('pos-discount-view').textContent = fmt(discount);
        document.getElementById('pos-total').textContent = fmt(total);
        document.getElementById('pos-change').textContent = fmt(Math.max(0, paid - total));
        payBtn.disabled = items.length === 0 || paid + 0.001 < total;
        return total;
    };

    const renderCart = () => {
        try { sessionStorage.setItem('pos_cart', JSON.stringify(items)); } catch (e) {}
        if (!items.length) {
            cartBox.innerHTML = '<div class="pos-empty">Keranjang kosong</div>';
            return totals();
        }
        cartBox.innerHTML = items.map((it, i) => `
            <div class="pos-line">
                <div>
                    <div class="name">${it.name}</div>
                    <div class="price">${it.sku ? it.sku + ' · ' : ''}${fmt(it.price)}</div>
                    <button type="button" class="remove" data-remove="${i}">hapus</button>
                </div>
                <div>
                    <div class="total">${fmt(it.price * it.qty)}</div>
                    <div class="qty">
                        <button type="button" data-dec="${i}">−</button>
                        <input type="number" min="1" max="${it.stock}" value="${it.qty}" data-qty="${i}">
                        <button type="button" data-inc="${i}">+</button>
                    </div>
                </div>
            </div>`).join('');
        totals();
    };

    const renderGrid = list => {
        if (!list.length) {
            grid.innerHTML = '<div class="pos-empty">Produk tidak ditemukan</div>';
            return;
        }
        grid.innerHTML = list.map(p => `
            <div class="pos-item ${p.stock <= 0 ? 'out' : ''}" data-key="${p.key}">
                <h6>${p.name}</h6>
                <div class="sku">${p.sku || '-'}</div>
                <div class="price">${fmt(p.price)}</div>
                <div class="stock">Stok: ${p.stock}</div>
            </div>`).join('');
        grid.querySelectorAll('.pos-item').forEach(el => {
            el.addEventListener('click', () => add(list.find(p => p.key === el.dataset.key)));
        });
    };

    const add = p => {
        if (!p || p.stock <= 0) return;
        const found = items.find(it => it.key === p.key);
        if (found) {
            if (found.qty >= p.stock) { alert('Stok ' + p.name + ' tersisa ' + p.stock); return; }
            found.qty++;
        } else {
            items.push({...p, qty: 1});
        }
        renderCart();
    };

    const load = async () => {
        try {
            const res = await fetch(source.dataset.url + '?search=' + encodeURIComponent(search.value), {headers: {Accept: 'application/json'}});
            const data = await res.json();
            renderGrid(data.items || []);
        } catch (e) {
            grid.innerHTML = '<div class="pos-empty">Gagal memuat produk</div>';
        }
    };

    search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); });
    discountInput.addEventListener('input', totals);
    paidInput.addEventListener('input', totals);

    document.querySelectorAll('.pos-quick button').forEach(btn => {
        btn.addEventListener('click', () => {
            paidInput.value = btn.dataset.cash === 'pas' ? totals() : btn.dataset.cash;
            totals();
        });
    });

    document.getElementById('pos-clear').addEventListener('click', () => {
        items = [];
        try { sessionStorage.removeItem('pos_cart'); } catch (e) {}
        renderCart();
    });

    cartBox.addEventListener('click', e => {
        const rm = e.target.dataset.remove, inc = e.target.dataset.inc, dec = e.target.dataset.dec;
        if (rm !== undefined) { items.splice(Number(rm), 1); return renderCart(); }
        if (inc !== undefined || dec !== undefined) {
            const i = Number(inc ?? dec);
            const next = items[i].qty + (inc !== undefined ? 1 : -1);
            items[i].qty = Math.max(1, Math.min(items[i].stock, next));
            return renderCart();
        }
    });

    cartBox.addEventListener('change', e => {
        const i = e.target.dataset.qty;
        if (i === undefined) return;
        items[Number(i)].qty = Math.max(1, Math.min(items[Number(i)].stock, Number(e.target.value) || 1));
        renderCart();
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        if (!items.length) { alert('Keranjang kosong'); return; }
        form.querySelectorAll('[data-dyn]').forEach(el => el.remove());
        items.forEach(it => {
            ['product_id', 'variant_id', 'quantity'].forEach(field => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${items.indexOf(it)}][${field}]`;
                input.value = field === 'quantity' ? it.qty : (it[field] ?? '');
                input.setAttribute('data-dyn', '1');
                form.appendChild(input);
            });
        });
        payBtn.disabled = true;
        form.submit();
    });

    try {
        const saved = JSON.parse(sessionStorage.getItem('pos_cart') || '[]');
        if (Array.isArray(saved)) items = saved;
    } catch (e) { items = []; }

    load();
    renderCart();
})();
</script>
@endpush
