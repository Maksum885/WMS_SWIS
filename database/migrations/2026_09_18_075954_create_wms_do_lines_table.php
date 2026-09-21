<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_do_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('wms_delivery_orders')->cascadeOnDelete();
            $table->foreignId('matrix_partcode_id')->constrained('matrix_partcode');
            $table->decimal('qty_delivered', 14, 2);
            $table->string('uom');
            $table->string('lot_no')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_do_lines');
    }
};
