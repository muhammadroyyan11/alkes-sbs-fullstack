@extends('layouts.admin')

@section('title', 'Supplier')
@section('page-title', 'Supplier')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Supplier</h5>
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Supplier
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Kontak Person</th>
                        <th>Telepon</th>
                        <th>Email</th>
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
    ajax: '{{ route("admin.suppliers.datatable") }}',
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'name' },
        { data: 'contact' },
        { data: 'phone' },
        { data: 'email' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
