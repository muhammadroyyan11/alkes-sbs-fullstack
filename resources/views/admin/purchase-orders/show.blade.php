@extends('layouts.admin')

@section('title', 'Detail Purchase Order')
@section('page-title', 'Detail Purchase Order')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Detail Purchase Order</h5>
        <div>
            @if($purchaseOrder->status === 'draft')
            <form method="POST" action="{{ route('admin.purchase-orders.send', $purchaseOrder) }}" style="display:inline;" onsubmit="return confirm('Kirim PO ini ke supplier?')">
                @csrf
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-paper-plane"></i> Kirim PO</button>
            </form>
            <form method="POST" action="{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}" style="display:inline;" onsubmit="return confirm('Batalkan PO ini?')">
                @csrf
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Batalkan</button>
            </form>
            @endif
            <a href="{{ route('admin.purchase-orders.print', $purchaseOrder) }}" class="btn btn-secondary" target="_blank" rel="noopener">
                <i class="fa-solid fa-print"></i> Cetak
            </a>
            <a href="{{ route('admin.purchase-orders.export', $purchaseOrder) }}" class="btn btn-secondary">
                <i class="fa-solid fa-file-csv"></i> Ekspor CSV
            </a>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">No. PO</label>
                <input type="text" class="form-control" value="{{ $purchaseOrder->po_number }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Supplier</label>
                <input type="text" class="form-control" value="{{ $purchaseOrder->supplier?->name ?? '-' }}" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="text" class="form-control" value="{{ $purchaseOrder->created_at->format('d/m/Y H:i') }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                @php
                $statusBadges = [
                    'draft' => 'badge-secondary',
                    'sent' => 'badge-info',
                    'partial' => 'badge-warning',
                    'received' => 'badge-success',
                    'cancelled' => 'badge-danger',
                ];
                @endphp
                <span class="badge {{ $statusBadges[$purchaseOrder->status] ?? 'badge-secondary' }}" style="font-size:.85rem;padding:6px 12px;">
                    {{ ucfirst($purchaseOrder->status) }}
                </span>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Total</label>
            <input type="text" class="form-control" value="Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea class="form-control" rows="3" readonly>{{ $purchaseOrder->notes ?? '-' }}</textarea>
        </div>

        @if($purchaseOrder->items && $purchaseOrder->items->count())
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">Item PO</h6>
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>Variant</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->product?->name ?? '-' }}</td>
                        <td>{{ $item->variant?->name ?? '-' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
