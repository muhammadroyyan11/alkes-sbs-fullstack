@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Edit Produk</h5>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
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

        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Produk *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Harga *</label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', $product->price) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stok *</label>
                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Satuan *</label>
                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $product->unit) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" {{ old('is_active', $product->is_active) ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('is_active', $product->is_active) == 0 ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Tanpa kategori —</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Ganti Gambar</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    @if($product->image)
                    <div class="mt-2">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:70px;height:70px;object-fit:cover;border-radius:6px;">
                        <label class="ml-2">
                            <input type="checkbox" name="remove_image" value="1"> Hapus gambar
                        </label>
                    </div>
                    @endif
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Update
            </button>
        </form>
    </div>
</div>
@endsection
