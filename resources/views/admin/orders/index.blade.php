@extends('layouts.admin')

@section('title', 'Pesanan')
@section('page-title', 'Pesanan')

@section('content')
{{-- ===== Ringkasan: apa yang harus diproses hari ini ===== --}}
<div class="stats-grid">
    <a href="{{ route('admin.orders.index', ['filter' => 'need_process']) }}"
       class="stat-card" style="text-decoration:none;color:inherit;{{ $filter === 'need_process' ? 'outline:2px solid #16a34a;' : '' }}">
        <div class="stat-icon" style="background:#16a34a;">
            <i class="fa-solid fa-box-open"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $summary['need_process'] }}</h3>
            <p>Siap Diproses Hari Ini <span style="color:#16a34a;">(lunas, belum dikirim)</span></p>
        </div>
    </a>

    <a href="{{ route('admin.orders.index', ['filter' => 'overdue']) }}"
       class="stat-card" style="text-decoration:none;color:inherit;{{ $filter === 'overdue' ? 'outline:2px solid #dc2626;' : '' }}">
        <div class="stat-icon" style="background:#dc2626;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $summary['overdue'] }}</h3>
            <p>Lewat 1 Hari <span style="color:#dc2626;">(wajib diselesaikan!)</span></p>
        </div>
    </a>

    <a href="{{ route('admin.orders.index', ['filter' => 'unpaid']) }}"
       class="stat-card" style="text-decoration:none;color:inherit;{{ $filter === 'unpaid' ? 'outline:2px solid #f59e0b;' : '' }}">
        <div class="stat-icon" style="background:#f59e0b;">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $summary['unpaid'] }}</h3>
            <p>Menunggu Pembayaran</p>
        </div>
    </a>

    <a href="{{ route('admin.orders.index', ['filter' => 'today']) }}"
       class="stat-card" style="text-decoration:none;color:inherit;{{ $filter === 'today' ? 'outline:2px solid #2563eb;' : '' }}">
        <div class="stat-icon" style="background:#2563eb;">
            <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <h3>{{ $summary['today'] }}</h3>
            <p>Masuk Hari Ini</p>
        </div>
    </a>
</div>

{{-- ===== Antrean proses (SLA maks 1 hari) ===== --}}
<div class="card">
    <div class="card-header">
        <h5><i class="fa-solid fa-list-check"></i> Antrean Proses Hari Ini</h5>
        <div>
            <span class="badge badge-secondary" style="font-size:.75rem;padding:4px 10px;">
                Total {{ $summary['queue_total'] }} pesanan aktif
            </span>
            <span class="badge {{ $summary['overdue'] > 0 ? 'badge-danger' : 'badge-success' }}"
                  style="font-size:.75rem;padding:4px 10px;">
                SLA maks {{ \App\Models\Order::SLA_HOURS }} jam
            </span>
        </div>
    </div>
    <div class="card-body">
        <p style="font-size:.85rem;color:#666;margin-top:0;">
            Urut dari yang paling lama menunggu. Pesanan <strong>wajib diproses maksimal 1 hari (24 jam)
            sejak dipesan</strong>; baris merah artinya sudah melewati batas.
        </p>

        @if ($queue->isEmpty())
            <p style="color:#888;padding:24px 0;text-align:center;margin:0;">
                <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i>
                Tidak ada pesanan yang menunggu diproses. Kerja bagus!
            </p>
        @else
            <div class="table-responsive">
                <table class="dt-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Menunggu</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($queue as $q)
                            @php $overdue = $q->isOverdue(); @endphp
                            <tr style="{{ $overdue ? 'background:#fef2f2;box-shadow:inset 3px 0 0 #dc2626;' : '' }}">
                                <td>
                                    <strong>{{ $q->order_number }}</strong>
                                    <div style="font-size:.72rem;color:#999;">
                                        {{ $q->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td>{{ $q->user?->name ?? '-' }}</td>
                                <td>Rp {{ number_format($q->total, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $q->paymentBadgeClass() }}">{{ $q->paymentStatusLabel() }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $q->statusBadgeClass() }}">{{ $q->statusLabel() }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $overdue ? 'badge-danger' : 'badge-secondary' }}">
                                        {{ $q->ageLabel() }}
                                    </span>
                                    <div style="font-size:.72rem;font-weight:600;margin-top:3px;color:{{ $overdue ? '#dc2626' : '#16a34a' }};">
                                        {{ $q->slaLabel() }}
                                    </div>
                                    @if ($overdue)
                                        <div style="font-size:.72rem;color:#dc2626;font-weight:600;">
                                            Lewat 1 Hari!
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="{{ route('admin.orders.show', $q) }}" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-hammer"></i>
                                        {{ $q->payment_status === 'paid' ? 'Proses' : 'Cek' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($summary['queue_total'] > $queue->count())
                <p style="font-size:.8rem;color:#888;margin:12px 0 0;">
                    Menampilkan {{ $queue->count() }} teratas —
                    <a href="{{ route('admin.orders.index', ['filter' => 'need_process']) }}">lihat semua di bawah</a>.
                </p>
            @endif
        @endif
    </div>
</div>

{{-- ===== Daftar semua pesanan ===== --}}
<div class="card">
    <div class="card-header">
        <h5>Daftar Pesanan</h5>
        <div style="display:flex;gap:8px;align-items:center;">
            @if ($filter)
                <span class="badge badge-primary" style="font-size:.72rem;padding:4px 8px;">
                    Filter: {{ $filter }}
                </span>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-xmark"></i> Reset
                </a>
            @endif
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Pesanan</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th>Menunggu</th>
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
var activeFilter = @json($filter);

$('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ route("admin.orders.datatable") }}',
        data: function (d) {
            d.filter = activeFilter;
        }
    },
    order: [[1, 'desc']],
    columns: [
        { data: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'order_number' },
        { data: 'date_formatted' },
        { data: 'customer' },
        { data: 'total_formatted' },
        { data: 'payment_badge' },
        { data: 'status_badge' },
        { data: 'age' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    language: dtLang
});
</script>
@endpush
