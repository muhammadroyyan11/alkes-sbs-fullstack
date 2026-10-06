<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            // status log keluar-masuk barang:
            // waiting_for_checkout (OUT saat checkout) -> completed (dibayar)
            // atau -> cancelled (IN - cancel transaction)
            $table->string('status')->default('completed')->after('type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropColumn('status');
        });
    }
};
