@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dc2626;">
            <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $totalProducts }}</h3>
            <p>Total Produk</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#28a745;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $totalUsers }}</h3>
            <p>Total Pengguna</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ffc107;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $lowStock }}</h3>
            <p>Stok Menipis</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#17a2b8;">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $pendingOrders }}</h3>
            <p>PO Pending</p>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-link"></i> Akses Cepat</h5>
        </div>
        <div class="card-body">
            <a href="{{ route('admin.products.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-box"></i> Produk
            </a>
            <a href="{{ route('admin.variants.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-layer-group"></i> Variant
            </a>
            <a href="{{ route('admin.stocks.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-cubes"></i> Stok
            </a>
            <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-clipboard-check"></i> Stok Opname
            </a>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-truck"></i> Supplier
            </a>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-file-invoice"></i> Purchase Order
            </a>
            <a href="{{ route('admin.purchase-receives.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-boxes-stacked"></i> Purchase Receive
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-primary" style="margin-right:8px;margin-bottom:8px;">
                <i class="fa-solid fa-user-gear"></i> Users
            </a>
            <a href="{{ route('admin.website.edit') }}" class="btn btn-secondary" style="margin-bottom:8px;">
                <i class="fa-solid fa-globe"></i> Website
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-circle-info"></i> Informasi Sistem</h5>
        </div>
        <div class="card-body">
            <table style="width:100%;font-size:.875rem;">
                <tr>
                    <td style="padding:8px 0;color:#888;">Nama Aplikasi</td>
                    <td style="padding:8px 0;font-weight:600;">ALKES SBS</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#888;">Versi</td>
                    <td style="padding:8px 0;font-weight:600;">1.0.0</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#888;">Server Time</td>
                    <td style="padding:8px 0;font-weight:600;">{{ now()->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#888;">Laravel Version</td>
                    <td style="padding:8px 0;font-weight:600;">{{ app()->version() }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#888;">PHP Version</td>
                    <td style="padding:8px 0;font-weight:600;">{{ phpversion() }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection
