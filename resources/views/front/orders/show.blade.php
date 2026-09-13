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
                <p>{{ $order->shipment?->courier }} · {{ ucfirst($order->shipment?->status ?? 'waiting') }}</p>
            </section>
            <aside class="cart-summary">
                <h3>Ringkasan</h3>
                <div><span>Subtotal</span><strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong></div>
                <div><span>Pengiriman</span><strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong></div>
                <hr>
                <div class="summary-total"><span>Total</span><strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong></div>
            </aside>
        </div>
    </div>
</main>
@endsection
