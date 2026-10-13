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
        // Perbarui pesanan yang lewat 30 menit belum bayar (auto-cancel).
        \App\Services\OrderExpiry::run();
        // Selesaikan pesanan terkirim yang lewat batas hari (auto-complete).
        \App\Services\OrderAutoComplete::run();

        $status = $request->query('status');
        $allowedStatuses = ['unpaid', 'processing', 'shipped', 'completed', 'refunded'];

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

        \App\Services\OrderAutoComplete::run();
        $order->refresh();
        $order->load('items', 'shipment');

        return view('front.orders.show', compact('order'));
    }

    /**
     * Customer menekan "Pesanan Diterima" -> pesanan selesai
     * (gaya Tokopedia/Shopee; kepemilikan dicek dulu).
     */
    public function receive(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status === 'completed') {
            return redirect()->route('orders.show', $order)
                ->with('success', 'Pesanan sudah selesai. Terima kasih!');
        }

        if (!$order->markReceived()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Pesanan belum bisa dikonfirmasi diterima (status: ' . $order->statusLabel() . ').');
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Terima kasih! Pesanan ' . $order->order_number . ' ditandai selesai.');
    }
}
