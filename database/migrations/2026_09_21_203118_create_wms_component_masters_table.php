<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_component_masters', function (Blueprint $table) {
            $table->id();
            // Katalog komponen/item — beda tujuan dari `component`/`component_name`
            // bebas di wms_asn_lines: ini referensi kanonik (1 kode = 1 nama tetap)
            // yang dipakai buat cetak label barcode untuk pendaftaran ke aplikasi
            // mainform, jadi SENGAJA unique per component_code (bukan mengulang
            // wms_parts yang dulu dihapus — itu untuk normalisasi internal yang
            // memang tidak perlu, ini untuk kebutuhan baru: master data eksternal).
            $table->string('component_code')->unique();
            $table->string('component_name');
            $table->string('default_uom')->default('Pcs');
            // Qty tetap yang ikut dicetak di barcode label (mis. isi per dus/pack),
            // bukan qty transaksi — diisi manual, dipakai berulang tiap cetak.
            $table->decimal('qty_label', 14, 2)->default(0);
            // 'manual' = didaftarkan langsung di menu ini, 'asn' = otomatis
            // kebentuk saat kode baru diketik pertama kali di form ASN.
            $table->enum('source', ['manual', 'asn'])->default('manual');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_component_masters');
    }
};
