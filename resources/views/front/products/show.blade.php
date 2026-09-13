@extends('layouts.front')

@section('title', $product->name.' — ALKES SBS')
@section('meta-description', $product->description)

@section('content')
<main class="product-detail-section">
    <div class="container">
        <nav class="product-breadcrumb"><a href="{{ route('home') }}">Home</a><i class="bi bi-chevron-right"></i><a href="{{ route('products.index') }}">Produk</a><i class="bi bi-chevron-right"></i><span>{{ $product->name }}</span></nav>
        <div class="product-detail-card">
            <div class="product-detail-image"><img src="{{ $product->image_url }}" alt="{{ $product->name }}"><span class="detail-stock {{ $product->stock > 0 ? 'available' : 'empty' }}">{{ $product->stock > 0 ? 'Stok tersedia' : 'Stok habis' }}</span></div>
            <div class="product-detail-info">
                <div class="product-sku">SKU {{ $product->sku }}</div>
                <h1>{{ $product->name }}</h1>
                <div class="detail-rating"><span>★ ★ ★ ★ ★</span><small>Produk terpercaya ALKES SBS</small></div>
                <div class="detail-price">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                <p class="detail-description">{{ $product->description ?: 'Produk alat kesehatan berkualitas untuk mendukung kebutuhan kesehatan Anda.' }}</p>
                <form method="POST" action="{{ route('cart.store', $product) }}">@csrf
                    @if($product->variants->count())
                        <div class="variant-picker"><label>Pilih Varian</label><div class="variant-options">@foreach($product->variants as $variant)<button type="button" data-price="{{ $variant->price }}" data-variant-id="{{ $variant->id }}">{{ $variant->name }} <small>({{ $variant->stock }} tersedia)</small></button>@endforeach</div></div>
                    @endif
                    <input type="hidden" name="variant_id">
                    <div class="purchase-row"><div class="quantity-control"><button type="button" data-action="minus">−</button><input name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}" readonly><button type="button" data-action="plus">+</button></div><button class="add-cart-btn" type="submit" @disabled($product->stock <= 0)><i class="bi bi-cart-plus"></i> Tambah ke Keranjang</button></div>
                </form>
                <div class="shopping-benefits"><span><i class="bi bi-shield-check"></i> Produk terpercaya</span><span><i class="bi bi-box-seam"></i> Packing aman</span><span><i class="bi bi-headset"></i> Bisa konsultasi</span></div>
            </div>
        </div>

        <section class="related-section"><div class="section-heading-row"><div><span class="eyebrow">Produk Lainnya</span><h2>Mungkin Anda Suka</h2></div></div><div class="product-grid">@foreach($relatedProducts as $related) @include('front.partials.product-card', ['product' => $related]) @endforeach</div></section>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.variant-options button').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.variant-options button').forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    document.querySelector('.detail-price').textContent = 'Rp ' + Number(button.dataset.price).toLocaleString('id-ID');
    document.querySelector('input[name="variant_id"]').value = button.dataset.variantId;
}));
document.querySelectorAll('.quantity-control button').forEach(button => button.addEventListener('click', () => {
    const input = button.parentElement.querySelector('input');
    const next = Number(input.value) + (button.dataset.action === 'plus' ? 1 : -1);
    input.value = Math.max(1, Math.min(Number(input.max), next));
}));
</script>
@endpush
