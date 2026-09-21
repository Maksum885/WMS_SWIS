<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Semua komponen sekarang didaftarkan manual lewat menu Component
        // Master (ASN wajib pilih dari sini, tidak bisa auto-create lagi),
        // jadi kolom source manual/asn tidak relevan lagi.
        Schema::table('wms_component_masters', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }

    public function down(): void
    {
        Schema::table('wms_component_masters', function (Blueprint $table) {
            $table->enum('source', ['manual', 'asn'])->default('manual');
        });
    }
};
