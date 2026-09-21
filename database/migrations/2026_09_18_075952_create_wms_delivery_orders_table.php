<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_no')->unique();
            $table->foreignId('sales_order_id')->constrained('wms_sales_orders');
            $table->foreignId('picking_id')->nullable()->constrained('wms_pickings')->nullOnDelete();
            $table->date('delivery_date');
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('status')->default('draft'); // draft | shipped | delivered
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_delivery_orders');
    }
};
