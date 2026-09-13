@extends('layouts.admin')

@section('title', 'Stok Opname')
@section('page-title', 'Stok Opname')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Stok Opname</h5>
        <a href="{{ route('admin.stock-opnames.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Stok Opname
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Catatan</th>
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
    ajax: '{{ route("admin.stock-opnames.datatable") }}',
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'date_formatted' },
        { data: 'user_name' },
        { data: 'status_badge' },
        { data: 'notes' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
