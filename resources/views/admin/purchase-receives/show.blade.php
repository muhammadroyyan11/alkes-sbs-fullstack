@extends('layouts.admin')

@section('title', 'Detail Purchase Receive')
@section('page-title', 'Detail Purchase Receive')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Detail Purchase Receive</h5>
        <div>
            @if($purchaseReceive->status === 'pending')
            <form method="POST" action="{{ route('admin.purchase-receives.approve', $purchaseReceive) }}" style="display:inline;" onsubmit="return confirm('Setujui penerimaan ini?')">
                @csrf
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Setujui</button>
            </form>
            <form method="POST" action="{{ route('admin.purchase-receives.reject', $purchaseReceive) }}" style="display:inline;" onsubmit="return confirm('Tolak penerimaan ini?')">
                @csrf
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Tolak</button>
            </form>
            @endif
            <a href="{{ route('admin.purchase-receives.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">No. Receive</label>
                <input type="text" class="form-control" value="{{ $purchaseReceive->receive_number }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">No. PO</label>
                <input type="text" class="form-control" value="{{ $purchaseReceive->purchaseOrder?->po_number ?? '-' }}" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="text" class="form-control" value="{{ $purchaseReceive->created_at?->format('d/m/Y H:i') ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                @php
                $statusBadges = [
                    'pending' => 'badge-warning',
                    'received' => 'badge-success',
                    'rejected' => 'badge-danger',
                ];
                @endphp
                <span class="badge {{ $statusBadges[$purchaseReceive->status] ?? 'badge-secondary' }}" style="font-size:.85rem;padding:6px 12px;">
                    {{ ucfirst($purchaseReceive->status) }}
                </span>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea class="form-control" rows="3" readonly>{{ $purchaseReceive->notes ?? '-' }}</textarea>
        </div>

        @if($purchaseReceive->items && $purchaseReceive->items->count())
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">Item Receive</h6>
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>Variant</th>
                        <th>Jumlah Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseReceive->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->product?->name ?? '-' }}</td>
                        <td>{{ $item->variant?->name ?? '-' }}</td>
                        <td>{{ $item->quantity_received }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
