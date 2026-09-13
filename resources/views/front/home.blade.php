@extends('layouts.front')

@section('title', 'Alat Kesehatan Berkualitas — ALKES SBS')

@section('content')
<section class="hero">
    <div class="container position-relative" style="z-index:2">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="location-badge"><i class="bi bi-geo-alt-fill"></i> KOTA MALANG</span>
                <h1>Alat Kesehatan<br><span>Berkualitas & Terpercaya</span></h1>
                <p>Menyediakan berbagai alat kesehatan berkualitas untuk kebutuhan rumah, klinik, dan tenaga medis di seluruh Indonesia.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('products.index') }}" class="btn btn-white">Belanja Sekarang</a>
                    <a href="#keunggulan" class="btn btn-outline-light">Mengapa Kami?</a>
                </div>
            </div>
            <div class="col-lg-6 text-center"><img src="{{ asset('gambar/Tokoh.jpg') }}" alt="Toko ALKES SBS" class="img-fluid"></div>
        </div>
    </div>
</section>

<section class="features" id="keunggulan">
    <div class="container text-center">
        <h2 class="section-title">Mengapa Pilih Kami?</h2>
        <p class="section-subtitle">Pelayanan terbaik untuk kebutuhan alat kesehatan Anda</p>
        <div class="row g-4">
            @foreach([
                ['heart-pulse','olive','Alat Kesehatan Lengkap','Pilihan produk untuk kebutuhan rumah, klinik, dan tenaga medis.'],
                ['shield-check','green','Produk Terpercaya','Produk terpilih dengan informasi harga dan stok yang transparan.'],
                ['headset','yellow','Konsultasi Gratis','Tim kami siap membantu memilih produk sesuai kebutuhan Anda.'],
                ['truck','orange','Pengiriman Aman','Packing aman dan dukungan pengiriman ke seluruh Indonesia.'],
            ] as [$icon,$color,$title,$description])
            <div class="col-lg-3 col-md-6"><div class="feature-card text-start"><div class="feature-icon {{ $color }}"><i class="bi bi-{{ $icon }}"></i></div><h5>{{ $title }}</h5><p>{{ $description }}</p></div></div>
            @endforeach
        </div>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <div class="section-heading-row"><div><span class="eyebrow">Pilihan Terpopuler</span><h2>Produk Rekomendasi</h2></div><a href="{{ route('products.index') }}">Lihat semua <i class="bi bi-arrow-right"></i></a></div>
        <div class="product-grid">@foreach($featuredProducts as $product) @include('front.partials.product-card', ['product' => $product]) @endforeach</div>
    </div>
</section>

<section class="cta-section"><div class="container"><div class="cta-box mx-auto"><h2>Butuh Bantuan Memilih Produk?</h2><p>Konsultasikan kebutuhan alat kesehatan Anda bersama tim ALKES SBS.</p><a href="{{ route('products.index') }}" class="btn">Jelajahi Produk</a></div></div></section>
@endsection
