<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_putaway_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('putaway_id')->constrained('wms_putaways')->cascadeOnDelete();
            $table->foreignId('grn_line_id')->constrained('wms_grn_lines');
            $table->decimal('qty', 14, 2);
            // String, BUKAN foreign key: kode rak (mis. "R1C11") berasal dari koneksi
            // pgsql_agv yang terpisah (schema db_agv, read-only) — tidak bisa FK lintas
            // koneksi database. Validasi kevalidan kode dilakukan di service, bukan DB.
            $table->string('location_code');
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_putaway_lines');
    }
};
