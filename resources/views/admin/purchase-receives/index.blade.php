@extends('layouts.admin')

@section('title', 'Purchase Receive')
@section('page-title', 'Purchase Receive')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Purchase Receive</h5>
        <a href="{{ route('admin.purchase-receives.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Receive
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Receive</th>
                        <th>No. PO</th>
                        <th>Tanggal</th>
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
    ajax: '{{ route("admin.purchase-receives.datatable") }}',
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'receive_number' },
        { data: 'po_number' },
        { data: 'date_formatted' },
        { data: 'status_badge' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
