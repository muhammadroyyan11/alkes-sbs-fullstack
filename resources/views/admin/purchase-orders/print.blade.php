<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak {{ $purchaseOrder->po_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 14px; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #555; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 3px 10px; border: 1px solid #111; border-radius: 999px; font-size: 12px; text-transform: uppercase; }
        .meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 32px; margin-bottom: 18px; }
        .meta div { display: flex; gap: 8px; }
        .meta span:first-child { width: 110px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #111; padding: 7px 9px; text-align: left; }
        th { background: #f2f2f2; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f8f8f8; }
        .notes { border: 1px solid #111; padding: 10px; margin-bottom: 20px; white-space: pre-wrap; }
        .sign { display: flex; justify-content: space-between; margin-top: 48px; }
        .sign div { width: 200px; text-align: center; }
        .sign .line { border-top: 1px dashed #111; margin-top: 60px; padding-top: 6px; }
        .actions { margin-bottom: 20px; }
        .actions button, .actions a { font: inherit; padding: 8px 16px; border: 1px solid #111; background: #fff; cursor: pointer; text-decoration: none; color: #111; border-radius: 4px; }
        .actions button { background: #111; color: #fff; }
        @media print { .actions { display: none; } body { margin: 0; } @page { margin: 14mm; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">🖨️ Cetak</button>
        <a href="{{ route('admin.purchase-orders.export', $purchaseOrder) }}">⬇️ Ekspor CSV</a>
        <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}">← Kembali</a>
    </div>

    <div class="head">
        <div>
            <h1>PURCHASE ORDER</h1>
            <div class="muted">{{ config('app.name', 'ALKES SBS') }}</div>
        </div>
        <div style="text-align:right;">
            <div><strong>{{ $purchaseOrder->po_number }}</strong></div>
            <div class="muted">{{ $purchaseOrder->created_at->format('d/m/Y H:i') }}</div>
            <div><span class="badge">{{ ucfirst($purchaseOrder->status) }}</span></div>
        </div>
    </div>

    <div class="meta">
        <div><span>Supplier</span><strong>{{ $purchaseOrder->supplier?->name ?? '-' }}</strong></div>
        <div><span>Kontak</span><strong>{{ $purchaseOrder->supplier?->phone ?: ($purchaseOrder->supplier?->email ?? '-') }}</strong></div>
        <div><span>Dibuat oleh</span><strong>{{ $purchaseOrder->user?->name ?? '-' }}</strong></div>
        <div><span>Total</span><strong>Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:36px;">No</th>
                <th>SKU</th>
                <th>Produk</th>
                <th>Varian</th>
                <th class="num">Jumlah</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->variant?->sku ?: ($item->product?->sku ?? '-') }}</td>
                <td>{{ $item->product?->name ?? '-' }}</td>
                <td>{{ $item->variant?->name ?? '-' }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;">Tidak ada item</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="num">Total</td>
                <td class="num">Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if($purchaseOrder->notes)
    <div class="notes"><strong>Catatan:</strong> {{ $purchaseOrder->notes }}</div>
    @endif

    <div class="sign">
        <div>
            <div class="muted">Penerima</div>
            <div class="line">( {{ $purchaseOrder->supplier?->name ?? '________________' }} )</div>
        </div>
        <div>
            <div class="muted">Mengetahui,</div>
            <div class="line">( {{ $purchaseOrder->user?->name ?? '________________' }} )</div>
        </div>
    </div>
</body>
</html>
