@extends('layouts.admin')

@section('title', 'Buat Purchase Receive')
@section('page-title', 'Buat Purchase Receive')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Buat Purchase Receive</h5>
        <a href="{{ route('admin.purchase-receives.index') }}" class="btn btn-secondary">
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

        <form method="POST" action="{{ route('admin.purchase-receives.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Purchase Order *</label>
                <select name="purchase_order_id" class="form-control" id="pr-po-select" required>
                    <option value="">-- Pilih PO --</option>
                    @foreach($purchaseOrders as $po)
                    <option value="{{ $po->id }}" data-items="{{ e(json_encode($poItemsJson[$po->id] ?? [])) }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                        {{ $po->po_number }} - {{ $po->supplier?->name ?? '-' }} ({{ ucfirst($po->status) }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Jumlah Diterima *</label>
                <div id="pr-items-wrap" class="table-responsive">
                    <p class="text-muted mb-0">Pilih PO untuk menampilkan item yang bisa diterima.</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan penerimaan...">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Buat Receive
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('pr-po-select');
    const wrap = document.getElementById('pr-items-wrap');

    function render() {
        const opt = select.selectedOptions[0];
        let items = [];

        if (opt && opt.dataset.items) {
            try { items = JSON.parse(opt.dataset.items); } catch (e) { items = []; }
        }

        if (!items.length) {
            wrap.innerHTML = '<p class="text-muted mb-0">PO ini tidak memiliki item. Penerimaan hanya mencatat status.</p>';
            return;
        }

        let html = '<table class="dt-table" style="width:100%"><thead><tr>' +
            '<th>Produk</th><th>Varian</th><th>Jumlah di PO</th><th style="width:150px">Jumlah Diterima</th>' +
            '</tr></thead><tbody>';

        items.forEach(function (item) {
            html += '<tr>' +
                '<td>' + item.product + '</td>' +
                '<td>' + (item.variant || '-') + '</td>' +
                '<td>' + item.qty + '</td>' +
                '<td><input type="number" name="items[' + item.id + ']" class="form-control" min="0" max="' + item.qty + '" value="' + item.qty + '"></td>' +
                '</tr>';
        });

        html += '</tbody></table>' +
            '<small class="text-muted">Isi 0 untuk item yang belum diterima. Jumlah maksimal = sisa PO.</small>';

        wrap.innerHTML = html;
    }

    select.addEventListener('change', render);
    render();
});
</script>
@endpush
