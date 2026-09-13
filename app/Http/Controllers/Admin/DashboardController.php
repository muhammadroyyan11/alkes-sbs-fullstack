<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Models\Stock;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalProducts = Product::where('is_active', true)->count();
        $totalUsers = User::count();
        $lowStock = Stock::where('quantity', '<=', 5)->count();
        $pendingOrders = PurchaseOrder::whereIn('status', ['draft', 'sent'])->count();

        return view('admin.dashboard.index', compact(
            'totalProducts', 'totalUsers', 'lowStock', 'pendingOrders'
        ));
    }
}
