@extends('layouts.admin')

@section('title', 'Laporan Penjualan POS')
@section('page-title', 'Laporan Penjualan (POS)')

@section('content')
<div class="stats-grid" style="margin-bottom:16px;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dc2626;"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-info">
            <h3>{{ number_format($summary->transactions ?? 0) }}</h3>
            <p>Total Transaksi</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#16a34a;"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <h3>Rp {{ number_format($summary->revenue ?? 0, 0, ',', '.') }}</h3>
            <p>Omzet (setelah diskon)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f59e0b;"><i class="fa-solid fa-percent"></i></div>
        <div class="stat-info">
            <h3>Rp {{ number_format($summary->discount ?? 0, 0, ',', '.') }}</h3>
            <p>Total Diskon</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#2563eb;"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="stat-info">
            <h3>{{ number_format($itemsSold) }}</h3>
            <p>Item Terjual</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h5><i class="fa-solid fa-filter"></i> Filter</h5>
        <a href="{{ route('admin.pos.sales.export', request()->query()) }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-file-csv"></i> Ekspor CSV
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.pos.sales.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div>
                <label style="font-size:.75rem;font-weight:600;color:#6b7280;">Rentang Cepat</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    @foreach(['today' => 'Hari Ini', 'yesterday' => 'Kemarin', '7days' => '7 Hari', '30days' => '30 Hari', 'month' => 'Bulan Ini'] as $value => $label)
                    <a href="{{ route('admin.pos.sales.index', array_merge(request()->query(), ['range' => $value])) }}"
                       class="btn btn-sm {{ ($filters['label'] ?? request('range')) === $value ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:#6b7280;">Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" style="width:150px;">
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:#6b7280;">Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" style="width:150px;">
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:#6b7280;">Pembayaran</label>
                <select name="payment_method" class="form-control" style="width:140px;">
                    <option value="">Semua</option>
                    @foreach(\App\Models\PosSale::PAYMENT_METHODS as $method)
                    <option value="{{ $method }}" @selected(request('payment_method') === $method)>
                        {{ \App\Models\PosSale::paymentMethodLabel($method) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:#6b7280;">Status</label>
                <select name="status" class="form-control" style="width:130px;">
                    <option value="">Semua</option>
                    <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;color:#6b7280;">Cari</label>
                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Invoice / pelanggan" style="width:180px;">
            </div>
            <div style="display:flex;gap:6px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Terapkan</button>
                <a href="{{ route('admin.pos.sales.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h5><i class="fa-solid fa-star"></i> Produk Terlaris (rentang terpilih)</h5>
    </div>
    <div class="card-body">
        @if($topProducts->isEmpty())
        <p class="text-muted" style="margin:0;">Belum ada penjualan pada rentang ini.</p>
        @else
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr><th>SKU</th><th>Produk</th><th>Terjual</th><th>Omzet</th></tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $row)
                    <tr>
                        <td>{{ $row->sku ?: '-' }}</td>
                        <td>
                            {{ $row->product_name }}
                            @if($row->variant_name)<small class="text-muted">· {{ $row->variant_name }}</small>@endif
                        </td>
                        <td><span class="badge badge-info">{{ $row->qty }}</span></td>
                        <td>Rp {{ number_format($row->revenue, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fa-solid fa-cash-register"></i> Daftar Transaksi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Kasir</th>
                        <th>Item</th>
                        <th>Total</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><strong>{{ $sale->invoice }}</strong>@if($sale->customer_name)<br><small class="text-muted">{{ $sale->customer_name }}</small>@endif</td>
                        <td>{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $sale->user?->name ?? '-' }}</td>
                        <td><span class="badge badge-secondary">{{ $sale->items()->sum('quantity') }}</span></td>
                        <td><strong>Rp {{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                        <td>{{ \App\Models\PosSale::paymentMethodLabel($sale->payment_method) }}</td>
                        <td>
                            @if($sale->isCancelled())
                            <span class="badge badge-danger">Dibatalkan</span>
                            @else
                            <span class="badge badge-success">Selesai</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.pos.sales.show', $sale) }}" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>
                            <a href="{{ route('admin.pos.receipt', $sale) }}" class="btn btn-sm btn-secondary" target="_blank"><i class="fa-solid fa-print"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">Tidak ada transaksi pada rentang ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sales->links() }}
    </div>
</div>
@endsection
