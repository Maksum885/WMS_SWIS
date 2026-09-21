<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_no')->unique();
            $table->foreignId('customer_id')->constrained('wms_customers');
            $table->date('order_date');
            $table->date('required_date')->nullable();
            $table->string('status')->default('draft'); // draft | confirmed | picking | delivered | closed | cancelled
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_sales_orders');
    }
};
