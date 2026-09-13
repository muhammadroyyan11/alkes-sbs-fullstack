@extends('layouts.front')

@section('title', 'Akun Saya — ALKES SBS')

@section('content')
@php
    $statusItems = [
        'unpaid' => ['wallet2', 'Belum Dibayar'],
        'processing' => ['box-seam', 'Diproses'],
        'shipped' => ['truck', 'Dikirim'],
        'completed' => ['check2-circle', 'Selesai'],
    ];
@endphp

<main class="account-dashboard-page">
    <div class="container account-dashboard-container">
        <div class="account-page-heading">
            <div>
                <span class="eyebrow">Pusat Akun</span>
                <h1>Akun Saya</h1>
                <p>Kelola profil, alamat, dan pantau pesanan Anda.</p>
            </div>
            <a href="{{ route('orders.index') }}" class="account-heading-link">
                Riwayat Pesanan <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @if(session('success'))
            <div class="store-alert success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="store-alert error">{{ $errors->first() }}</div>
        @endif

        <section class="account-profile-card">
            <div class="account-avatar">{{ $initials ?: 'AS' }}</div>
            <div class="account-profile-copy">
                <span>Customer ALKES SBS</span>
                <h2>{{ $user->name }}</h2>
                <p><i class="bi bi-envelope"></i> {{ $user->email }}</p>
                <p><i class="bi bi-whatsapp"></i> {{ $user->phone ?: 'Nomor WhatsApp belum diisi' }}</p>
            </div>
            <a href="#edit-profile" class="account-outline-button">
                <i class="bi bi-pencil-square"></i> Edit Profil
            </a>
        </section>

        <section class="account-metrics">
            <a href="{{ route('orders.index') }}" class="account-metric-card">
                <span class="account-metric-icon"><i class="bi bi-receipt"></i></span>
                <strong>{{ $orderStatusCounts->sum() }}</strong>
                <small>Total Pesanan Aktif</small>
            </a>
            <a href="#saved-addresses" class="account-metric-card">
                <span class="account-metric-icon"><i class="bi bi-geo-alt"></i></span>
                <strong>{{ $addresses->count() }}</strong>
                <small>Alamat Tersimpan</small>
            </a>
            <a href="{{ route('cart.index') }}" class="account-metric-card">
                <span class="account-metric-icon"><i class="bi bi-bag"></i></span>
                <strong>{{ $cartQuantity }}</strong>
                <small>Item di Keranjang</small>
            </a>
        </section>

        <section class="account-panel order-status-panel">
            <div class="account-panel-heading">
                <div>
                    <span class="eyebrow">Pesanan Saya</span>
                    <h2>Status Pesanan</h2>
                </div>
                <a href="{{ route('orders.index') }}">Lihat Semua <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="order-status-grid">
                @foreach($statusItems as $key => [$icon, $label])
                    <a href="{{ route('orders.index', ['status' => $key]) }}" class="order-status-item">
                        <span class="order-status-icon">
                            <i class="bi bi-{{ $icon }}"></i>
                            @if($orderStatusCounts[$key] > 0)
                                <b>{{ $orderStatusCounts[$key] }}</b>
                            @endif
                        </span>
                        <strong>{{ $label }}</strong>
                        <small>{{ $orderStatusCounts[$key] }} pesanan</small>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="account-overview-grid">
            <section class="account-panel recent-orders-panel">
                <div class="account-panel-heading">
                    <div>
                        <span class="eyebrow">Aktivitas Terbaru</span>
                        <h2>Transaksi Terakhir</h2>
                    </div>
                    <a href="{{ route('orders.index') }}">Lihat Semua</a>
                </div>

                <div class="account-order-list">
                    @forelse($orders as $order)
                        <a href="{{ route('orders.show', $order) }}" class="account-order-item">
                            <span class="account-order-icon"><i class="bi bi-box2-heart"></i></span>
                            <div class="account-order-copy">
                                <div>
                                    <strong>{{ $order->order_number }}</strong>
                                    <span class="account-status-badge {{ $order->customerStatus() }}">{{ $order->customerStatusLabel() }}</span>
                                </div>
                                <p>{{ $order->items->first()?->product_name ?? 'Pesanan alat kesehatan' }}</p>
                                <small>{{ $order->created_at->format('d M Y, H:i') }} · {{ $order->items->sum('quantity') }} item</small>
                            </div>
                            <div class="account-order-total">
                                <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                    @empty
                        <div class="account-empty-state">
                            <i class="bi bi-bag-check"></i>
                            <h3>Belum ada transaksi</h3>
                            <p>Produk yang Anda beli akan tampil di bagian ini.</p>
                            <a href="{{ route('products.index') }}">Mulai Belanja</a>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="account-panel primary-address-panel">
                <div class="account-panel-heading">
                    <div>
                        <span class="eyebrow">Pengiriman</span>
                        <h2>Alamat Utama</h2>
                    </div>
                    <a href="#saved-addresses">Kelola</a>
                </div>

                @if($primaryAddress)
                    <div class="primary-address-icon"><i class="bi bi-house-heart"></i></div>
                    <span class="primary-address-label">{{ $primaryAddress->label }}</span>
                    <h3>{{ $primaryAddress->recipient_name }}</h3>
                    <p>{{ $primaryAddress->phone }}</p>
                    <p>{{ $primaryAddress->address }}, {{ $primaryAddress->city }}, {{ $primaryAddress->province }} {{ $primaryAddress->postal_code }}</p>
                @else
                    <div class="account-empty-state compact">
                        <i class="bi bi-geo-alt"></i>
                        <h3>Alamat belum tersedia</h3>
                        <a href="#add-address">Tambah Alamat</a>
                    </div>
                @endif
            </section>
        </div>

        <section class="account-settings-grid">
            <details class="account-form-panel" id="edit-profile">
                <summary><span><i class="bi bi-person-gear"></i> Edit Profil</span><i class="bi bi-chevron-down"></i></summary>
                <form method="POST" action="{{ route('account.update') }}">
                    @csrf
                    @method('PATCH')
                    <div class="checkout-fields">
                        <label>Nama<input name="name" value="{{ old('name', $user->name) }}" required></label>
                        <label>Email<input value="{{ $user->email }}" disabled></label>
                        <label>WhatsApp<input name="phone" value="{{ old('phone', $user->phone) }}"></label>
                        <label class="full">Alamat singkat<textarea name="address">{{ old('address', $user->address) }}</textarea></label>
                    </div>
                    <button class="checkout-button">Simpan Profil</button>
                </form>
            </details>

            <details class="account-form-panel" id="add-address">
                <summary><span><i class="bi bi-geo-alt"></i> Tambah Alamat</span><i class="bi bi-chevron-down"></i></summary>
                <form method="POST" action="{{ route('account.addresses.store') }}">
                    @csrf
                    <div class="checkout-fields">
                        <label>Label<input name="label" value="{{ old('label') }}" placeholder="Rumah" required></label>
                        <label>Penerima<input name="recipient_name" value="{{ old('recipient_name', $user->name) }}" required></label>
                        <label>WhatsApp<input name="phone" value="{{ old('phone', $user->phone) }}" required></label>
                        <label>Kota<input name="city" value="{{ old('city') }}" required></label>
                        <label>Provinsi<input name="province" value="{{ old('province') }}" required></label>
                        <label>Kode Pos<input name="postal_code" value="{{ old('postal_code') }}" required></label>
                        <label class="full">Alamat lengkap<textarea name="address" required>{{ old('address') }}</textarea></label>
                        <label class="account-checkbox"><input type="checkbox" name="is_primary" value="1"> Jadikan alamat utama</label>
                    </div>
                    <button class="checkout-button">Tambah Alamat</button>
                </form>
            </details>
        </section>

        <section class="account-panel saved-address-panel" id="saved-addresses">
            <div class="account-panel-heading">
                <div>
                    <span class="eyebrow">Buku Alamat</span>
                    <h2>Alamat Tersimpan</h2>
                </div>
                <a href="#add-address"><i class="bi bi-plus-lg"></i> Tambah</a>
            </div>

            <div class="saved-address-grid">
                @forelse($addresses as $address)
                    <article class="saved-address-card">
                        <div>
                            <span class="saved-address-icon"><i class="bi bi-geo-alt-fill"></i></span>
                            <div>
                                <strong>{{ $address->label }}</strong>
                                @if($address->is_primary)<small>Utama</small>@endif
                            </div>
                        </div>
                        <h3>{{ $address->recipient_name }}</h3>
                        <p>{{ $address->phone }}</p>
                        <p>{{ $address->address }}, {{ $address->city }}, {{ $address->province }} {{ $address->postal_code }}</p>
                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                            @csrf
                            @method('DELETE')
                            <button class="account-delete-button"><i class="bi bi-trash3"></i> Hapus</button>
                        </form>
                    </article>
                @empty
                    <div class="account-empty-state compact">
                        <i class="bi bi-house-add"></i>
                        <h3>Belum ada alamat tersimpan</h3>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>
@endsection
