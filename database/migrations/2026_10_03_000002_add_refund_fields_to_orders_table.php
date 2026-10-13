<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prosedur refund: mencatat pengembalian dana ke pembeli
 * (waktu, alasan, dan admin yang memproses).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->string('refund_reason', 300)->nullable()->after('refunded_at');
            $table->foreignId('refunded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn(['refunded_at', 'refund_reason']);
        });
    }
};
