<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — ALKES SBS</title>
    <link rel="icon" type="image/png" href="{{ asset('alkes-sbs.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary: #dc2626;
            --primary-dark: #b91c1c;
            --sidebar-w: 250px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-w); background: #1a1a2e;
            color: #fff; z-index: 1000; transition: transform .3s;
            overflow-y: auto;
        }
        .sidebar.hidden { transform: translateX(-100%); }
        .sidebar-brand {
            padding: 20px 16px; background: var(--primary);
            font-size: 1.1rem; font-weight: 700; display: flex;
            align-items: center; gap: 10px;
        }
        .sidebar-brand img { width: 28px; height: 28px; object-fit: contain; }
        .sidebar-menu { padding: 12px 0; }
        .menu-label {
            padding: 8px 16px 4px; font-size: .7rem;
            text-transform: uppercase; color: #888; letter-spacing: 1px;
        }
        .menu-item a {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px; color: #ccc; text-decoration: none;
            transition: all .2s; font-size: .9rem;
        }
        .menu-item a:hover, .menu-item a.active {
            background: rgba(255,255,255,.1); color: #fff;
            border-left: 3px solid var(--primary);
        }
        .menu-item a i { width: 18px; text-align: center; }

        /* Topbar */
        .topbar {
            position: fixed; top: 0; left: var(--sidebar-w); right: 0;
            height: 60px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px; z-index: 999; transition: left .3s;
        }
        .topbar.full { left: 0; }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .btn-toggle {
            background: none; border: none; font-size: 1.2rem;
            cursor: pointer; color: #555; padding: 4px 8px;
        }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .user-info { font-size: .85rem; text-align: right; }
        .user-info strong { display: block; }
        .user-info span { color: #888; font-size: .75rem; }
        .btn-user-dropdown {
            background: var(--primary); color: #fff; border: none;
            padding: 6px 14px; border-radius: 6px; cursor: pointer;
            font-size: .85rem; text-decoration: none; display: inline-flex;
            align-items: center; gap: 6px; position: relative;
        }
        .btn-user-dropdown:hover { background: var(--primary-dark); }
        .user-dropdown-menu {
            position: absolute; top: calc(100% + 6px); right: 0;
            background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.12);
            min-width: 200px; display: none; z-index: 1000; overflow: hidden;
        }
        .user-dropdown-menu.show { display: block; }
        .user-dropdown-menu a, .user-dropdown-menu button {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px; font-size: .85rem; color: #333;
            text-decoration: none; border: none; background: none;
            width: 100%; text-align: left; cursor: pointer;
        }
        .user-dropdown-menu a:hover, .user-dropdown-menu button:hover {
            background: #f5f5f5; color: var(--primary);
        }
        .user-dropdown-divider { height: 1px; background: #eee; margin: 4px 0; }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-w); margin-top: 60px;
            padding: 24px; min-height: calc(100vh - 60px);
            transition: margin-left .3s;
        }
        .main-content.full { margin-left: 0; }

        /* Cards */
        .card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06); overflow: hidden;
        }
        .card-header {
            padding: 16px 20px; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header h5 { font-size: 1rem; font-weight: 600; }
        .card-body { padding: 20px; }

        /* Stats */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 24px;
        }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 52px; height: 52px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff; flex-shrink: 0;
        }
        .stat-info h3 { font-size: 1.4rem; font-weight: 700; }
        .stat-info p { font-size: .8rem; color: #888; margin-top: 2px; }

        /* Table */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th {
            background: #f8f9fa; padding: 12px 16px;
            text-align: left; font-weight: 600; color: #555;
            border-bottom: 2px solid #e9ecef;
        }
        tbody td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; }
        tbody tr:hover { background: #fafafa; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; border: none;
            cursor: pointer; font-size: .875rem; font-weight: 500;
            text-decoration: none; transition: all .2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: #fff; }
        .btn-success:hover { background: #218838; }
        .btn-sm { padding: 5px 10px; font-size: .8rem; }
        .btn-outline {
            background: transparent; border: 1px solid currentColor;
        }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 500; font-size: .875rem; }
        .form-control {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd;
            border-radius: 8px; font-size: .875rem; transition: border .2s;
            background: #fff;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(220,38,38,.15); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        /* Badge */
        .badge {
            display: inline-block; padding: 3px 8px; border-radius: 20px;
            font-size: .75rem; font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .badge-primary { background: var(--primary); color: #fff; }

        /* Alert */
        .alert {
            padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        /* Pagination */
        .pagination { display: flex; gap: 4px; justify-content: center; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span {
            padding: 6px 12px; border-radius: 6px; font-size: .85rem;
            text-decoration: none; border: 1px solid #ddd; color: #555;
        }
        .pagination .active span { background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination a:hover { background: #f0f0f0; }

        /* Overlay */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 999;
        }
        .sidebar-overlay.show { display: block; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .topbar { left: 0 !important; }
            .main-content { margin-left: 0 !important; padding: 16px; }
            .form-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        /* DataTables overrides */
        .dt-table { font-size: .875rem; }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd; border-radius: 8px;
            padding: 6px 10px; font-size: .85rem;
        }
        .dataTables_wrapper .dataTables_filter input:focus { outline: none; border-color: var(--primary); }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 10px; border-radius: 6px; font-size: .82rem;
            border: 1px solid #ddd !important; margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary) !important; color: #fff !important;
            border-color: var(--primary) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f0f0f0 !important; color: #333 !important;
        }

        /* Select2 */
        .select2-container--default .select2-selection--single {
            height: 38px; border: 1px solid #ddd; border-radius: 8px;
            padding: 5px 12px; font-size: .875rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--primary); }
        .select2-dropdown { border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .select2-search--dropdown .select2-search__field { border-radius: 6px; border: 1px solid #ddd; padding: 6px 10px; }
        .dataTables_wrapper .dataTables_info { font-size: .82rem; color: #888; }
        div.dataTables_processing { background: rgba(255,255,255,.9); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('alkes-sbs.png') }}" alt="ALKES SBS">
        <span>ALKES SBS</span>
    </div>
    <nav class="sidebar-menu">
        @php
            $sidebarMenus = \App\Models\Menu::getForUser(auth()->user());
        @endphp

        @foreach($sidebarMenus as $groupName => $group)
        <div class="menu-label">{{ $groupName }}</div>
        @foreach($group['items'] as $menu)
        <div class="menu-item">
            <a href="{{ $menu->menu_url }}" class="{{ $menu->is_current ? 'active' : '' }}">
                <i class="{{ $menu->icon }}"></i> {{ $menu->name }}
                @if($menu->badge_text)
                <span style="font-size:.65rem;background:{{ $menu->badge_color }};color:#fff;padding:2px 5px;border-radius:8px;margin-left:4px;">{{ $menu->badge_text }}</span>
                @endif
            </a>
        </div>
        @endforeach
        @endforeach
    </nav>
</aside>

<!-- Topbar -->
<header class="topbar" id="topbar">
    <div class="topbar-left">
        <button class="btn-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span style="font-weight:600;color:#333;">@yield('page-title', 'Dashboard')</span>
    </div>
    <div class="topbar-right">
        <div style="position:relative;">
            <button class="btn-user-dropdown" onclick="toggleUserMenu()">
                <i class="fa-solid fa-user"></i>
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <i class="fa-solid fa-chevron-down" style="font-size:.7rem;"></i>
            </button>
            <div class="user-dropdown-menu" id="userDropdown">
                <a href="{{ route('profile.edit') }}">
                    <i class="fa-solid fa-user-pen"></i> Edit Profile
                </a>
                <a href="{{ route('profile.edit') }}#password">
                    <i class="fa-solid fa-lock"></i> Ubah Password
                </a>
                <div class="user-dropdown-divider"></div>
                <button onclick="confirmLogout()">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </button>
                <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display:none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Main -->
<main class="main-content" id="mainContent">
    @yield('content')
</main>

<script>
    let sidebarOpen = window.innerWidth > 768;

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const main = document.getElementById('mainContent');
        const topbar = document.getElementById('topbar');

        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        } else {
            sidebarOpen = !sidebarOpen;
            sidebar.classList.toggle('hidden', !sidebarOpen);
            main.classList.toggle('full', !sidebarOpen);
            topbar.classList.toggle('full', !sidebarOpen);
        }
    }

    // Auto-hide sidebar on mobile
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('show');
    }
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ═══ SweetAlert Global Helpers ═══
const Toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
});

// Flash messages dari Laravel
@if(session('success'))
Toast.fire({ icon: 'success', title: @json(session('success')) });
@endif
@if(session('error'))
Toast.fire({ icon: 'error', title: @json(session('error')) });
@endif

// Override confirm untuk semua form dengan onsubmit="return confirm(...)"
document.addEventListener('submit', function(e) {
    const form = e.target;
    const onsubmit = form.getAttribute('onsubmit');
    if (onsubmit && onsubmit.includes('confirm(')) {
        e.preventDefault();
        const match = onsubmit.match(/confirm\(['"](.+?)['"]\)/);
        const msg = match ? match[1] : 'Apakah Anda yakin?';
        Swal.fire({
            title: 'Konfirmasi',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.removeAttribute('onsubmit');
                form.submit();
            }
        });
    }
});

// Override onclick confirm untuk button
document.addEventListener('click', function(e) {
    const btn = e.target.closest('button[onclick*="confirm("]');
    if (btn && !btn.dataset.swalBypassed) {
        e.preventDefault();
        e.stopPropagation();
        const onclick = btn.getAttribute('onclick');
        const match = onclick.match(/confirm\(['"](.+?)['"]\)/);
        const msg = match ? match[1] : 'Apakah Anda yakin?';
        Swal.fire({
            title: 'Konfirmasi',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.dataset.swalBypassed = 'true';
                btn.click();
                delete btn.dataset.swalBypassed;
            }
        });
    }
}, true);

// Global alert replacement
window.nativeAlert = window.alert;
window.alert = function(msg) {
    Swal.fire({ title: 'Info', text: msg, icon: 'info', confirmButtonColor: '#dc2626' });
};
// Dropdown user menu
function toggleUserMenu() {
    document.getElementById('userDropdown').classList.toggle('show');
}
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown && dropdown.classList.contains('show') && !e.target.closest('.btn-user-dropdown') && !e.target.closest('.user-dropdown-menu')) {
        dropdown.classList.remove('show');
    }
});

// Logout with SweetAlert
function confirmLogout() {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('logoutForm').submit();
        }
    });
}

// Bahasa Indonesia untuk semua DataTables
const dtLang = {
    processing: '<i class="fa-solid fa-spinner fa-spin"></i> Memuat...',
    search: 'Cari:',
    lengthMenu: 'Tampilkan _MENU_ data',
    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data',
    infoFiltered: '(difilter dari _MAX_ total data)',
    zeroRecords: '<div style="text-align:center;padding:20px;color:#888;"><i class="fa-solid fa-box-open" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>Tidak ada data ditemukan</div>',
    emptyTable: '<div style="text-align:center;padding:20px;color:#888;"><i class="fa-solid fa-box-open" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>Belum ada data</div>',
    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
};
</script>
@stack('scripts')
</body>
</html>
