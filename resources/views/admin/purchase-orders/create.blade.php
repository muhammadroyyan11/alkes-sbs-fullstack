@extends('layouts.admin')

@section('title', 'Buat Purchase Order')
@section('page-title', 'Buat Purchase Order')

@push('styles')
<style>
    .po-item-row td { vertical-align: middle; }
    .po-total-box { text-align: right; font-size: 1.1rem; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Buat Purchase Order</h5>
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.purchase-orders.store') }}" id="po-form">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="form-label">Supplier *</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}{{ $supplier->contact_person ? ' (' . $supplier->contact_person . ')' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="1" placeholder="Catatan untuk PO ini...">{{ old('notes') }}</textarea>
                </div>
            </div>

            <h6 style="margin-top:15px;margin-bottom:10px;font-weight:600;">Item PO</h6>
            <div class="form-row mb-2">
                <div class="col-md-7">
                    <select id="po-product-picker" class="form-control">
                        <option value="">-- Pilih produk / varian --</option>
                        @foreach($products as $product)
                            @if($product->variants && $product->variants->count())
                                @foreach($product->variants as $variant)
                                <option value="v{{ $variant->id }}" data-product="{{ $product->id }}" data-price="{{ $variant->price }}" data-label="{{ $product->name }} - {{ $variant->name }}">
                                    {{ $product->name }} - {{ $variant->name }} ({{ $variant->sku }})
                                </option>
                                @endforeach
                            @else
                            <option value="p{{ $product->id }}" data-product="{{ $product->id }}" data-price="{{ $product->price }}" data-label="{{ $product->name }}">
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <button type="button" class="btn btn-outline-primary" id="po-add-item">
                        <i class="fa-solid fa-plus"></i> Tambah Item
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="dt-table" style="width:100%" id="po-items-table">
                    <thead>
                        <tr>
                            <th style="width:38%">Produk</th>
                            <th style="width:14%">Jumlah</th>
                            <th style="width:20%">Harga Satuan</th>
                            <th style="width:20%">Subtotal</th>
                            <th style="width:8%"></th>
                        </tr>
                    </thead>
                    <tbody id="po-items-body"></tbody>
                </table>
            </div>
            <div class="po-total-box">Total: <span id="po-total">Rp 0</span></div>

            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Buat PO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('po-product-picker');
    const body = document.getElementById('po-items-body');
    const addBtn = document.getElementById('po-add-item');
    const totalEl = document.getElementById('po-total');
    const picked = {};

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function recalc() {
        let total = 0;
        body.querySelectorAll('tr').forEach(function (tr) {
            const qty = parseFloat(tr.querySelector('.po-qty').value) || 0;
            const price = parseFloat(tr.querySelector('.po-price').value) || 0;
            const sub = qty * price;
            total += sub;
            tr.querySelector('.po-subtotal').textContent = formatRp(sub);
        });
        totalEl.textContent = formatRp(total);
    }

    function addRow() {
        const opt = picker.selectedOptions[0];
        if (!opt || !opt.value) return;
        if (picked[opt.value]) {
            alert('Item sudah ditambahkan.');
            return;
        }
        picked[opt.value] = true;

        const key = opt.value;
        const productId = opt.dataset.product;
        const variantId = key.charAt(0) === 'v' ? key.slice(1) : '';
        const price = parseFloat(opt.dataset.price) || 0;

        const tr = document.createElement('tr');
        tr.className = 'po-item-row';
        tr.dataset.key = key;
        tr.innerHTML =
            '<td>' + opt.dataset.label +
                '<input type="hidden" name="items[][product_id]" value="' + productId + '">' +
                '<input type="hidden" name="items[][variant_id]" value="' + variantId + '">' +
            '</td>' +
            '<td><input type="number" name="items[][quantity]" class="form-control po-qty" value="1" min="1" required style="width:90px"></td>' +
            '<td><input type="number" name="items[][price]" class="form-control po-price" value="' + price + '" min="0" step="1" required style="width:140px"></td>' +
            '<td class="po-subtotal">' + formatRp(price) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger po-remove"><i class="fa-solid fa-trash"></i></button></td>';
        body.appendChild(tr);

        tr.querySelector('.po-qty').addEventListener('input', recalc);
        tr.querySelector('.po-price').addEventListener('input', recalc);
        tr.querySelector('.po-remove').addEventListener('click', function () {
            delete picked[key];
            tr.remove();
            recalc();
        });

        picker.selectedIndex = 0;
        recalc();
    }

    addBtn.addEventListener('click', addRow);

    const params = new URLSearchParams(window.location.search);
    if (params.get('items') === 'add') addRow();
    recalc();
});
</script>
@endpush
