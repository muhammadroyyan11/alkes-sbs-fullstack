@extends('layouts.front')

@section('title', 'Pembayaran Selesai - ALKES SBS')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            @if($order->status === 'cancelled')
                <div class="mb-4">
                    <i class="bi bi-x-octagon text-danger" style="font-size: 4rem;"></i>
                </div>
                <h3 class="text-danger mb-3">Pesanan Dibatalkan</h3>
                <p class="text-muted mb-4">
                    Pesanan #{{ $order->order_number }} dibatalkan karena melewati batas pembayaran
                    {{ \App\Services\OrderExpiry::MINUTES }} menit tanpa pembayaran.
                    Stok sudah dikembalikan.
                </p>
            @elseif($order->payment_status === 'paid')
                <div class="mb-4">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h3 class="text-success mb-3">Pembayaran Berhasil!</h3>
                <p class="text-muted mb-4">Pesanan #{{ $order->order_number }} telah dikonfirmasi. Tim kami akan segera memproses pesanan Anda.</p>
            @elseif($order->payment_status === 'failed')
                <div class="mb-4">
                    <i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
                </div>
                <h3 class="text-danger mb-3">Pembayaran Gagal</h3>
                <p class="text-muted mb-4">Pesanan #{{ $order->order_number }} gagal diproses. Silakan coba bayar ulang.</p>
            @else
                <div class="mb-4">
                    <i class="bi bi-hourglass-split text-warning" style="font-size: 4rem;"></i>
                </div>
                <h3 class="text-warning mb-3">Menunggu Pembayaran</h3>
                <p class="text-muted mb-4">Pesanan #{{ $order->order_number }} sedang menunggu pembayaran.</p>
            @endif

            <div class="d-flex justify-content-center gap-3">
                @if($order->payment_status === 'unpaid' && $order->status !== 'cancelled')
                    <a href="{{ route('payment.index', $order) }}" class="btn btn-success">
                        <i class="bi bi-credit-card"></i> Bayar Sekarang
                    </a>
                @endif
                <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-primary">
                    <i class="bi bi-receipt"></i> Lihat Pesanan
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-bag"></i> Lanjut Belanja
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
