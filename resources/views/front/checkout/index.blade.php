@extends('layouts.front')

@section('title', 'Checkout — ALKES SBS')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment.css') }}">
<style>
    .shipping-service { display: flex; gap: .6rem; align-items: center; padding: .35rem 0; font-size: .92rem; }
    .shipping-service span { flex: 1; }
    .shipping-service small { color: #6b7280; }
    .shipping-note { margin: .35rem 0 0; font-size: .78rem; color: #6b7280; }
</style>
@endpush

@section('content')
@php($subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']))

<main class="checkout-page">
    <div class="container">
        <div class="page-heading">
            <span class="eyebrow">Checkout Aman</span>
            <h1>Selesaikan Pesanan</h1>
            <p>Pilih alamat, pengiriman, dan metode pembayaran.</p>
        </div>

        @if($errors->any())
            <div class="store-alert error">{{ $errors->first() }}</div>
        @endif

        <div class="checkout-layout">
            <section class="checkout-form-card">
                <form id="checkout-form">
                    <h3><span>1</span> Alamat Pengiriman</h3>
                    @forelse($addresses as $a)
                        <label class="address-choice">
                            <input type="radio" name="address_id" value="{{ $a->id }}" @checked($a->is_primary) required>
                            <div>
                                <strong>{{ $a->label }} · {{ $a->recipient_name }}</strong>
                                <p>{{ $a->phone }} · {{ $a->address }}, {{ $a->city }}, {{ $a->province }} {{ $a->postal_code }}</p>
                            </div>
                        </label>
                    @empty
                        <div class="shipping-placeholder">
                            <div>
                                <strong>Belum ada alamat.</strong>
                                <p>Tambahkan alamat di halaman akun.</p>
                                <a href="{{ route('account.index') }}">Atur alamat</a>
                            </div>
                        </div>
                    @endforelse

                    <h3><span>2</span> Pengiriman</h3>
                    <label class="address-choice">
                        <input type="radio" name="shipping_method" value="regular" checked>
                        <div style="width:100%">
                            <strong>Reguler</strong>
                            <div id="regular-options">
                                <label class="shipping-service">
                                    <input type="radio" name="shipping_option" value="{{ \App\Services\ShippingQuoteService::CODE_FALLBACK }}" checked data-cost="{{ $regularCost }}">
                                    <span>Reguler · Rp {{ number_format($regularCost, 0, ',', '.') }}</span>
                                    <small>Estimasi 2–5 hari kerja (RajaOngkir)</small>
                                </label>
                            </div>
                            <p class="shipping-note" id="shipping-note">Tarif dikirim sesuai kota tujuan.</p>
                        </div>
                    </label>
                    <label class="address-choice">
                        <input type="radio" name="shipping_method" value="instant">
                        <div>
                            <strong>Instant · GoSend · Rp {{ number_format($instantCost, 0, ',', '.') }}</strong>
                            <p>Estimasi 1–2 jam</p>
                        </div>
                    </label>

                    <h3><span>3</span> Metode Pembayaran</h3>

                    {{-- Virtual Account --}}
                    <div class="payment-group">
                        <div class="payment-group-title"><i class="bi bi-bank"></i> Virtual Account</div>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bca_va" checked>
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/bca_va.svg') }}" alt="BCA"></span>
                                    <div><strong>BCA Virtual Account</strong><small>Transfer via ATM, m-banking, e-banking, QRIS</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bri_va">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/bri_va.svg') }}" alt="BRI"></span>
                                    <div><strong>BRI Virtual Account</strong><small>Transfer via ATM, m-banking, e-banking, QRIS</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="mandiri_va">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/mandiri_va.svg') }}" alt="Mandiri"></span>
                                    <div><strong>Mandiri Virtual Account</strong><small>Transfer via ATM, m-banking, e-banking, QRIS</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bni_va">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/bni_va.svg') }}" alt="BNI"></span>
                                    <div><strong>BNI Virtual Account</strong><small>Transfer via ATM, m-banking, e-banking, QRIS</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="permata_va">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/permata_va.svg') }}" alt="Permata"></span>
                                    <div><strong>Permata Virtual Account</strong><small>Transfer via ATM, m-banking, e-banking, QRIS</small></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- QRIS --}}
                    <div class="payment-group">
                        <div class="payment-group-title"><i class="bi bi-qr-code"></i> QRIS <span class="payment-badge">Populer</span></div>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="qris">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/qris.svg') }}" alt="QRIS"></span>
                                    <div><strong>QRIS</strong><small>Scan dengan semua aplikasi — GoPay, OVO, DANA, ShopeePay, m-banking</small></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- E-Wallet --}}
                    <div class="payment-group">
                        <div class="payment-group-title"><i class="bi bi-wallet2"></i> E-Wallet</div>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="gopay">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/gopay.svg') }}" alt="GoPay"></span>
                                    <div><strong>GoPay</strong><small>Bayar langsung dari aplikasi Gojek</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="shopeepay">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/shopeepay.svg') }}" alt="ShopeePay"></span>
                                    <div><strong>ShopeePay</strong><small>Bayar langsung dari aplikasi Shopee</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="dana">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/dana.svg') }}" alt="DANA"></span>
                                    <div><strong>DANA</strong><small>Bayar langsung dari aplikasi DANA</small></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Minimarket --}}
                    <div class="payment-group">
                        <div class="payment-group-title"><i class="bi bi-shop"></i> Minimarket</div>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="alfamart">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/alfamart.svg') }}" alt="Alfamart"></span>
                                    <div><strong>Alfamart</strong><small>Bayar tunai di kasir Alfamart terdekat</small></div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="indomaret">
                                <div class="payment-option-body">
                                    <span class="payment-logo"><img src="{{ asset('img/payments/indomaret.svg') }}" alt="Indomaret"></span>
                                    <div><strong>Indomaret</strong><small>Bayar tunai di kasir Indomaret terdekat</small></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Kartu Kredit --}}
                    <div class="payment-group">
                        <div class="payment-group-title"><i class="bi bi-credit-card-2-front"></i> Kartu Kredit / Debit</div>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="credit_card">
                                <div class="payment-option-body">
                                    <span class="payment-logo multi"><img src="{{ asset('img/payments/visa.svg') }}" alt="Visa"><img src="{{ asset('img/payments/mastercard.svg') }}" alt="Mastercard"></span>
                                    <div><strong>Kartu Kredit / Debit</strong><small>Visa, Mastercard, JCB — aman &amp; terenkripsi</small></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <h3><span>4</span> Catatan</h3>
                    <textarea name="notes" class="checkout-select" placeholder="Catatan pesanan (opsional)"></textarea>
                </form>
            </section>

            <aside class="cart-summary">
                <h3>Ringkasan</h3>
                @foreach($cart as $i)
                    <div>
                        <span>{{ $i['name'] }} × {{ $i['quantity'] }}</span>
                        <strong>Rp {{ number_format($i['price'] * $i['quantity'], 0, ',', '.') }}</strong>
                    </div>
                @endforeach
                <hr>
                <div><span>Subtotal</span><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                <div><span>Pengiriman</span><strong id="shipping-cost">Rp {{ number_format($regularCost, 0, ',', '.') }}</strong></div>
                <div><span>{{ $adminFeeLabel }}</span><strong id="admin-fee">Rp {{ number_format($adminFee, 0, ',', '.') }}</strong></div>
                <hr>
                <div class="summary-total">
                    <span>Total</span>
                    <strong id="total-price">Rp {{ number_format($subtotal + $regularCost + $adminFee, 0, ',', '.') }}</strong>
                </div>
                <button type="button" id="checkout-btn" class="checkout-btn">
                    <span id="btn-text">Buat Pesanan & Bayar</span>
                    <span id="btn-loader" class="d-none">
                        <span class="spinner-border spinner-border-sm"></span> Memproses...
                    </span>
                </button>
            </aside>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('checkout-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoader = document.getElementById('btn-loader');
    const shippingCostEl = document.getElementById('shipping-cost');
    const totalPriceEl = document.getElementById('total-price');
    const subtotal = {{ $subtotal }};
    const adminFee = {{ $adminFee }};
    const instantCost = {{ $instantCost }};
    const quoteUrl = @json(route('shipping.quote'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let regularOptions = [{
        code: @json(\App\Services\ShippingQuoteService::CODE_FALLBACK),
        cost: {{ $regularCost }},
        label: 'Reguler',
        etd: '2-5 hari kerja',
        fallback: true
    }];

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }

    function selectedOption() {
        const method = document.querySelector('input[name="shipping_method"]:checked');
        if (method && method.value === 'instant') {
            return { cost: instantCost };
        }
        const option = document.querySelector('input[name="shipping_option"]:checked');
        return { cost: option ? Number(option.dataset.cost) : regularOptions[0].cost };
    }

    function updateSummary() {
        const cost = selectedOption().cost || 0;
        shippingCostEl.textContent = 'Rp ' + cost.toLocaleString('id-ID');
        totalPriceEl.textContent = 'Rp ' + (subtotal + cost + adminFee).toLocaleString('id-ID');
    }

    function renderRegularOptions(options) {
        regularOptions = options.filter(function (option) { return option.code !== 'instant'; });
        if (!regularOptions.length) return;

        const wrap = document.getElementById('regular-options');
        wrap.innerHTML = regularOptions.map(function (option, index) {
            return '<label class="shipping-service">' +
                '<input type="radio" name="shipping_option" value="' + escapeHtml(option.code) + '"' +
                (index === 0 ? ' checked' : '') +
                ' data-cost="' + Number(option.cost) + '">' +
                '<span>' + escapeHtml(option.label) + ' · Rp ' + Number(option.cost).toLocaleString('id-ID') + '</span>' +
                '<small>' + (option.etd ? 'Estimasi ' + escapeHtml(option.etd) : '') + '</small>' +
                '</label>';
        }).join('');

        wrap.querySelectorAll('input[name="shipping_option"]').forEach(function (el) {
            el.addEventListener('change', updateSummary);
        });

        const note = document.getElementById('shipping-note');
        const isFallback = regularOptions.every(function (option) { return option.fallback; });
        note.textContent = isFallback
            ? 'Tarif estimasi dari pengaturan toko.'
            : 'Tarif aktual sesuai kota tujuan.';

        updateSummary();
    }

    async function loadQuote() {
        const address = document.querySelector('input[name="address_id"]:checked');
        if (!address) return;

        try {
            const response = await fetch(quoteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ address_id: Number(address.value) })
            });
            if (!response.ok) return;
            const data = await response.json();
            renderRegularOptions(data.options || []);
        } catch (e) {
            // Ongkir fallback tetap dipakai bila layanan tidak terjangkau.
        }
    }

    document.querySelectorAll('input[name="shipping_method"]').forEach(i => {
        i.addEventListener('change', updateSummary);
    });
    document.querySelectorAll('input[name="address_id"]').forEach(el => {
        el.addEventListener('change', loadQuote);
    });
    loadQuote();

    function showLoader(show) {
        btn.disabled = show;
        btnText.classList.toggle('d-none', show);
        btnLoader.classList.toggle('d-none', !show);
    }

    btn.addEventListener('click', function() {
        if (!document.querySelector('input[name="address_id"]:checked')) {
            alert('Silakan pilih alamat pengiriman.');
            return;
        }

        showLoader(true);

        const formData = new FormData(document.getElementById('checkout-form'));

        fetch('{{ route("checkout.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(r => r.ok ? r.json() : r.json().then(d => { throw d; }))
        .then(data => {
            if (!data.success || !data.snap_token) {
                alert(data.message || 'Gagal membuat pesanan.');
                showLoader(false);
                return;
            }

            snap.pay(data.snap_token, {
                onSuccess: function() {
                    window.location.href = '{{ url("/pembayaran") }}/' + data.order_number + '/selesai';
                },
                onPending: function() {
                    window.location.href = '{{ url("/pembayaran") }}/' + data.order_number + '/selesai';
                },
                onError: function() {
                    alert('Pembayaran gagal. Silakan coba lagi.');
                    showLoader(false);
                },
                onClose: function() {
                    showLoader(false);
                }
            });
        })
        .catch(err => {
            console.error(err);
            if (err && err.errors) {
                const e = Object.values(err.errors)[0];
                alert(Array.isArray(e) ? e[0] : e);
            } else {
                alert('Terjadi kesalahan. Coba lagi.');
            }
            showLoader(false);
        });
    });
});
</script>
@endpush
