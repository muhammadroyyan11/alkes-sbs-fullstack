@extends('layouts.admin')

@section('title', 'Log Stok')
@section('page-title', 'Log Stok')

@section('content')
<div class="card">
    <div class="card-header">
        <h5><i class="fa-solid fa-arrow-right-arrow-left"></i> Log Keluar Masuk Barang</h5>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.stock-logs.index') }}" class="btn btn-sm {{ $filters['type'] || $filters['status'] ? 'btn-secondary' : 'btn-primary' }}">
                Semua
            </a>
            <a href="{{ route('admin.stock-logs.index', ['status' => 'waiting_for_checkout']) }}"
               class="btn btn-sm {{ $filters['status'] === 'waiting_for_checkout' ? 'btn-primary' : 'btn-outline-warning' }}">
                Waiting for Checkout
            </a>
            <a href="{{ route('admin.stock-logs.index', ['status' => 'completed']) }}"
               class="btn btn-sm {{ $filters['status'] === 'completed' ? 'btn-primary' : 'btn-outline-success' }}">
                Completed
            </a>
            <a href="{{ route('admin.stock-logs.index', ['status' => 'cancelled']) }}"
               class="btn btn-sm {{ $filters['status'] === 'cancelled' ? 'btn-primary' : 'btn-outline-danger' }}">
                Cancel Transaction
            </a>
            <a href="{{ route('admin.stock-logs.index', ['type' => 'out']) }}"
               class="btn btn-sm {{ $filters['type'] === 'out' ? 'btn-primary' : 'btn-outline-danger' }}">
                OUT
            </a>
            <a href="{{ route('admin.stock-logs.index', ['type' => 'in']) }}"
               class="btn btn-sm {{ $filters['type'] === 'in' ? 'btn-primary' : 'btn-outline-success' }}">
                IN
            </a>
            <a href="{{ route('admin.stocks.index') }}" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-cubes"></i> Stok
            </a>
        </div>
    </div>
    <div class="card-body">
        <p style="font-size:.85rem;color:#666;margin-top:0;">
            Setiap pergerakan stok tercatat di sini.
            <span class="badge badge-warning">Waiting for Checkout</span> = stok ditahan pesanan yang belum bayar,
            <span class="badge badge-success">Completed</span> = pesanan lunas,
            <span class="badge badge-danger">Cancel Transaction</span> = stok dikembalikan (IN).
            Pesanan belum bayar otomatis dibatalkan setelah {{ \App\Services\OrderExpiry::MINUTES }} menit.
        </p>

        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Jumlah</th>
                        <th>Referensi</th>
                        <th>Catatan</th>
                        <th>User</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var logFilters = @json($filters);

$('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ route("admin.stock-logs.datatable") }}',
        data: function (d) {
            d.type = logFilters.type;
            d.status = logFilters.status;
        }
    },
    order: [[1, 'desc']],
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'date_formatted' },
        { data: 'product' },
        { data: 'type_badge' },
        { data: 'status_badge' },
        { data: 'quantity' },
        { data: 'reference' },
        { data: 'note' },
        { data: 'user' }
    ],
    language: dtLang
});
</script>
@endpush
