<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Selesaikan otomatis pesanan yang sudah dikirim/diterima melewati
 * batas hari (pengaturan website: `auto_complete_days`, 0 = mati).
 *
 * Pelengkap tombol "Pesanan Diterima" customer: bila customer tidak
 * pernah mengonfirmasi, pesanan tetap diselesaikan setelah N hari.
 */
class CompleteShippedOrders extends Command
{
    protected $signature = 'orders:auto-complete
        {--days= : Timpa jumlah hari dari pengaturan website}
        {--dry-run : Tampilkan pesanan yang akan diselesaikan tanpa mengubahnya}';

    protected $description = 'Selesaikan otomatis pesanan shipped/delivered yang lebih dari N hari.';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $days = $daysOption !== null
            ? max(0, (int) $daysOption)
            : Setting::int('auto_complete_days');

        if ($days <= 0) {
            $this->info('Auto-complete nonaktif (auto_complete_days = 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $orders = Order::query()
            ->leftJoin('shipments', 'shipments.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['shipped', 'in_transit', 'delivered'])
            ->whereRaw('COALESCE(shipments.shipped_at, orders.updated_at) <= ?', [$cutoff])
            ->select('orders.*')
            ->with('shipment')
            ->orderBy('orders.id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada pesanan yang perlu diselesaikan otomatis.');

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $since = $order->shipment?->shipped_at ?: $order->updated_at;
            $age = (int) $since->diffInDays(now());

            if ($dryRun) {
                $this->line(sprintf('[DRY-RUN] %s (dikirim %d hari lalu) akan diselesaikan.', $order->order_number, $age));
                continue;
            }

            $order->markReceived();

            Log::info('Pesanan diselesaikan otomatis (auto-complete)', [
                'order_number' => $order->order_number,
                'shipped_days_ago' => $age,
                'threshold_days' => $days,
            ]);

            $this->info(sprintf('%s diselesaikan otomatis (dikirim %d hari lalu).', $order->order_number, $age));
        }

        return self::SUCCESS;
    }
}
