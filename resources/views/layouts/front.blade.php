<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta-description', 'ALKES SBS menyediakan alat kesehatan berkualitas dan terpercaya.')">
    <title>@yield('title', 'ALKES SBS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/front.css') }}">
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm sticky-top storefront-navbar">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand" href="{{ route('home') }}">ALKES SBS</a>
        <form class="nav-search order-lg-2" action="{{ route('products.index') }}" method="GET" role="search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari alat kesehatan, merek, atau SKU..." aria-label="Cari produk">
            <button type="submit">Cari</button>
        </form>
        <button class="navbar-toggler order-lg-3" type="button" data-bs-toggle="collapse" data-bs-target="#frontNavbar" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse order-lg-4" id="frontNavbar">
            <ul class="navbar-nav ms-lg-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Produk</a></li>
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Masuk</a></li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('account.index') }}"><i class="bi bi-person-circle"></i> Akun</a></li>
                @endguest
                <li class="nav-item ms-lg-2">
                    <a href="{{ route('cart.index') }}" class="nav-cart" aria-label="Keranjang">
                        <i class="bi bi-bag"></i><span class="cart-text">Keranjang</span>
                        <span class="cart-count-badge">{{ collect(session('cart', []))->sum('quantity') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

@yield('content')

<footer class="front-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5"><h4>ALKES SBS</h4><p>Alat kesehatan berkualitas untuk rumah, klinik, dan tenaga medis.</p></div>
            <div class="col-6 col-lg-3"><h6>Navigasi</h6><a href="{{ route('home') }}">Home</a><a href="{{ route('products.index') }}">Produk</a></div>
            <div class="col-6 col-lg-4"><h6>Hubungi Kami</h6><p><i class="bi bi-geo-alt"></i> Kota Malang, Jawa Timur</p><p><i class="bi bi-whatsapp"></i> Konsultasi produk tersedia</p></div>
        </div>
        <div class="footer-bottom">© {{ date('Y') }} ALKES SBS. Semua hak dilindungi.</div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
