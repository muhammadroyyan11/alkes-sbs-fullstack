@extends('layouts.front')

@section('title', 'Pembayaran - ALKES SBS')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detail Pesanan #{{ $order->order_number }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Status Pembayaran</div>
                        <div class="col-sm-8">
                            @if($order->payment_status === 'paid')
                                <span class="badge bg-success">Lunas</span>
                            @elseif($order->payment_status === 'failed')
                                <span class="badge bg-danger">Gagal</span>
                            @else
                                <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Metode Pembayaran</div>
                        <div class="col-sm-8 d-flex align-items-center gap-2">
                            @if(in_array($order->payment_method, ['bca_va','bri_va','mandiri_va','bni_va','permata_va','qris','gopay','shopeepay','dana','alfamart','indomaret'], true))
                                <img src="{{ asset('img/payments/'.$order->payment_method.'.svg') }}" alt="{{ $order->payment_method }}" style="width:32px;height:32px;object-fit:contain;border:1px solid #e8edf3;border-radius:7px;">
                            @endif
                            <span>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Total</div>
                        <div class="col-sm-8 fw-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Item Pesanan</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}<br><small class="text-muted">{{ $item->sku }}</small></td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Subtotal</td>
                                <td class="text-end">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Ongkir</td>
                                <td class="text-end">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Biaya Admin</td>
                                <td class="text-end">Rp {{ number_format($order->admin_fee, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($order->payment_status === 'unpaid' && $snapToken)
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <p class="mb-3">Klik tombol di bawah untuk melakukan pembayaran:</p>
                    <button id="pay-button" class="btn btn-lg btn-success">
                        <i class="bi bi-credit-card"></i> Bayar Sekarang
                    </button>
                    <p class="small text-muted mt-2">Anda akan diarahkan ke halaman pembayaran Midtrans</p>
                </div>
            </div>
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Lihat Detail Pesanan
                </a>
            </div>
        </div>
    </div>
</div>

@if($order->payment_status === 'unpaid' && $snapToken)
@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
    document.getElementById('pay-button').addEventListener('click', function () {
        snap.pay('{{ $snapToken }}', {
            onSuccess: function(result) {
                window.location.href = '{{ route("payment.finish", $order) }}';
            },
            onPending: function(result) {
                window.location.href = '{{ route("payment.finish", $order) }}';
            },
            onError: function(result) {
                alert('Pembayaran gagal. Silakan coba lagi.');
            },
            onClose: function() {
                // window.location.reload();
            }
        });
    });
</script>
@endpush
@endif
@endsection
