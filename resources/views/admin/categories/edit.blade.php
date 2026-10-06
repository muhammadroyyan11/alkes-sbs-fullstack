@extends('layouts.admin')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Edit Kategori</h5>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Nama Kategori *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Slug (opsional — kosongkan untuk otomatis)</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $category->slug) }}" placeholder="mis. alat-diagnosa">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active', $category->is_active ? '1' : '0') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active', $category->is_active ? '1' : '0') == '0')>Nonaktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan</button>
        </form>
    </div>
</div>
@endsection
