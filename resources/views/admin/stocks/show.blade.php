@extends('layouts.admin')

@section('title', 'Detail Stok')
@section('page-title', 'Detail Stok')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Detail Stok</h5>
        <a href="{{ route('admin.stocks.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Produk</label>
                <input type="text" class="form-control" value="{{ $stock->product?->name ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Variant</label>
                <input type="text" class="form-control" value="{{ $stock->variant?->name ?? '-' }}" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Gudang</label>
                <input type="text" class="form-control" value="{{ $stock->warehouse?->name ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah</label>
                @php
                $cls = $stock->quantity <= 5 ? 'badge-danger' : ($stock->quantity <= 20 ? 'badge-warning' : 'badge-success');
                @endphp
                <div style="padding:10px 0;">
                    <span class="badge {{ $cls }}" style="font-size:1rem;padding:8px 16px;">{{ $stock->quantity }} {{ $stock->product?->unit ?? 'pcs' }}</span>
                </div>
            </div>
        </div>

        @if($stock->mutations && $stock->mutations->count())
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">Riwayat Mutasi Stok</h6>
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stock->mutations as $mutation)
                    <tr>
                        <td>{{ $mutation->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($mutation->type === 'in')
                            <span class="badge badge-success">Masuk</span>
                            @elseif($mutation->type === 'out')
                            <span class="badge badge-danger">Keluar</span>
                            @else
                            <span class="badge badge-warning">Penyesuaian</span>
                            @endif
                        </td>
                        <td>{{ $mutation->quantity }}</td>
                        <td>{{ $mutation->note ?? '-' }}</td>
                        <td>{{ $mutation->creator?->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="alert alert-warning" style="margin-top:20px;">
            <i class="fa-solid fa-info-circle"></i> Belum ada riwayat mutasi stok.
        </div>
        @endif
    </div>
</div>
@endsection
