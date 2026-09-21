<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_picking_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('picking_id')->constrained('wms_pickings')->cascadeOnDelete();
            $table->foreignId('so_line_id')->constrained('wms_so_lines');
            $table->foreignId('matrix_partcode_id')->constrained('matrix_partcode');
            $table->decimal('qty_picked', 14, 2);
            $table->string('lot_no')->nullable();
            // Sama seperti putaway_lines.location_code: string biasa, bukan FK
            // (kode rak berasal dari koneksi pgsql_agv yang terpisah).
            $table->string('location_code')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_picking_lines');
    }
};
