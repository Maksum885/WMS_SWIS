<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('wms_grns')->cascadeOnDelete();
            // Sama seperti wms_asn_lines: kode+nama komponen langsung di baris,
            // mengikuti konvensi warehouse_stock.component milik sistem lama.
            $table->string('component');
            $table->string('component_name')->nullable();
            $table->decimal('qty_received', 14, 2);
            $table->string('uom');
            // Field traceability sesuai contoh dokumen (lot/serial/expiry/pallet).
            $table->string('lot_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('pallet_id')->nullable();
            $table->string('status')->default('A'); // A = Active/Approved, sesuai contoh PDF
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_grn_lines');
    }
};
