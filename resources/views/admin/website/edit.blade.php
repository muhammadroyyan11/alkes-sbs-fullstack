@extends('layouts.admin')

@section('title', 'Pengaturan Website')
@section('page-title', 'Pengaturan Website')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Pengaturan Website</h5>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.website.update') }}">
            @csrf
            @method('PUT')

            <h6 class="mb-3" style="font-weight:600;">Identitas Website</h6>
            <div class="form-group">
                <label class="form-label">Nama Website *</label>
                <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings['site_name']) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="site_description" class="form-control" rows="2">{{ old('site_description', $settings['site_description']) }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="site_email" class="form-control" value="{{ old('site_email', $settings['site_email']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="site_phone" class="form-control" value="{{ old('site_phone', $settings['site_phone']) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="site_address" class="form-control" rows="2">{{ old('site_address', $settings['site_address']) }}</textarea>
            </div>

            <hr>
            <h6 class="mb-3" style="font-weight:600;">Banner Beranda</h6>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="form-label">URL / Path Gambar Banner</label>
                    <input type="text" name="site_banner" class="form-control" value="{{ old('site_banner', $settings['site_banner']) }}" placeholder="/img/banner.jpg">
                </div>
                <div class="form-group col-md-6">
                    <label class="form-label">Judul Banner</label>
                    <input type="text" name="site_banner_title" class="form-control" value="{{ old('site_banner_title', $settings['site_banner_title']) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Subjudul Banner</label>
                <input type="text" name="site_banner_subtitle" class="form-control" value="{{ old('site_banner_subtitle', $settings['site_banner_subtitle']) }}">
            </div>

            <hr>
            <h6 class="mb-3" style="font-weight:600;">Kontak & Media Sosial</h6>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" name="wa_number" class="form-control" value="{{ old('wa_number', $settings['wa_number']) }}" placeholder="6281234567890">
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="url" name="instagram_url" class="form-control" value="{{ old('instagram_url', $settings['instagram_url']) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Facebook</label>
                    <input type="url" name="facebook_url" class="form-control" value="{{ old('facebook_url', $settings['facebook_url']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">YouTube</label>
                    <input type="url" name="youtube_url" class="form-control" value="{{ old('youtube_url', $settings['youtube_url']) }}">
                </div>
            </div>

            <hr>
            <h6 class="mb-3" style="font-weight:600;">Marketplace</h6>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tokopedia</label>
                    <input type="url" name="tokopedia_url" class="form-control" value="{{ old('tokopedia_url', $settings['tokopedia_url']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Shopee</label>
                    <input type="url" name="shopee_url" class="form-control" value="{{ old('shopee_url', $settings['shopee_url']) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Lazada</label>
                    <input type="url" name="lazada_url" class="form-control" value="{{ old('lazada_url', $settings['lazada_url']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Blibli</label>
                    <input type="url" name="blibli_url" class="form-control" value="{{ old('blibli_url', $settings['blibli_url']) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">TikTok Shop</label>
                <input type="url" name="tiktok_shop_url" class="form-control" value="{{ old('tiktok_shop_url', $settings['tiktok_shop_url']) }}">
            </div>

            <hr>
            <h6 class="mb-3" style="font-weight:600;">Pengiriman</h6>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kota Asal</label>
                    <input type="text" name="shipping_origin_city" class="form-control" value="{{ old('shipping_origin_city', $settings['shipping_origin_city']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Provinsi Asal</label>
                    <input type="text" name="shipping_origin_province" class="form-control" value="{{ old('shipping_origin_province', $settings['shipping_origin_province']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Kode Pos Asal</label>
                    <input type="text" name="shipping_origin_postal" class="form-control" value="{{ old('shipping_origin_postal', $settings['shipping_origin_postal']) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kode Kota Asal RajaOngkir</label>
                    <input type="text" name="shipping_origin_id" class="form-control" value="{{ old('shipping_origin_id', $settings['shipping_origin_id']) }}" placeholder="mis. 501 (kosongkan = resolve dari nama kota)">
                </div>
                <div class="form-group">
                    <label class="form-label">Kurir Reguler (dipisah koma)</label>
                    <input type="text" name="shipping_couriers" class="form-control" value="{{ old('shipping_couriers', $settings['shipping_couriers']) }}" placeholder="jne, sicepat, pos">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Ongkir Regular (Rp) *</label>
                    <input type="number" name="shipping_regular_cost" class="form-control" min="0" value="{{ old('shipping_regular_cost', $settings['shipping_regular_cost']) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ongkir Instan (Rp) *</label>
                    <input type="number" name="shipping_instant_cost" class="form-control" min="0" value="{{ old('shipping_instant_cost', $settings['shipping_instant_cost']) }}" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Pengiriman Instan</label>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:400;">
                        <input type="hidden" name="shipping_instant_enabled" value="0">
                        <input type="checkbox" name="shipping_instant_enabled" value="1" @checked(old('shipping_instant_enabled', $settings['shipping_instant_enabled']) == 1)>
                        Aktifkan pengiriman instan (GoSend)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Area Layanan Instan</label>
                    <input type="text" name="shipping_instant_areas" class="form-control" value="{{ old('shipping_instant_areas', $settings['shipping_instant_areas']) }}" placeholder="Kota Malang, Kabupaten Malang (kosongkan = semua area)">
                    <small class="text-muted">Dipisah koma. Hanya alamat tujuan di area ini yang melihat opsi instan.</small>
                </div>
            </div>

            <hr>
            <h6 class="mb-3" style="font-weight:600;">Biaya Transaksi</h6>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Biaya Admin per Transaksi (Rp) *</label>
                    <input type="number" name="admin_fee" class="form-control" min="0" value="{{ old('admin_fee', $settings['admin_fee']) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Label Biaya Admin</label>
                    <input type="text" name="admin_fee_label" class="form-control" value="{{ old('admin_fee_label', $settings['admin_fee_label']) }}">
                </div>
            </div>

            <hr>
            <h6 class="mb-3" style="font-weight:600;">Fitur Website</h6>
            <div class="form-row">
                @foreach([
                    'feature_reviews' => 'Ulasan Produk',
                    'feature_wishlist' => 'Wishlist',
                    'feature_live_chat' => 'Live Chat (Tawk)',
                    'feature_cod' => 'COD (Bayar di Tempat)',
                ] as $key => $label)
                <div class="form-group col-md-3">
                    <label class="form-label">{{ $label }}</label>
                    <select name="{{ $key }}" class="form-control">
                        <option value="1" @selected(old($key, $settings[$key]) == '1')>Aktif</option>
                        <option value="0" @selected(old($key, $settings[$key]) == '0')>Nonaktif</option>
                    </select>
                </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary mt-2">
                <i class="fa-solid fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>
@endsection
