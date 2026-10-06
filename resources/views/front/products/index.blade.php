@extends('layouts.front')

@section('title', ($search ? 'Hasil pencarian “'.$search.'”' : 'Katalog Produk').' — ALKES SBS')

@section('content')
<header class="catalog-hero"><div class="container"><span class="eyebrow">Katalog ALKES SBS</span><h1>Temukan Alat Kesehatan yang Tepat</h1><p>Cari berdasarkan nama, fungsi, atau SKU produk.</p></div></header>

<main class="catalog-section">
    <div class="container">
        <form class="catalog-toolbar" method="GET" action="{{ route('products.index') }}">
            <div class="catalog-search"><i class="bi bi-search"></i><input name="q" value="{{ $search }}" placeholder="Cari produk atau SKU..."></div>
            <select name="kategori" aria-label="Filter kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected($kategori === $category->slug)>{{ $category->name }}</option>@endforeach</select>
            <select name="stock" aria-label="Filter stok"><option value="">Semua stok</option><option value="available" @selected($stock === 'available')>Tersedia</option><option value="low" @selected($stock === 'low')>Stok terbatas</option><option value="empty" @selected($stock === 'empty')>Stok habis</option></select>
            <select name="sort" aria-label="Urutkan"><option value="latest" @selected($sort === 'latest')>Terbaru</option><option value="price_low" @selected($sort === 'price_low')>Harga terendah</option><option value="price_high" @selected($sort === 'price_high')>Harga tertinggi</option><option value="name" @selected($sort === 'name')>Nama A–Z</option></select>
            <button type="submit">Terapkan</button>
        </form>

        <div class="catalog-result"><div><strong>{{ $products->total() }}</strong> produk ditemukan @if($search)untuk “{{ $search }}”@endif</div>@if($search || $stock || $kategori)<a href="{{ route('products.index') }}">Reset filter</a>@endif</div>

        @if($products->count())
            <div class="product-grid">@foreach($products as $product) @include('front.partials.product-card', ['product' => $product]) @endforeach</div>
            <div class="catalog-pagination">{{ $products->links('pagination::bootstrap-5') }}</div>
        @else
            <div class="empty-products"><i class="bi bi-search"></i><h3>Produk tidak ditemukan</h3><p>Coba gunakan kata kunci yang lebih umum atau reset filter pencarian.</p><a href="{{ route('products.index') }}" class="btn btn-danger">Lihat Semua Produk</a></div>
        @endif
    </div>
</main>
@endsection
