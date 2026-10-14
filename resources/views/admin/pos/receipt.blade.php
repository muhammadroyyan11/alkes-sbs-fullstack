<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->invoice }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 14px; margin: 24px; }
        .wrap { max-width: 420px; margin: 0 auto; }
        .head { text-align: center; border-bottom: 2px dashed #111; padding-bottom: 10px; margin-bottom: 10px; }
        .head h1 { font-size: 18px; margin: 0; }
        .head p { margin: 3px 0 0; color: #555; font-size: 12px; }
        .meta { font-size: 12px; color: #444; margin-bottom: 10px; }
        .meta div { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 10px; }
        td { padding: 4px 0; vertical-align: top; }
        td.num { text-align: right; white-space: nowrap; }
        .line { border-top: 1px dashed #111; padding-top: 6px; }
        .totals div { display: flex; justify-content: space-between; padding: 2px 0; font-size: 13px; }
        .totals .grand { font-size: 16px; font-weight: bold; border-top: 1px solid #111; margin-top: 4px; padding-top: 6px; }
        .status { text-align: center; margin-top: 10px; font-size: 12px; color: #555; }
        .status b.cancelled { color: #b91c1c; }
        .actions { display: flex; gap: 8px; justify-content: center; margin-top: 18px; }
        .actions button, .actions a { font: inherit; font-size: 13px; padding: 8px 14px; border: 1px solid #111; background: #fff; color: #111; cursor: pointer; border-radius: 4px; text-decoration: none; }
        .actions button { background: #111; color: #fff; }
        @media print { .actions { display: none; } body { margin: 0; } @page { margin: 10mm; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="actions">
            <button type="button" onclick="window.print()">🖨️ Cetak Struk</button>
            <a href="{{ route('admin.pos.sales.show', $sale) }}">Detail Transaksi</a>
            <a href="{{ route('admin.pos.cashier') }}">Kembali ke Kasir</a>
        </div>

        <div class="head">
            <h1>{{ config('app.name', 'ALKES SBS') }}</h1>
            <p>Struk Penjualan Point of Sale</p>
        </div>

        <div class="meta">
            <div><span>Invoice</span><b>{{ $sale->invoice }}</b></div>
            <div><span>Tanggal</span><span>{{ $sale->sold_at?->format('d/m/Y H:i') }}</span></div>
            <div><span>Kasir</span><span>{{ $sale->user?->name ?? '-' }}</span></div>
            <div><span>Pelanggan</span><span>{{ $sale->customer_name ?? '-' }}</span></div>
            <div><span>Metode</span><span>{{ \App\Models\PosSale::paymentMethodLabel($sale->payment_method) }}</span></div>
        </div>

        <table>
            @foreach($sale->items as $item)
            <tr>
                <td>
                    {{ $item->product_name }}<br>
                    <small>{{ $item->quantity }} × Rp {{ number_format($item->price, 0, ',', '.') }}</small>
                </td>
                <td class="num">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </table>

        <div class="totals line">
            <div><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
            <div><span>Diskon</span><span>Rp {{ number_format($sale->discount, 0, ',', '.') }}</span></div>
            <div class="grand"><span>Total</span><span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span></div>
            <div><span>Bayar</span><span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span></div>
            <div><span>Kembali</span><span>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span></div>
        </div>

        @if($sale->notes)
        <div class="meta" style="margin-top:10px;"><div><span>Catatan</span><span>{{ $sale->notes }}</span></div></div>
        @endif

        <div class="status">
            @if($sale->isCancelled())
                <b class="cancelled">DIBATALKAN {{ $sale->cancelled_at?->format('d/m/Y H:i') }}</b>
            @else
                Terima kasih sudah berbelanja
            @endif
        </div>
    </div>

    {{-- Transaksi sukses: kosongkan keranjang kasir agar tidak muncul lagi
         saat kembali dari halaman struk. --}}
    <script>
    try { sessionStorage.removeItem('pos_cart'); } catch (e) {}
    </script>
</body>
</html>
