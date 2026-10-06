<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid
        {--minutes=30 : Batas waktu pembayaran dalam menit}
        {--dry-run : Tampilkan pesanan yang akan dibatalkan tanpa membatalkannya}';

    protected $description = 'Batalkan otomatis pesanan belum bayar yang melewati batas waktu (default 30 menit) dan kembalikan stok.';

    public function handle(MidtransService $midtrans): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $dryRun = (bool) $this->option('dry-run');

        $orders = Order::query()
            ->where('status', 'pending')
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->orderBy('created_at')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada pesanan kedaluwarsa.');

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $age = $order->created_at->diffInMinutes(now());

            if ($dryRun) {
                $this->line(sprintf('[DRY-RUN] %s (umur %d menit) akan dibatalkan.', $order->order_number, $age));
                continue;
            }

            // Tutup transaksi Snap agar customer tidak bisa bayar lagi.
            $midtrans->cancelTransaction($order);

            $order->cancelAndRelease('lewat ' . $minutes . ' menit belum bayar');

            Log::info('Pesanan otomatis dibatalkan (melewati batas bayar)', [
                'order_number' => $order->order_number,
                'age_minutes' => $age,
                'payment_method' => $order->payment_method,
            ]);

            $this->info(sprintf('%s dibatalkan (umur %d menit), stok dikembalikan.', $order->order_number, $age));
        }

        return self::SUCCESS;
    }
}
