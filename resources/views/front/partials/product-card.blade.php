<article class="store-product-card">
    <a class="product-image-wrap" href="{{ route('products.show', ['product' => $product->sku]) }}">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        @if($product->stock <= 0)
            <span class="product-badge sold">Stok Habis</span>
        @elseif($product->stock <= 10)
            <span class="product-badge low">Stok Terbatas</span>
        @else
            <span class="product-badge ready">Siap Kirim</span>
        @endif
    </a>
    <div class="store-product-body">
        <div class="product-sku">{{ $product->sku }}</div>
        <a class="product-name" href="{{ route('products.show', ['product' => $product->sku]) }}">{{ $product->name }}</a>
        <p>{{ \Illuminate\Support\Str::limit($product->description ?: 'Produk alat kesehatan berkualitas untuk kebutuhan Anda.', 78) }}</p>
        <div class="product-meta"><span><i class="bi bi-box-seam"></i> {{ $product->stock }} {{ $product->unit }}</span>@if(($product->variants_count ?? 0) > 0)<span>{{ $product->variants_count }} varian</span>@endif</div>
        <div class="product-card-footer">
            <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
            <a href="{{ route('products.show', ['product' => $product->sku]) }}" class="product-action" aria-label="Lihat {{ $product->name }}"><i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</article>
