<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_pickings', function (Blueprint $table) {
            $table->id();
            $table->string('picking_no')->unique();
            $table->foreignId('sales_order_id')->constrained('wms_sales_orders');
            $table->date('picking_date');
            $table->string('status')->default('pending'); // pending | completed
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_pickings');
    }
};
