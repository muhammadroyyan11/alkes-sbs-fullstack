<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    public function datatable(Request $request)
    {
        $query = User::query();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name_col', function ($u) {
                $you = $u->id === auth()->id() ? ' <span class="badge badge-info">Anda</span>' : '';
                return '<strong>' . e($u->name) . '</strong>' . $you;
            })
            ->addColumn('role_badge', fn($u) => '<span class="badge ' . ($u->role === 'admin' ? 'badge-warning' : 'badge-secondary') . '">' . ucfirst($u->role) . '</span>')
            ->addColumn('status_badge', fn($u) => '<span class="badge ' . ($u->is_active ? 'badge-success' : 'badge-danger') . '">' . ($u->is_active ? 'Aktif' : 'Nonaktif') . '</span>')
            ->addColumn('actions', function ($u) {
                $edit = '<a href="' . route('admin.users.edit', $u) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></a>';
                $del  = $u->id !== auth()->id()
                    ? '<form method="POST" action="' . route('admin.users.destroy', $u) . '" style="display:inline;" onsubmit="return confirm(\'Hapus pengguna ini?\')"><input type="hidden" name="_token" value="' . csrf_token() . '"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>'
                    : '';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['name_col', 'role_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6|confirmed',
            'role'      => 'required|in:admin,user',
        ]);

        User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'password'  => 'nullable|min:6|confirmed',
            'role'      => 'required|in:admin,user',
        ]);

        $data = $request->except('password', 'password_confirmation');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
