@extends('layouts.admin')

@section('title', 'Purchase Order')
@section('page-title', 'Purchase Order')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Purchase Order</h5>
        <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat PO
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Total</th>
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
    ajax: '{{ route("admin.purchase-orders.datatable") }}',
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'po_number' },
        { data: 'supplier_name' },
        { data: 'date_formatted' },
        { data: 'status_badge' },
        { data: 'total_fmt' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
