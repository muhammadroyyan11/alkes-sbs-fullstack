@extends('layouts.admin')

@section('title', 'Detail Transaksi POS')
@section('page-title', 'Detail Transaksi POS')

@section('content')
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h5><i class="fa-solid fa-receipt"></i> {{ $sale->invoice }}</h5>
        <div>
            <a href="{{ route('admin.pos.receipt', $sale) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-print"></i> Cetak Struk
            </a>
            <a href="{{ route('admin.pos.sales.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Status</label>
                <div>
                    @if($sale->isCancelled())
                    <span class="badge badge-danger">Dibatalkan {{ $sale->cancelled_at?->format('d/m/Y H:i') }}</span>
                    @else
                    <span class="badge badge-success">Selesai</span>
                    @endif
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <input type="text" class="form-control" value="{{ $sale->sold_at?->format('d/m/Y H:i') }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Kasir</label>
                <input type="text" class="form-control" value="{{ $sale->user?->name ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Pelanggan</label>
                <input type="text" class="form-control" value="{{ $sale->customer_name ?? '-' }}" readonly>
            </div>
        </div>

        <h6 style="margin:16px 0 10px;font-weight:600;">Item Terjual</h6>
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if($item->product_id || $item->variant_id)
                            <br><small class="text-muted">
                                @if($item->variant_id) varian #{{ $item->variant_id }} @elseif($item->product_id) produk #{{ $item->product_id }} @endif
                                · stok tercatat di <a href="{{ route('admin.stock-logs.index') }}">Log Stok</a>
                            </small>
                            @endif
                        </td>
                        <td>{{ $item->sku ?: '-' }}</td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td><strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="max-width:340px;margin-left:auto;margin-top:14px;">
            <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Subtotal</span><strong>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</strong></div>
            <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Diskon</span><strong>Rp {{ number_format($sale->discount, 0, ',', '.') }}</strong></div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:1px solid #e5e7eb;font-size:1.05rem;"><span>TOTAL</span><strong>Rp {{ number_format($sale->total, 0, ',', '.') }}</strong></div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;"><span>Metode</span><strong>{{ \App\Models\PosSale::paymentMethodLabel($sale->payment_method) }}</strong></div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;"><span>Uang Diterima</span><strong>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</strong></div>
            <div style="display:flex;justify-content:space-between;padding:2px 0;"><span>Kembalian</span><strong>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</strong></div>
        </div>

        @if($sale->notes)
        <div class="form-group" style="margin-top:14px;">
            <label class="form-label">Catatan</label>
            <textarea class="form-control" rows="2" readonly>{{ $sale->notes }}</textarea>
        </div>
        @endif

        @if(!$sale->isCancelled())
        <hr>
        <form method="POST" action="{{ route('admin.pos.sales.cancel', $sale) }}"
              onsubmit="return confirm('Batalkan transaksi ini? Stok akan dikembalikan ke buku stok jual & gudang.')">
            @csrf
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Alasan Pembatalan (opsional)</label>
                    <input type="text" name="reason" class="form-control" maxlength="200" placeholder="mis. salah input kasir">
                </div>
                <div class="form-group" style="align-self:flex-end;">
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Batalkan Transaksi</button>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
