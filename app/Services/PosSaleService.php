<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur transaksi kasir (POS).
 *
 * Stok mengikuti konsep yang sama dengan pesanan online:
 *  - `products.stock` / `variants.stock` (stok jual) dipotong saat pembayaran
 *  - `stocks.quantity` (buku gudang) ikut bergerak lewat StockLogService
 *  - setiap pergerakan meninggalkan baris di `stock_mutations`
 *
 * Pembatalan mengembalikan kedua buku dan menutup mutasi OUT terkait.
 */
class PosSaleService
{
    public function __construct(private StockLogService $log)
    {
    }

    /**
     * Bayar transaksi kasir.
     *
     * @param array<int, array{product_id:int, variant_id:int|null, quantity:int}> $items
     * @param array{discount?:float, paid_amount:float, payment_method:string, customer_name?:string|null, notes?:string|null} $meta
     *
     * @throws ValidationException stok kurang / item tidak dikenal
     */
    public function checkout(array $items, array $meta, User $kasir): PosSale
    {
        if (!$items) {
            throw ValidationException::withMessages(['items' => 'Keranjang kosong.']);
        }

        return DB::transaction(function () use ($items, $meta, $kasir) {
            $subtotal = 0;
            $lines = [];

            foreach ($items as $index => $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 0));
                $variantId = !empty($item['variant_id']) ? (int) $item['variant_id'] : null;
                $productId = !empty($item['product_id']) ? (int) $item['product_id'] : null;

                if ($variantId) {
                    $model = Variant::lockForUpdate()->find($variantId);
                } else {
                    $model = $productId ? Product::lockForUpdate()->find($productId) : null;
                }

                if (!$model || ($model instanceof Product && !$model->is_active)) {
                    throw ValidationException::withMessages([
                        'items' => 'Baris ' . ($index + 1) . ': produk tidak ditemukan atau nonaktif.',
                    ]);
                }

                $stock = (int) $model->stock;
                if ($stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'Baris %d: stok %s tidak mencukupi (tersisa %d).',
                            $index + 1,
                            $this->labelFor($model),
                            $stock
                        ),
                    ]);
                }

                $price = (float) $model->price;
                $lineTotal = $price * $qty;

                $lines[] = [
                    'model' => $model,
                    'variant_id' => $variantId,
                    'product_id' => $model instanceof Variant ? $model->product_id : $model->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $lineTotal,
                ];

                $subtotal += $lineTotal;
            }

            $discount = max(0, (float) ($meta['discount'] ?? 0));
            $total = max(0, $subtotal - $discount);
            $paid = max(0, (float) ($meta['paid_amount'] ?? 0));

            if ($paid + 0.001 < $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang diterima kurang dari total belanja.',
                ]);
            }

            $sale = PosSale::create([
                'invoice' => $this->nextInvoice(),
                'user_id' => $kasir->id,
                'customer_name' => trim((string) ($meta['customer_name'] ?? '')) ?: null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'change_amount' => max(0, $paid - $total),
                'payment_method' => $meta['payment_method'],
                'status' => PosSale::STATUS_COMPLETED,
                'notes' => trim((string) ($meta['notes'] ?? '')) ?: null,
                'sold_at' => now(),
            ]);

            foreach ($lines as $line) {
                $model = $line['model'];
                $model->decrement('stock', $line['quantity']);

                $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'product_name' => $model instanceof Variant
                        ? $model->product?->name . ' - ' . $model->name
                        : $model->name,
                    'sku' => $model->sku,
                    'variant_name' => $model instanceof Variant ? $model->name : null,
                    'price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);

                $this->log->posOut(
                    $sale,
                    $line['variant_id'],
                    $line['product_id'],
                    $line['quantity'],
                    (int) $model->stock,
                    $kasir->id
                );
            }

            return $sale;
        });
    }

    /**
     * Batalkan transaksi POS: kembalikan stok jual + buku gudang,
     * tutup mutasi OUT, tandai status cancelled (idempoten).
     */
    public function cancel(PosSale $sale, ?string $reason = null, ?int $userId = null): void
    {
        if ($sale->status === PosSale::STATUS_CANCELLED) {
            return;
        }

        DB::transaction(function () use ($sale, $reason, $userId) {
            foreach ($sale->items()->get() as $item) {
                if ($item->variant_id) {
                    $model = Variant::lockForUpdate()->find($item->variant_id);
                    if (!$model) {
                        continue;
                    }
                    $model->increment('stock', $item->quantity);
                    $productId = $item->product_id;
                } else {
                    $model = $item->product_id ? Product::lockForUpdate()->find($item->product_id) : null;
                    if (!$model) {
                        continue;
                    }
                    $model->increment('stock', $item->quantity);
                    $productId = $model->id;
                }

                $this->log->posIn(
                    $sale,
                    $item->variant_id ? (int) $item->variant_id : null,
                    $productId ? (int) $productId : null,
                    (int) $item->quantity,
                    (int) $model->stock,
                    $userId
                );
            }

            $sale->update([
                'status' => PosSale::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'notes' => trim(($sale->notes ? $sale->notes . ' | ' : '') . 'Dibatalkan' . ($reason ? ': ' . $reason : '')),
            ]);
        });
    }

    /** Nomor invoice unik: POS-YYYYMMDD-XXXX (retry bila tabrakan). */
    public function nextInvoice(): string
    {
        $prefix = 'POS-' . now()->format('Ymd') . '-';

        for ($i = 0; $i < 10; $i++) {
            $candidate = $prefix . strtoupper(substr(str_replace('-', '', uniqid('', true)), -4));
            if (!PosSale::where('invoice', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix . strtoupper(bin2hex(random_bytes(2)));
    }

    private function labelFor(Product|Variant $model): string
    {
        if ($model instanceof Variant) {
            return ($model->product?->name ?? 'Produk') . ' - ' . $model->name;
        }

        return $model->name;
    }
}
