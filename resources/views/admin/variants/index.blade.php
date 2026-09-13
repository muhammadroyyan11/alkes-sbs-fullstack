@extends('layouts.admin')

@section('title', 'Variant')
@section('page-title', 'Variant')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Variant</h5>
        <a href="{{ route('admin.variants.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Variant
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>Nama</th>
                        <th>SKU</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("admin.variants.datatable") }}',
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'product_name' },
        { data: 'name' },
        { data: 'sku' },
        { data: 'price_fmt' },
        { data: 'stock_badge' },
        { data: 'status_badge' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
