<?php

namespace App\Services;

use Midtrans\Config;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected ?string $lastError = null;

    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.production', false);
        Config::$is3ds = config('midtrans.3ds', true);
        Config::$isSanitized = config('midtrans.sanitized', true);
    }

    public function createSnapToken(Order $order, ?string $paymentMethod = null): ?string
    {
        $token = $this->requestSnapToken($order, $paymentMethod);

        if ($token === null && $this->isDuplicateOrderIdError($this->lastError)) {
            $order->midtrans_order_id = $order->order_number . '-R' . strtoupper(substr(uniqid(), -5));
            $order->save();
            $token = $this->requestSnapToken($order, $paymentMethod);
        }

        return $token;
    }

    protected function isDuplicateOrderIdError(?string $message): bool
    {
        return $message !== null
            && str_contains($message, 'order_id')
            && (str_contains($message, 'taken') || str_contains($message, 'digunakan'));
    }

    protected function requestSnapToken(Order $order, ?string $paymentMethod): ?string
    {
        $this->lastError = null;

        try {
            $params = [
                'transaction_details' => [
                    'order_id' => $order->midtrans_order_id ?: $order->order_number,
                    'gross_amount' => (int) $order->total,
                ],
                'customer_details' => [
                    'first_name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone ?? '',
                    'shipping_address' => [
                        'first_name' => $order->user->name,
                        'phone' => $order->user->phone ?? '',
                        'address' => $order->shipping_address,
                    ],
                ],
                'item_details' => $order->items->map(fn ($item) => [
                    'id' => $item->sku,
                    'name' => $item->product_name,
                    'price' => (int) $item->price,
                    'quantity' => $item->quantity,
                ])->concat(array_values(array_filter([
                    [
                        'id' => 'SHIPPING',
                        'name' => 'Biaya Pengiriman (' . $order->shipping_method . ')',
                        'price' => (int) $order->shipping_cost,
                        'quantity' => 1,
                    ],
                    (int) $order->admin_fee > 0 ? [
                        'id' => 'ADMIN_FEE',
                        'name' => (string) \App\Models\Setting::get('admin_fee_label', 'Biaya Admin'),
                        'price' => (int) $order->admin_fee,
                        'quantity' => 1,
                    ] : null,
                ])))->toArray(),
                'callbacks' => [
                    'finish' => route('payment.finish', $order),
                ],
            ];

            $enabled = $this->mapPaymentMethod($paymentMethod);
            if ($enabled) {
                $params['enabled_payments'] = $enabled;
            }

            return \Midtrans\Snap::getSnapToken($params);
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('Midtrans snap token error: ' . $e->getMessage());
            return null;
        }
    }

    protected function mapPaymentMethod(?string $method): ?array
    {
        return match ($method) {
            'bca_va' => ['bca_va'],
            'bri_va' => ['bri_va'],
            'mandiri_va' => ['mandiri_va'],
            'bni_va' => ['bni_va'],
            'permata_va' => ['permata_va'],
            'qris' => ['qris'],
            'gopay' => ['gopay'],
            'shopeepay' => ['shopeepay'],
            'dana' => ['danamon_online'],
            'ovo' => ['indomaret'],
            'alfamart' => ['alfamart'],
            'indomaret' => ['indomaret'],
            'credit_card' => ['credit_card'],
            default => null,
        };
    }

    /**
     * Verifikasi signature_key notifikasi Midtrans.
     * SHA512(order_id + status_code + gross_amount + serverKey)
     * https://docs.midtrans.com/docs/https-notification-webhooks
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.server_key'));

        return hash_equals($expected, $signature);
    }

    /**
     * Terima notifikasi Midtrans. Idempoten: notifikasi duplikat atau tidak
     * berurutan (out-of-order) tidak mengubah state yang sudah final.
     */
    public function handleNotification(Order $order, array $notification): void
    {
        try {
            $transactionStatus = $notification['transaction_status'] ?? null;
            $fraudStatus = $notification['fraud_status'] ?? null;
            $paymentType = $notification['payment_type'] ?? null;

            Log::info('Midtrans notification', [
                'order_number' => $order->order_number,
                'order_id' => $notification['order_id'] ?? null,
                'status' => $transactionStatus,
                'fraud' => $fraudStatus,
                'payment_type' => $paymentType,
            ]);

            match (true) {
                $transactionStatus === 'settlement' => $this->paymentSuccess($order, $notification),
                $transactionStatus === 'capture' && in_array($fraudStatus, ['accept', null], true) => $this->paymentSuccess($order, $notification),
                in_array($transactionStatus, ['cancel', 'expire', 'deny'], true) => $this->paymentFailed($order, $notification),
                $transactionStatus === 'pending' => $this->paymentPending($order),
                default => Log::warning('Midtrans notification: status tidak dikenal', [
                    'order_number' => $order->order_number,
                    'status' => $transactionStatus,
                ]),
            };
        } catch (\Exception $e) {
            Log::error('Midtrans notification handling error: ' . $e->getMessage());
        }
    }

    protected function paymentSuccess(Order $order, array $notification): void
    {
        if ($order->payment_status === 'paid') {
            Log::info('Midtrans notification diabaikan (sudah lunas)', [
                'order_number' => $order->order_number,
            ]);
            return;
        }

        // Pembayaran masuk SETELAH pesanan dibatalkan (mis. auto-cancel 30 menit):
        // jangan dihidupkan lagi tanpa review admin, catat error agar ditindak.
        if ($order->status === 'cancelled') {
            Log::error('Pembayaran masuk setelah pesanan dibatalkan - perlu penanganan manual', [
                'order_number' => $order->order_number,
                'transaction_id' => $notification['transaction_id'] ?? null,
                'amount' => $notification['gross_amount'] ?? null,
            ]);
            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $notification['transaction_id'] ?? $order->payment_reference,
            'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
        ]);
        $order->markReservationPaid();

        Log::info('Pembayaran lunas', [
            'order_number' => $order->order_number,
            'payment_type' => $notification['payment_type'] ?? null,
        ]);
    }

    protected function paymentFailed(Order $order, array $notification): void
    {
        // Pesanan lunas tidak dibatalkan oleh notifikasi gagal (refund terpisah).
        if ($order->payment_status === 'paid') {
            Log::warning('Notifikasi gagal diabaikan karena pesanan sudah lunas', [
                'order_number' => $order->order_number,
            ]);
            return;
        }

        // Idempoten: jangan batalkan dua kali agar stok tidak dikembalikan dobel.
        if ($order->payment_status === 'failed' && $order->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);
            $order->load('items');
            $order->restoreStock('pembayaran gagal/dibatalkan Midtrans');
        });

        Log::info('Pembayaran gagal / dibatalkan Midtrans', [
            'order_number' => $order->order_number,
            'transaction_status' => $notification['transaction_status'] ?? null,
        ]);
    }

    protected function paymentPending(Order $order): void
    {
        // Abaikan notifikasi pending yang datang setelah status final
        // (Midtrans bisa mengirim notifikasi tidak berurutan).
        if (in_array($order->payment_status, ['paid', 'failed'], true)) {
            return;
        }

        $order->update(['payment_status' => 'unpaid']);
    }

    /**
     * Sinkronkan status pembayaran dengan memanggil API Status Midtrans.
     * Dipakai sebagai pelengkap webhook (mis. saat server belum bisa
     * menerima notifikasi, atau notifikasi telat).
     */
    public function syncTransactionStatus(Order $order): ?string
    {
        if ($order->payment_status === 'paid') {
            return 'paid';
        }

        $status = $this->getTransactionStatus($order);
        if (!$status) {
            return $order->payment_status;
        }

        $this->handleNotification($order, [
            'order_id' => $status['order_id'] ?? $order->midtrans_order_id ?: $order->order_number,
            'transaction_status' => $status['transaction_status'] ?? null,
            'fraud_status' => $status['fraud_status'] ?? null,
            'transaction_id' => $status['transaction_id'] ?? null,
            'payment_type' => $status['payment_type'] ?? null,
        ]);

        return $order->fresh()->payment_status;
    }

    public function getTransactionStatus(Order $order): ?array
    {
        try {
            $status = \Midtrans\Transaction::status($order->midtrans_order_id ?: $order->order_number);
            return [
                'order_id' => $status->order_id ?? null,
                'transaction_status' => $status->transaction_status ?? null,
                'fraud_status' => $status->fraud_status ?? null,
                'payment_type' => $status->payment_type ?? null,
                'gross_amount' => $status->gross_amount ?? null,
                'transaction_id' => $status->transaction_id ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans status check error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Batalkan transaksi terbuka di Midtrans (best effort).
     * Dipakai saat pesanan auto-batal (lewat 30 menit belum bayar) supaya
     * customer tidak bisa lagi membayar pesanan yang sudah dibatalkan.
     */
    public function cancelTransaction(Order $order): bool
    {
        // Tidak ada transaksi Snap yang perlu dibatalkan.
        if (!$order->midtrans_order_id && !$order->snap_token) {
            return false;
        }

        try {
            \Midtrans\Transaction::cancel($order->midtrans_order_id ?: $order->order_number);
            return true;
        } catch (\Exception $e) {
            Log::warning('Midtrans cancel (best effort) gagal: ' . $e->getMessage(), [
                'order_number' => $order->order_number,
            ]);
            return false;
        }
    }
}
