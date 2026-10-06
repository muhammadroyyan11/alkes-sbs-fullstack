<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\Variant;
use App\Models\Product;
use App\Models\PurchaseReceive;
use App\Models\StockOpnameItem;
use RuntimeException;

/**
 * Pencatat keluar-masuk barang (log stok) untuk pergerakan stok.
 *
 * Dua buku stok selalu bergerak bersama:
 *  - `products.stock` / `variants.stock` -> stok jual (dipakai storefront/checkout)
 *  - `stocks.quantity`                   -> buku gudang (dipakai admin & stok opname)
 *
 * Alur:
 *  - checkout     -> OUT status "waiting_for_checkout" (stok ditahan pesanan)
 *  - dibayar      -> OUT status "completed"
 *  - dibatalkan   -> IN  status "cancelled" + stok dikembalikan
 *  - PR disetujui -> IN  status "completed" (barang masuk gudang)
 *  - opname disetujui -> mutasi sesuai selisih, kedua buku disamakan
 */
class StockLogService
{
    /**
     * Cari baris stok (tabel `stocks`) milik produk/variant; buat bila belum ada
     * supaya riwayat mutasi selalu punya tempat menempel.
     */
    public function resolveStock(?int $variantId, ?int $productId, int $fallbackQty = 0): ?Stock
    {
        if ($variantId) {
            return Stock::firstOrCreate(
                ['variant_id' => $variantId],
                ['product_id' => $productId, 'quantity' => $fallbackQty]
            );
        }

        if ($productId) {
            return Stock::firstOrCreate(
                ['product_id' => $productId, 'variant_id' => null],
                ['quantity' => $fallbackQty]
            );
        }

        return null;
    }

    /**
     * Catat stok KELUAR karena pesanan dibuat (stok ditahan sementara).
     */
    public function recordOut(Order $order, ?int $variantId, ?int $productId, int $qty, int $currentStock = 0, ?int $userId = null): ?StockMutation
    {
        $stock = $this->findStock($variantId, $productId);
        if (!$stock) {
            // Baris gudang belum ada: buat sebesar stok jual terkini (sudah dipotong checkout).
            $stock = $this->resolveStock($variantId, $productId, $currentStock);
        } else {
            $stock->quantity = max(0, $stock->quantity - $qty);
            $stock->save();
        }

        if (!$stock) {
            return null;
        }

        return $stock->mutations()->create([
            'type' => 'out',
            'status' => StockMutation::STATUS_WAITING,
            'quantity' => $qty,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'note' => 'OUT - waiting for checkout: ' . $order->order_number,
            'created_by' => $userId,
        ]);
    }

    /**
     * Catat stok MASUK kembali ke stok utama (pesanan dibatalkan/gagal).
     */
    public function recordIn(Order $order, ?int $variantId, ?int $productId, int $qty, string $reason, int $currentStock = 0, ?int $userId = null): ?StockMutation
    {
        $stock = $this->findStock($variantId, $productId);
        if (!$stock) {
            $stock = $this->resolveStock($variantId, $productId, $currentStock);
        } else {
            $stock->quantity += $qty;
            $stock->save();
        }

        if (!$stock) {
            return null;
        }

        return $stock->mutations()->create([
            'type' => 'in',
            'status' => StockMutation::STATUS_CANCELLED,
            'quantity' => $qty,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'note' => 'IN - cancel transaction (' . $reason . '): ' . $order->order_number,
            'created_by' => $userId,
        ]);
    }

    /**
     * Stok MASUK dari penerimaan pembelian (Purchase Receive disetujui).
     * Mengunci baris produk/variant supaya approval ganda tidak menumpuk,
     * lalu menaikkan stok jual + buku gudang dan mencatat mutasi IN.
     */
    public function receiveFromPurchase(
        ?int $variantId,
        ?int $productId,
        int $qty,
        PurchaseReceive $receive,
        ?int $userId = null
    ): ?StockMutation {
        if ($qty <= 0) {
            return null;
        }

        if ($variantId) {
            $variant = Variant::lockForUpdate()->find($variantId);
            if (!$variant) {
                throw new RuntimeException('Variant #' . $variantId . ' tidak ditemukan.');
            }
            $variant->increment('stock', $qty);
            $before = $variant->stock - $qty;
        } else {
            $product = Product::lockForUpdate()->find($productId);
            if (!$product) {
                throw new RuntimeException('Produk #' . $productId . ' tidak ditemukan.');
            }
            $product->increment('stock', $qty);
            $before = $product->stock - $qty;
        }

        $stock = $this->findStock($variantId, $productId);
        if (!$stock) {
            $stock = $this->resolveStock($variantId, $productId, $before);
        }
        $stock->quantity += $qty;
        $stock->save();

        return $stock->mutations()->create([
            'type' => 'in',
            'status' => StockMutation::STATUS_COMPLETED,
            'quantity' => $qty,
            'reference_type' => PurchaseReceive::class,
            'reference_id' => $receive->id,
            'note' => 'IN - penerimaan pembelian: ' . $receive->receive_number,
            'created_by' => $userId,
        ]);
    }

    /**
     * Terapkan hasil stok opname: samakan stok jual & buku gudang dengan
     * stok fisik, lalu catat mutasi sesuai selisih.
     */
    public function applyOpname(StockOpnameItem $item, string $code, ?int $userId = null): ?StockMutation
    {
        $variantId = $item->variant_id ?: null;
        $productId = $item->product_id ?: null;
        $physical = max(0, (int) $item->physical_stock);
        $delta = $physical - (int) $item->system_stock;

        $before = $physical;
        if ($variantId) {
            $variant = Variant::lockForUpdate()->find($variantId);
            if ($variant) {
                $before = (int) $variant->stock;
                $variant->stock = $physical;
                $variant->save();
            }
        } elseif ($productId) {
            $product = Product::lockForUpdate()->find($productId);
            if ($product) {
                $before = (int) $product->stock;
                $product->stock = $physical;
                $product->save();
            }
        }

        $stock = $this->findStock($variantId, $productId);
        if (!$stock) {
            $stock = $this->resolveStock($variantId, $productId, $physical);
        } else {
            $stock->quantity = $physical;
            $stock->save();
        }

        if ($delta === 0 && $before === $physical) {
            return null;
        }

        if ($delta !== 0) {
            $type = $delta > 0 ? 'in' : 'out';
            $quantity = abs($delta);
            $sign = $delta > 0 ? '+' : '-';
        } else {
            $type = 'adjustment';
            $quantity = abs($physical - $before);
            $sign = $physical > $before ? '+' : '-';
        }

        return $stock->mutations()->create([
            'type' => $type,
            'status' => StockMutation::STATUS_COMPLETED,
            'quantity' => $quantity,
            'reference_type' => \App\Models\StockOpname::class,
            'reference_id' => $item->stock_opname_id,
            'note' => sprintf(
                'ADJ - Stok opname %s: sistem %d -> fisik %d (%s%d)',
                $code,
                (int) $item->system_stock,
                $physical,
                $sign,
                $quantity
            ),
            'created_by' => $userId,
        ]);
    }

    /**
     * Log keluar milik suatu pesanan (terbaru di atas).
     */
    public function forOrder(Order $order)
    {
        return StockMutation::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->latest();
    }

    /**
     * Cari baris gudang tanpa membuat baru (untuk tahu apakah perlu sinkron qty).
     */
    private function findStock(?int $variantId, ?int $productId): ?Stock
    {
        if ($variantId) {
            return Stock::where('variant_id', $variantId)->first();
        }

        if ($productId) {
            return Stock::where('product_id', $productId)->whereNull('variant_id')->first();
        }

        return null;
    }
}
