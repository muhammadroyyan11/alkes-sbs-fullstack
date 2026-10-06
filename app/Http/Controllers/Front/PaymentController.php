<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\OrderExpiry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected MidtransService $midtrans)
    {
    }

    public function index(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // Pastikan pesanan yang lewat 30 menit belum bayar sudah dibatalkan.
        OrderExpiry::run();
        $order->refresh();

        // Pelengkap webhook: tanyakan status terkini ke API Midtrans.
        if ($order->payment_status !== 'paid' && $order->status !== 'cancelled') {
            $this->midtrans->syncTransactionStatus($order);
            $order->refresh();
        }

        // Sudah lunas -> langsung ke halaman selesai.
        if ($order->payment_status === 'paid') {
            return redirect()->route('payment.finish', $order);
        }

        // Dibatalkan (auto 30 menit / gagal bayar) -> tampilkan keterangan, bukan 404.
        if ($order->status === 'cancelled') {
            $order->load('items');

            return view('front.payment.cancelled', compact('order'));
        }

        abort_unless($order->payment_status === 'unpaid', 404);

        $snapToken = $order->snap_token;
        if (!$snapToken) {
            $snapToken = $this->midtrans->createSnapToken($order, $order->payment_method);
            if ($snapToken) {
                $order->update(['snap_token' => $snapToken]);
            }
        }
        $order->load('items', 'shipment');

        return view('front.payment.index', compact('order', 'snapToken'));
    }

    /**
     * Webhook notifikasi pembayaran Midtrans.
     * Didaftarkan di Midtrans Dashboard -> Settings -> Payment Notification URL
     * (contoh: https://domain-anda/payment/callback).
     */
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->except(['_token']);
        }

        if (!$this->midtrans->verifySignature($payload)) {
            Log::warning('Midtrans callback: signature tidak valid', [
                'ip' => $request->ip(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        $orderNumber = $payload['order_id'] ?? null;
        if (!$orderNumber) {
            return response()->json(['status' => 'error', 'message' => 'Missing order_id'], 400);
        }

        $order = Order::where('order_number', $orderNumber)
            ->orWhere('midtrans_order_id', $orderNumber)
            ->first();

        if (!$order) {
            Log::warning('Midtrans callback: pesanan tidak ditemukan', ['order_id' => $orderNumber]);

            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // Nominal harus sama dengan total pesanan (anti-tamper).
        $grossAmount = $payload['gross_amount'] ?? null;
        if ($grossAmount !== null && (float) $grossAmount !== (float) $order->total) {
            Log::error('Midtrans callback: nominal tidak cocok', [
                'order_number' => $order->order_number,
                'expected' => (string) $order->total,
                'received' => (string) $grossAmount,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 200);
        }

        $this->midtrans->handleNotification($order, $payload);

        return response()->json(['status' => 'ok']);
    }

    public function finish(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // Pastikan pesanan yang lewat 30 menit belum bayar sudah dibatalkan.
        OrderExpiry::run();
        $order->refresh();

        // Pelengkap webhook: sinkronkan status bila notifikasi belum masuk.
        if ($order->payment_status !== 'paid' && $order->status !== 'cancelled') {
            $this->midtrans->syncTransactionStatus($order);
            $order->refresh();
        }

        $order->load('items', 'shipment');

        return view('front.payment.finish', compact('order'));
    }
}
