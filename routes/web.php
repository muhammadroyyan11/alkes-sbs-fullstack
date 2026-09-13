<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Front;

Route::get('/', [Front\HomeController::class, 'index'])->name('home');
Route::get('/produk', [Front\ProductController::class, 'index'])->name('products.index');
Route::get('/produk/{product:sku}', [Front\ProductController::class, 'show'])->name('products.show');
Route::get('/keranjang', [Front\CartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/{product}', [Front\CartController::class, 'store'])->name('cart.store');
Route::patch('/keranjang', [Front\CartController::class, 'update'])->name('cart.update');
Route::delete('/keranjang/{key}', [Front\CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/checkout', [Front\CheckoutController::class, 'index'])->middleware('auth')->name('checkout.index');
Route::post('/checkout', [Front\CheckoutController::class, 'store'])->middleware('auth')->name('checkout.store');
Route::middleware('auth')->prefix('akun')->name('account.')->group(function(){Route::get('/',[Front\AccountController::class,'index'])->name('index');Route::patch('/',[Front\AccountController::class,'update'])->name('update');Route::post('/alamat',[Front\AddressController::class,'store'])->name('addresses.store');Route::put('/alamat/{address}',[Front\AddressController::class,'update'])->name('addresses.update');Route::delete('/alamat/{address}',[Front\AddressController::class,'destroy'])->name('addresses.destroy');});
Route::middleware('auth')->group(function(){Route::get('/pesanan',[Front\OrderController::class,'index'])->name('orders.index');Route::get('/pesanan/{order}',[Front\OrderController::class,'show'])->name('orders.show');});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('dashboard');

require __DIR__ . '/auth.php';

// Admin Panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // ── Datatable endpoints ──
    Route::get('products/datatable', [Admin\ProductController::class, 'datatable'])->name('products.datatable');
    Route::get('variants/datatable', [Admin\VariantController::class, 'datatable'])->name('variants.datatable');
    Route::get('stocks/datatable', [Admin\StockController::class, 'datatable'])->name('stocks.datatable');
    Route::get('stock-opnames/datatable', [Admin\StockOpnameController::class, 'datatable'])->name('stock-opnames.datatable');
    Route::get('users/datatable', [Admin\UserController::class, 'datatable'])->name('users.datatable');
    Route::get('suppliers/datatable', [Admin\SupplierController::class, 'datatable'])->name('suppliers.datatable');
    Route::get('purchase-orders/datatable', [Admin\PurchaseOrderController::class, 'datatable'])->name('purchase-orders.datatable');
    Route::get('purchase-receives/datatable', [Admin\PurchaseReceiveController::class, 'datatable'])->name('purchase-receives.datatable');

    // ── Resource routes ──
    Route::resource('products', Admin\ProductController::class)->except(['show']);
    Route::resource('variants', Admin\VariantController::class)->except(['show']);
    Route::resource('users', Admin\UserController::class)->except(['show']);
    Route::resource('suppliers', Admin\SupplierController::class)->except(['show']);

    // ── Stok ──
    Route::get('stocks', [Admin\StockController::class, 'index'])->name('stocks.index');
    Route::get('stocks/{stock}', [Admin\StockController::class, 'show'])->name('stocks.show');

    // ── Stok Opname ──
    Route::resource('stock-opnames', Admin\StockOpnameController::class)->except(['edit', 'update', 'destroy']);
    Route::post('stock-opnames/{stockOpname}/count', [Admin\StockOpnameController::class, 'count'])->name('stock-opnames.count');
    Route::post('stock-opnames/{stockOpname}/approve', [Admin\StockOpnameController::class, 'approve'])->name('stock-opnames.approve');
    Route::post('stock-opnames/{stockOpname}/reject', [Admin\StockOpnameController::class, 'reject'])->name('stock-opnames.reject');

    // ── Purchase Order ──
    Route::resource('purchase-orders', Admin\PurchaseOrderController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchase-orders/{purchaseOrder}/send', [Admin\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [Admin\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // ── Purchase Receive ──
    Route::resource('purchase-receives', Admin\PurchaseReceiveController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchase-receives/{purchaseReceive}/approve', [Admin\PurchaseReceiveController::class, 'approve'])->name('purchase-receives.approve');
    Route::post('purchase-receives/{purchaseReceive}/reject', [Admin\PurchaseReceiveController::class, 'reject'])->name('purchase-receives.reject');

    // ── Website Settings ──
    Route::get('website', [Admin\WebsiteController::class, 'edit'])->name('website.edit');
    Route::put('website', [Admin\WebsiteController::class, 'update'])->name('website.update');
});

// Profile & Password
Route::middleware('auth')->group(function () {
    Route::get('profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});
