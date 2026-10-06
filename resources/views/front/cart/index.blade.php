@extends('layouts.front')
@section('title', 'Keranjang Belanja — ALKES SBS')
@section('content')
<main class="cart-page"><div class="container"><div class="page-heading"><span class="eyebrow">Keranjang Belanja</span><h1>Produk Pilihan Anda</h1><p>Periksa produk sebelum checkout.</p></div>
@foreach(['success','error'] as $type) @if(session($type))<div class="store-alert {{ $type }}">{{ session($type) }}</div>@endif @endforeach
@if(count($cart)) @php($subtotal=collect($cart)->sum(fn($i)=>$i['price']*$i['quantity']))
<div class="cart-layout"><form method="POST" action="{{ route('cart.update') }}" class="cart-items-card">@csrf @method('PATCH')
@foreach($cart as $key=>$item)<div class="cart-item"><img src="{{ $item['image'] }}" alt="{{ $item['name'] }}"><div class="cart-item-info"><span>{{ $item['sku'] }}</span><h3>{{ $item['name'] }}</h3>@if($item['variant_name'])<p>{{ $item['variant_name'] }}</p>@endif<strong>Rp {{ number_format($item['price'],0,',','.') }}</strong></div><div class="cart-quantity"><label>Jumlah</label><input type="number" name="quantities[{{ $key }}]" value="{{ $item['quantity'] }}" min="1" max="{{ $item['stock'] }}"></div><div class="cart-item-total">Rp {{ number_format($item['price']*$item['quantity'],0,',','.') }}</div><button form="remove-{{ md5($key) }}" class="cart-remove"><i class="bi bi-trash3"></i></button></div>@endforeach
<div class="cart-actions"><a href="{{ route('products.index') }}">← Lanjut Belanja</a><button>Perbarui Keranjang</button></div></form>
@foreach($cart as $key=>$item)<form id="remove-{{ md5($key) }}" method="POST" action="{{ route('cart.destroy',$key) }}" class="d-none">@csrf @method('DELETE')</form>@endforeach
<aside class="cart-summary"><h3>Ringkasan Belanja</h3><div><span>Subtotal</span><strong>Rp {{ number_format($subtotal,0,',','.') }}</strong></div><div><span>Pengiriman</span><small>Dihitung saat checkout</small></div><div><span>{{ $adminFeeLabel }}</span><strong>Rp {{ number_format($adminFee,0,',','.') }}</strong></div><hr><div class="summary-total"><span>Total sementara</span><strong>Rp {{ number_format($subtotal + $adminFee,0,',','.') }}</strong></div><a href="{{ route('checkout.index') }}" class="checkout-button">Lanjut Checkout →</a>@guest<p class="login-hint"><i class="bi bi-lock"></i> Login diperlukan saat checkout.</p>@endguest</aside></div>
@else <div class="empty-cart"><i class="bi bi-cart-x"></i><h2>Keranjang masih kosong</h2><p>Temukan alat kesehatan yang Anda butuhkan.</p><a href="{{ route('products.index') }}">Mulai Belanja</a></div>@endif
</div></main>
@endsection
