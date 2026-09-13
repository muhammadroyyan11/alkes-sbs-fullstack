@extends('layouts.admin')

@section('title', 'Stok')
@section('page-title', 'Stok')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Stok</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Produk</th>
                        <th>Variant</th>
                        <th>Gudang</th>
                        <th>Jumlah</th>
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
    ajax: '{{ route("admin.stocks.datatable") }}',
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'product_name' },
        { data: 'variant_name' },
        { data: 'warehouse_name' },
        { data: 'quantity_badge' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
