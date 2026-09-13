@extends('layouts.admin')

@section('title', 'Buat Stok Opname')
@section('page-title', 'Buat Stok Opname')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Buat Stok Opname</h5>
        <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary">
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

        <form method="POST" action="{{ route('admin.stock-opnames.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan stok opname (opsional)">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Buat Stok Opname
            </button>
        </form>
    </div>
</div>
@endsection
