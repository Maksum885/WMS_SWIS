<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * serial_no & pallet_id dihapus — tidak ada padanannya di sistem nyata (unit
     * fisik gudang ini adalah "box" dengan QR code / bin_name, bukan barang
     * ter-serialize satuan atau pallet). Dua kolom ini kebawa dari contoh PDF WMS
     * Polibatam lain saat desain awal, ternyata tidak sesuai kondisi lapangan.
     * lot_no & expired_date TETAP dipakai karena relevan untuk barang konsumen
     * (FaceWash/Shampoo/dst punya nomor batch & tanggal kadaluarsa nyata).
     */
    public function up(): void
    {
        Schema::table('wms_grn_lines', function (Blueprint $table) {
            $table->dropColumn(['serial_no', 'pallet_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wms_grn_lines', function (Blueprint $table) {
            $table->string('serial_no')->nullable();
            $table->string('pallet_id')->nullable();
        });
    }
};
