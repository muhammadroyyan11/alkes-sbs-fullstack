@extends('layouts.front')

@section('title', 'Pesanan Saya — ALKES SBS')

@section('content')
<main class="checkout-page customer-orders-page">
    <div class="container">
        <div class="page-heading">
            <span class="eyebrow">Riwayat Transaksi</span>
            <h1>Pesanan Saya</h1>
            <p>Pantau pembayaran, proses pesanan, dan pengiriman Anda.</p>
        </div>

        <nav class="order-filter-tabs" aria-label="Filter status pesanan">
            @foreach([
                null => 'Semua',
                'unpaid' => 'Belum Dibayar',
                'processing' => 'Diproses',
                'shipped' => 'Dikirim',
                'completed' => 'Selesai',
            ] as $key => $label)
                <a href="{{ $key ? route('orders.index', ['status' => $key]) : route('orders.index') }}" class="{{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <section class="checkout-form-card customer-order-list">
            @forelse($orders as $order)
                <a class="customer-order-row" href="{{ route('orders.show', $order) }}">
                    <span class="account-order-icon"><i class="bi bi-box2-heart"></i></span>
                    <div>
                        <strong>{{ $order->order_number }}</strong>
                        <p>{{ $order->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <span class="account-status-badge {{ $order->customerStatus() }}">{{ $order->customerStatusLabel() }}</span>
                    <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @empty
                <div class="account-empty-state">
                    <i class="bi bi-bag-check"></i>
                    <h2>Belum ada pesanan pada status ini</h2>
                    <p>Pesanan Anda akan muncul di sini setelah checkout.</p>
                    <a href="{{ route('products.index') }}">Mulai Belanja</a>
                </div>
            @endforelse
        </section>

        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</main>
@endsection
