@extends('layouts.admin')

@section('title', 'Buat Purchase Order')
@section('page-title', 'Buat Purchase Order')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Buat Purchase Order</h5>
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
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

        <form method="POST" action="{{ route('admin.purchase-orders.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Supplier *</label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}{{ $supplier->contact_person ? ' (' . $supplier->contact_person . ')' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan untuk PO ini...">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Buat PO
            </button>
        </form>
    </div>
</div>
@endsection
