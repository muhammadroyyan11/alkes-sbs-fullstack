@extends('layouts.admin')

@section('title', 'Detail Pesanan')
@section('page-title', 'Detail Pesanan')

@section('content')
@php
    $payLogo = ['bca_va','bri_va','mandiri_va','bni_va','permata_va','qris','gopay','shopeepay','dana','alfamart','indomaret'];
@endphp

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
@endif

@if($order->isOverdue())
<div class="alert alert-danger" style="margin-bottom:16px;">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <strong>Lewat 1 Hari!</strong>
    Pesanan dibuat {{ $order->created_at->format('d/m/Y H:i') }} ({{ $order->ageLabel() }} lalu)
    dan belum diproses — target SLA {{ \App\Models\Order::SLA_HOURS }} jam.
    Segera selesaikan proses pesanan ini.
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5><i class="fa-solid fa-receipt"></i> {{ $order->order_number }}</h5>
        <div>
            <span class="badge {{ $order->paymentBadgeClass() }}" style="font-size:.8rem;padding:5px 12px;">{{ $order->paymentStatusLabel() }}</span>
            <span class="badge {{ $order->statusBadgeClass() }}" style="font-size:.8rem;padding:5px 12px;">{{ $order->statusLabel() }}</span>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
    </div>
    <div class="card-body">

        {{-- ═══ Aksi ═══ --}}
        @if($availableActions['mark_paid'] || $availableActions['process'] || $availableActions['ship'] || $availableActions['complete'] || $availableActions['cancel'] || $availableActions['refund'])
        <div style="background:#fafafa;border:1px solid #eee;border-radius:10px;padding:16px;margin-bottom:20px;">
            <h6 style="font-weight:600;margin-bottom:12px;"><i class="fa-solid fa-bolt"></i> Proses Pesanan</h6>

            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;">
                @if($availableActions['mark_paid'])
                <form method="POST" action="{{ route('admin.orders.pay', $order) }}" onsubmit="return confirm('Tandai pesanan ini sebagai LUNAS?')">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-money-bill-wave"></i> Konfirmasi Pembayaran (Lunas)</button>
                </form>
                @endif

                @if($availableActions['process'])
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    <input type="hidden" name="status" value="processing">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-gears"></i> Mulai Diproses</button>
                </form>
                @endif

                @if($availableActions['ship'])
                <form method="POST" action="{{ route('admin.orders.ship', $order) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    @csrf
                    <input type="text" name="tracking_number" class="form-control" placeholder="Nomor resi" style="width:180px;" required>
                    <input type="text" name="courier" class="form-control" placeholder="Kurir (JNE, J&T, GoSend)" style="width:170px;" value="{{ $order->shipment?->courier ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-truck-fast"></i> Kirim</button>
                </form>
                @endif

                @if($availableActions['complete'])
                <form method="POST" action="{{ route('admin.orders.status', $order) }}" onsubmit="return confirm('Tandai pesanan sebagai SELESAI?')">
                    @csrf
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-circle-check"></i> Selesaikan</button>
                </form>
                @endif

                @if($availableActions['cancel'])
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini? Stok akan dikembalikan.')">
                    @csrf
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Batalkan</button>
                </form>
                @endif
            </div>

            @if($availableActions['refund'])
            <form method="POST" action="{{ route('admin.orders.refund', $order) }}"
                  enctype="multipart/form-data"
                  style="margin-top:14px;padding-top:14px;border-top:1px dashed #e5e7eb;"
                  onsubmit="return confirm('Proses REFUND untuk pesanan ini?\n\n- Dana dikembalikan ke pembeli\n- Stok barang masuk kembali ke gudang\n- Tidak bisa dibatalkan')">
                @csrf
                <h6 style="font-weight:600;margin-bottom:8px;color:#b45309;"><i class="fa-solid fa-rotate-left"></i> Prosedur Refund</h6>

                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;margin-bottom:10px;font-size:.85rem;color:#78350f;">
                    <i class="fa-solid fa-building-columns"></i>
                    Rekening penerima (dari profil customer):
                    <strong>{{ $order->user?->bank_account ?: 'belum diisi — minta customer isi di menu Akun' }}</strong>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <input type="text" name="refund_reason" class="form-control" maxlength="300" required
                           placeholder="Alasan refund (mis. barang rusak, pembeli komplain)" style="flex:1;min-width:240px;">
                    <input type="file" name="refund_proof" class="form-control" accept="image/*,.pdf" style="width:240px;">
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-rotate-left"></i> Proses Refund</button>
                </div>
                <small style="display:block;color:#9ca3af;margin-top:6px;">
                    Unggah <strong>Bukti Transfer</strong> pengembalian dana (JPG/PNG/WEBP/PDF, maks 4 MB) — wajib bila transfer bank manual.
                </small>
            </form>
            @endif
        </div>
        @endif

        @if($order->status === 'refunded' || $order->payment_status === 'refunded')
        <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
            <h6 style="font-weight:600;margin:0 0 6px;color:#b45309;"><i class="fa-solid fa-rotate-left"></i> Refund Diproses</h6>
            <div style="font-size:.85rem;color:#78350f;">
                Tanggal: <strong>{{ $order->refunded_at?->format('d/m/Y H:i') ?: '-' }}</strong> ·
                Oleh: <strong>{{ $order->refundedBy?->name ?? '-' }}</strong><br>
                Alasan: {{ $order->refund_reason ?: '-' }}<br>
                Rekening penerima: <strong>{{ $order->user?->bank_account ?: '-' }}</strong>
            </div>
            @if($order->refund_proof)
            <div style="margin-top:8px;">
                <div style="font-size:.8rem;font-weight:600;color:#78350f;margin-bottom:4px;">Bukti Transfer</div>
                @if(str_ends_with(strtolower($order->refund_proof), '.pdf'))
                <a href="{{ asset($order->refund_proof) }}" target="_blank" rel="noopener"
                   style="font-size:.85rem;color:#b45309;">Lihat bukti (PDF)</a>
                @else
                <a href="{{ asset($order->refund_proof) }}" target="_blank" rel="noopener">
                    <img src="{{ asset($order->refund_proof) }}" alt="Bukti transfer refund"
                         style="max-width:260px;border-radius:8px;border:1px solid #fcd34d;">
                </a>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- ═══ Info Pesanan ═══ --}}
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tanggal Pesanan</label>
                <input type="text" class="form-control" value="{{ $order->created_at->format('d/m/Y H:i') }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <div class="form-control" style="display:flex;align-items:center;gap:8px;">
                    @if(in_array($order->payment_method, $payLogo, true))
                    <img src="{{ asset('img/payments/'.$order->payment_method.'.svg') }}" alt="{{ $order->payment_method }}" style="width:26px;height:26px;object-fit:contain;border:1px solid #e8edf3;border-radius:6px;">
                    @endif
                    <span>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Dibayar Pada</label>
                <input type="text" class="form-control" value="{{ $order->paid_at?->format('d/m/Y H:i') ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Ref. Transaksi</label>
                <input type="text" class="form-control" value="{{ $order->payment_reference ?? '-' }}" readonly>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Alamat Pengiriman</label>
            <textarea class="form-control" rows="2" readonly>{{ $order->shipping_address }}</textarea>
        </div>

        @if($order->notes)
        <div class="form-group">
            <label class="form-label">Catatan Pembeli</label>
            <textarea class="form-control" rows="2" readonly>{{ $order->notes }}</textarea>
        </div>
        @endif

        {{-- ═══ Item ═══ --}}
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">Item Pesanan</h6>
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th class="text-end">Harga</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->sku }}</td>
                        <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="5" class="text-end">Subtotal</td>
                        <td class="text-end">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end">Ongkir</td>
                        <td class="text-end">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end">Biaya Admin</td>
                        <td class="text-end">Rp {{ number_format($order->admin_fee, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end"><strong>Total</strong></td>
                        <td class="text-end"><strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ═══ Pengiriman ═══ --}}
        <h6 style="margin-top:20px;margin-bottom:10px;font-weight:600;">Pengiriman</h6>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kurir</label>
                <input type="text" class="form-control" value="{{ $order->shipment?->courier ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Layanan</label>
                <input type="text" class="form-control" value="{{ $order->shipment?->service ?? '-' }}" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nomor Resi</label>
                <input type="text" class="form-control" value="{{ $order->shipment?->tracking_number ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Status Kirim</label>
                <input type="text" class="form-control" value="{{ $order->shipment?->status ?? '-' }}" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Dikirim Pada</label>
                <input type="text" class="form-control" value="{{ $order->shipment?->shipped_at?->format('d/m/Y H:i') ?? '-' }}" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Diterima Pada</label>
                <input type="text" class="form-control" value="{{ $order->shipment?->delivered_at?->format('d/m/Y H:i') ?? '-' }}" readonly>
            </div>
        </div>

    </div>
</div>

{{-- ═══ Log keluar-masuk barang untuk pesanan ini ═══ --}}
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h5><i class="fa-solid fa-arrow-right-arrow-left"></i> Log Stok Pesanan</h5>
        <a href="{{ route('admin.stock-logs.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-list"></i> Semua Log Stok
        </a>
    </div>
    <div class="card-body">
        @if($order->stockLogs->isEmpty())
            <div class="alert alert-warning" style="margin:0;">
                <i class="fa-solid fa-info-circle"></i>
                Belum ada log stok untuk pesanan ini (dibuat sebelum fitur log aktif, atau stok belum ditahan).
            </div>
        @else
            <div class="table-responsive">
                <table class="dt-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Jumlah</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->stockLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td><span class="badge {{ $log->typeBadgeClass() }}">{{ $log->typeLabel() }}</span></td>
                            <td><span class="badge {{ $log->statusBadgeClass() }}">{{ $log->statusLabel() }}</span></td>
                            <td>{{ $log->type === 'out' ? '-' : '+' }}{{ $log->quantity }}</td>
                            <td>{{ $log->note ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
