<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prosedur refund:
 *  - users.bank_account : nomor rekening penerima (diisi customer dari halaman Akun)
 *  - orders.refund_proof : bukti transfer pengembalian dana (diunggah admin saat refund)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_account', 50)->nullable()->after('avatar');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_proof')->nullable()->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refund_proof');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bank_account');
        });
    }
};
