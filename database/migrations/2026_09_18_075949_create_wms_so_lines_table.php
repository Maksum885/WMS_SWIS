<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_so_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('wms_sales_orders')->cascadeOnDelete();
            // Produk jadi — FK LANGSUNG ke matrix_partcode yang sudah nyata ada di
            // sistem lama (bukan master part baru yang fiktif dari contoh PDF).
            $table->foreignId('matrix_partcode_id')->constrained('matrix_partcode');
            $table->decimal('qty_ordered', 14, 2);
            $table->string('uom');
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_so_lines');
    }
};
