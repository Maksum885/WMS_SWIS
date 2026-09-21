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
     * po_number = referensi nomor Purchase Order internal gudang ke supplier —
     * BEDA dari asn_no (nomor dokumen ASN itu sendiri). Cuma teks bebas
     * (nullable), TIDAK divalidasi/di-FK ke tabel PO manapun — sistem ini
     * tidak punya modul Purchasing sendiri, jadi field ini murni catatan
     * referensi untuk staf, sama seperti lot_no di GRN.
     */
    public function up(): void
    {
        Schema::table('wms_asns', function (Blueprint $table) {
            $table->string('po_number')->nullable()->after('asn_no');
        });
    }

    public function down(): void
    {
        Schema::table('wms_asns', function (Blueprint $table) {
            $table->dropColumn('po_number');
        });
    }
};
