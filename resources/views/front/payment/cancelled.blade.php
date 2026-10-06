@extends('layouts.front')

@section('title', 'Pesanan Dibatalkan - ALKES SBS')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="mb-4">
                <i class="bi bi-x-octagon text-danger" style="font-size: 4rem;"></i>
            </div>
            <h3 class="text-danger mb-3">Pesanan Dibatalkan</h3>
            <p class="text-muted mb-2">
                Pesanan <strong>#{{ $order->order_number }}</strong>
                (dibuat {{ $order->created_at->format('d/m/Y H:i') }}) dibatalkan otomatis karena
                melewati batas pembayaran <strong>{{ \App\Services\OrderExpiry::MINUTES }} menit</strong>
                tanpa pembayaran masuk.
            </p>
            <p class="text-muted mb-4">
                Stok yang sudah ditahan untuk pesanan ini telah dikembalikan ke stok utama.
                Silakan buat pesanan baru bila masih ingin membeli.
            </p>

            <div class="alert alert-light border text-start mx-auto" style="max-width: 460px;">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Status</span>
                    <span class="badge bg-danger">{{ $order->statusLabel() }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Pembayaran</span>
                    <span class="badge bg-warning text-dark">{{ $order->paymentStatusLabel() }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Total</span>
                    <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-primary">
                    <i class="bi bi-receipt"></i> Lihat Pesanan
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-success">
                    <i class="bi bi-bag"></i> Belanja Lagi
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
