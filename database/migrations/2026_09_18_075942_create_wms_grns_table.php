<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no')->unique();
            // Nullable: bisa "blind receive" tanpa ASN sebelumnya (dokumen contoh PDF Order Ref-nya "NA").
            $table->foreignId('asn_id')->nullable()->constrained('wms_asns')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('wms_suppliers');
            $table->date('received_date');
            $table->string('transport_mode')->nullable(); // ROAD/SEA/AIR, sesuai contoh PDF
            $table->string('vehicle_no')->nullable();
            $table->string('status')->default('draft'); // draft | completed
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_grns');
    }
};
