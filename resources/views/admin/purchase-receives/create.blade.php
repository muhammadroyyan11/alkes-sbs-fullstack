@extends('layouts.admin')

@section('title', 'Buat Purchase Receive')
@section('page-title', 'Buat Purchase Receive')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Form Buat Purchase Receive</h5>
        <a href="{{ route('admin.purchase-receives.index') }}" class="btn btn-secondary">
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

        <form method="POST" action="{{ route('admin.purchase-receives.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Purchase Order *</label>
                <select name="purchase_order_id" class="form-control" required>
                    <option value="">-- Pilih PO --</option>
                    @foreach($purchaseOrders as $po)
                    <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                        {{ $po->po_number }} - {{ $po->supplier?->name ?? '-' }} ({{ ucfirst($po->status) }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan penerimaan...">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Buat Receive
            </button>
        </form>
    </div>
</div>
@endsection
