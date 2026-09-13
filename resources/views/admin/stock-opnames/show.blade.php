@extends('layouts.admin')

@section('title', 'Detail Stok Opname')
@section('page-title', 'Detail Stok Opname')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Detail Stok Opname</h5>
        <div>
            @if($stockOpname->status === 'pending')
            <form method="POST" action="{{ route('admin.stock-opnames.approve', $stockOpname) }}" style="display:inline;" onsubmit="return confirm('Setujui stok opname ini?')">
                @csrf
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Setujui</button>
            </form>
            <form method="POST" action="{{ route('admin.stock-opnames.reject', $stockOpname) }}" style="display:inline;" onsubmit="return confirm('Tolak stok opname ini?')">
                @csrf
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Tolak</button>
            </form>
            @endif
            <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kode</label>
                <input type="text" class="form-control" value="SO-{{ str_pad($stockOpname->id, 4, '0', STR_PAD_LEFT) }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="text" class="form-control" value="{{ $stockOpname->created_at->format('d/m/Y H:i') }}" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Status</label>
                @php
                $statusBadges = [
                    'pending' => 'badge-warning',
                    'counted' => 'badge-info',
                    'approved' => 'badge-success',
                    'rejected' => 'badge-danger',
                ];
                @endphp
                <span class="badge {{ $statusBadges[$stockOpname->status] ?? 'badge-secondary' }}" style="font-size:.85rem;padding:6px 12px;">
                    {{ ucfirst($stockOpname->status) }}
                </span>
            </div>
            <div class="form-group">
                <label class="form-label">User</label>
                <input type="text" class="form-control" value="{{ $stockOpname->user?->name ?? '-' }}" readonly>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea class="form-control" rows="3" readonly>{{ $stockOpname->notes ?? '-' }}</textarea>
        </div>

        {{-- Count Form (only for pending status) --}}
        @if($stockOpname->status === 'pending' && $stocks->count())
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">
            <i class="fa-solid fa-clipboard-check"></i> Form Penghitungan Stok
        </h6>
        <form method="POST" action="{{ route('admin.stock-opnames.count', $stockOpname) }}">
            @csrf
            <div class="table-responsive">
                <table class="dt-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Variant</th>
                            <th>Stok Sistem</th>
                            <th>Stok Fisik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stocks as $i => $stock)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $stock->product?->name ?? '-' }}</td>
                            <td>{{ $stock->variant?->name ?? '-' }}</td>
                            <td>{{ $stock->quantity }}</td>
                            <td>
                                <input type="hidden" name="items[{{ $i }}][stock_id]" value="{{ $stock->id }}">
                                <input type="number" name="items[{{ $i }}][physical_stock]" class="form-control" value="{{ $stock->quantity }}" min="0" style="width:100px;">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:12px;">
                <i class="fa-solid fa-save"></i> Simpan Data Counting
            </button>
        </form>
        @endif

        {{-- Items (for counted/approved/rejected) --}}
        @if($stockOpname->items && $stockOpname->items->count())
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">Item Stok Opname</h6>
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>Variant</th>
                        <th>Stok Sistem</th>
                        <th>Stok Fisik</th>
                        <th>Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->product?->name ?? '-' }}</td>
                        <td>{{ $item->variant?->name ?? '-' }}</td>
                        <td>{{ $item->system_stock }}</td>
                        <td>{{ $item->physical_stock }}</td>
                        <td>
                            @php $diff = $item->physical_stock - $item->system_stock; @endphp
                            <span class="badge {{ $diff > 0 ? 'badge-success' : ($diff < 0 ? 'badge-danger' : 'badge-secondary') }}">
                                {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
