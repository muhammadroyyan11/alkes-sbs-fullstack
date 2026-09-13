<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index()
    {
        return view('admin.suppliers.index');
    }

    public function datatable()
    {
        $query = Supplier::query();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', fn($s) => '<strong>' . e($s->name) . '</strong>')
            ->addColumn('contact', fn($s) => e($s->contact_person ?? '-'))
            ->addColumn('phone', fn($s) => e($s->phone ?? '-'))
            ->addColumn('email', fn($s) => e($s->email ?? '-'))
            ->addColumn('actions', function ($s) {
                $edit = '<a href="' . route('admin.suppliers.edit', $s) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></a>';
                $del  = '<form method="POST" action="' . route('admin.suppliers.destroy', $s) . '" style="display:inline;" onsubmit="return confirm(\'Hapus supplier ini?\')"><input type="hidden" name="_token" value="' . csrf_token() . '"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['name', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string',
        ]);

        Supplier::create($request->only([
            'name', 'contact_person', 'phone', 'email', 'address',
        ]));

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string',
        ]);

        $supplier->update($request->only([
            'name', 'contact_person', 'phone', 'email', 'address',
        ]));

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}
