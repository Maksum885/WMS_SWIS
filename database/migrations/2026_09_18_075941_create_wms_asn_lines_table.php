<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_asn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asn_id')->constrained('wms_asns')->cascadeOnDelete();
            // Komponen mentah dari supplier — kode+nama disimpan langsung di baris,
            // SAMA seperti pola warehouse_stock.component milik sistem lama (tidak ada
            // tabel master komponen formal di data existing, jadi mengikuti konvensi
            // yang sudah ada, bukan bikin master baru yang tidak nyata datanya).
            $table->string('component');
            $table->string('component_name')->nullable();
            $table->decimal('qty_expected', 14, 2);
            $table->string('uom');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_asn_lines');
    }
};
