@extends('layouts.admin')

@section('title', 'Edit Variant')
@section('page-title', 'Edit Variant')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Edit Variant</h5>
        <a href="{{ route('admin.variants.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
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

        <form method="POST" action="{{ route('admin.variants.update', $variant) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Produk *</label>
                <select name="product_id" class="form-control" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id', $variant->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Variant *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $variant->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $variant->sku) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Harga *</label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', $variant->price) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stok *</label>
                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $variant->stock) }}" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" {{ old('is_active', $variant->is_active) ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ old('is_active', $variant->is_active) == 0 ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Update
            </button>
        </form>
    </div>
</div>
@endsection
