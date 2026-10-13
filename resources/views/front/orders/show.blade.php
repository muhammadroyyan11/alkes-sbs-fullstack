@extends('layouts.front')

@section('title', $order->order_number.' — ALKES SBS')

@section('content')
<main class="checkout-page">
    <div class="container">
        <div class="page-heading">
            <span class="eyebrow">Detail Pesanan</span>
            <h1>{{ $order->order_number }}</h1>
            <p>Status: <span class="account-status-badge {{ $order->customerStatus() }}">{{ $order->customerStatusLabel() }}</span></p>
        </div>
        @if(session('success'))<div class="store-alert success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="store-alert error">{{ session('error') }}</div>@endif

        @if($order->payment_status === 'refunded')
        <div class="store-alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;">
            <strong>Refund diproses.</strong>
            Dana dikembalikan {{ $order->refunded_at?->format('d/m/Y H:i') ?: '' }} —
            {{ $order->refund_reason ?: 'hubungi kami untuk keterangan lebih lanjut' }}.
            @if($order->user?->bank_account)<br>Rekening tujuan: <strong>{{ $order->user->bank_account }}</strong>@endif
            @if($order->refund_proof)
            <div style="margin-top:6px;">
                @if(str_ends_with(strtolower($order->refund_proof), '.pdf'))
                <a href="{{ asset($order->refund_proof) }}" target="_blank" rel="noopener" style="color:#b45309;">Lihat Bukti Transfer (PDF)</a>
                @else
                <a href="{{ asset($order->refund_proof) }}" target="_blank" rel="noopener">
                    <img src="{{ asset($order->refund_proof) }}" alt="Bukti transfer refund"
                         style="max-width:220px;border-radius:8px;margin-top:4px;">
                </a>
                @endif
            </div>
            @endif
        </div>
        @endif
        <div class="checkout-layout">
            <section class="checkout-form-card">
                <h3>Produk</h3>
                @foreach($order->items as $item)
                    <div class="order-row">
                        <div><strong>{{ $item->product_name }}</strong><p>{{ $item->sku }} · {{ $item->quantity }} item</p></div>
                        <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                    </div>
                @endforeach
                <h3>Alamat</h3>
                <p>{{ $order->shipping_address }}</p>
                <h3>Pengiriman</h3>
                @if($order->shipment)
                    <p>
                        {{ $order->shipment->courier }}{{ $order->shipment->service ? ' · ' . $order->shipment->service : '' }}
                        · {{ ucfirst($order->shipment->status ?? 'waiting') }}
                    </p>
                    <p>No. Resi: <strong>{{ $order->shipment->tracking_number ?: '-' }}</strong></p>
                    @if($order->shipment->shipped_at)
                        <p>Dikirim: {{ $order->shipment->shipped_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($order->shipment->delivered_at)
                        <p>Diterima: {{ $order->shipment->delivered_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($order->shipment->tracking_url())
                        <p><a href="{{ $order->shipment->tracking_url() }}" target="_blank" rel="noopener noreferrer">Lacak Pengiriman →</a></p>
                    @endif
                @else
                    <p>Belum dikirim</p>
                @endif
            </section>
            <aside class="cart-summary">
                <h3>Ringkasan</h3>
                <div><span>Subtotal</span><strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong></div>
                <div><span>Pengiriman</span><strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong></div><div><span>Biaya Admin</span><strong>Rp {{ number_format($order->admin_fee, 0, ',', '.') }}</strong></div>
                <hr>
                <div class="summary-total"><span>Total</span><strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong></div>
                @if($order->payment_status === 'unpaid')
                <a href="{{ route('payment.index', $order) }}" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-credit-card"></i> Bayar Sekarang
                </a>
                @endif

                @if(in_array($order->status, ['shipped', 'in_transit', 'delivered'], true))
                <form method="POST" action="{{ route('orders.receive', $order) }}" class="mt-3"
                      onsubmit="return confirm('Terima kasih! Pastikan barang sudah diterima dengan baik sebelum menekan tombol ini.')">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-box-check"></i> Pesanan Diterima
                    </button>
                </form>
                <p style="font-size:.75rem;color:#6b7280;text-align:center;margin-top:6px;">
                    Belum menerima barang? Jangan ditekan — hubungi kami dulu.
                </p>
                @endif

                @if($order->status === 'completed')
                <div class="mt-3 text-center" style="font-size:.85rem;color:#16a34a;font-weight:600;">
                    <i class="bi bi-check-circle"></i> Pesanan selesai
                </div>
                @endif
            </aside>
        </div>
    </div>
</main>
@endsection
