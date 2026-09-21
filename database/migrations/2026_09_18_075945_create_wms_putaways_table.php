<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_putaways', function (Blueprint $table) {
            $table->id();
            $table->string('putaway_no')->unique();
            $table->foreignId('grn_id')->constrained('wms_grns');
            $table->date('putaway_date');
            $table->string('status')->default('pending'); // pending | completed
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_putaways');
    }
};
