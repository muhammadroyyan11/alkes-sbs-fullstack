<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $allowedStatuses = ['unpaid', 'processing', 'shipped', 'completed'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $orders = $request->user()
            ->orders()
            ->with('shipment')
            ->forCustomerStatus($status)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('front.orders.index', compact('orders', 'status'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items', 'shipment');

        return view('front.orders.show', compact('order'));
    }
}
