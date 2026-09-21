<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_asns', function (Blueprint $table) {
            $table->id();
            $table->string('asn_no')->unique();
            $table->foreignId('supplier_id')->constrained('wms_suppliers');
            $table->date('expected_date')->nullable();
            $table->string('status')->default('draft'); // draft | confirmed | closed | cancelled
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_asns');
    }
};
