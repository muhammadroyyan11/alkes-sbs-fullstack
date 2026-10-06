@extends('layouts.admin')

@section('title', 'Kategori Produk')
@section('page-title', 'Kategori Produk')

@section('content')
<div class="card">
    <div class="card-header">
        <h5>Daftar Kategori</h5>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Kategori
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="dt-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Jumlah Produk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $i => $category)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td>{{ $category->slug }}</td>
                        <td><span class="badge badge-info">{{ $category->products_count }}</span></td>
                        <td>
                            <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-secondary' }}">
                                {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-secondary">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display:inline;"
                                  onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada kategori.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
