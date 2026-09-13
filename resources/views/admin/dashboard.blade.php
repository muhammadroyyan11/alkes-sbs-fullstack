@extends('adminlte::page')

@section('title', 'Dashboard - ALKES SBS')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total_products'] }}</h3>
                    <p>Total Produk</p>
                </div>
                <div class="icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <a href="{{ route('admin.products') }}" class="small-box-footer">More info <i class="bi bi-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['total_variants'] }}</h3>
                    <p>Total Variant</p>
                </div>
                <div class="icon">
                    <i class="bi bi-tags"></i>
                </div>
                <a href="{{ route('admin.variants') }}" class="small-box-footer">More info <i class="bi bi-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['low_stock'] }}</h3>
                    <p>Stok Menipis</p>
                </div>
                <div class="icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <a href="{{ route('admin.stocks') }}" class="small-box-footer">More info <i class="bi bi-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['pending_orders'] }}</h3>
                    <p>Order Pending</p>
                </div>
                <div class="icon">
                    <i class="bi bi-cart"></i>
                </div>
                <a href="{{ route('admin.purchase-orders') }}" class="small-box-footer">More info <i class="bi bi-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Order Terbaru</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_orders as $order)
                                <tr>
                                    <td>{{ $order->code }}</td>
                                    <td>{{ $order->user->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                    <td>
                                        @if($order->status == 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($order->status == 'paid')
                                            <span class="badge bg-info">Paid</span>
                                        @elseif($order->status == 'shipped')
                                            <span class="badge bg-primary">Shipped</span>
                                        @elseif($order->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada order</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistik</h3>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <canvas id="chart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box {
            border-radius: 0.5rem;
        }
        .small-box .icon {
            color: rgba(0,0,0,.15);
        }
    </style>
@stop

@section('js')
    <script>
        new Chart(document.getElementById('chart'), {
            type: 'doughnut',
            data: {
                labels: ['Produk', 'Variant', 'Stok Menipis', 'Order'],
                datasets: [{
                    data: [{{ $stats['total_products'] }}, {{ $stats['total_variants'] }}, {{ $stats['low_stock'] }}, {{ $stats['total_orders'] }}],
                    backgroundColor: ['#0dcaf0', '#198754', '#ffc107', '#dc3545'],
                }]
            }
        });
    </script>
@stop
