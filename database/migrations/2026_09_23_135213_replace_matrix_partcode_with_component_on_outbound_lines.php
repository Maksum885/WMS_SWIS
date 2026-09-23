<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * matrix_partcode dulu dipakai sebagai katalog "produk jadi" terpisah untuk
     * Outbound (SO/Picking/DO), TAPI di operasional nyata tabel itu tidak pernah
     * terisi lewat jalur manapun — satu-satunya data yang benar-benar mengalir ke
     * sistem adalah barang yang sudah di-scan & dikirim ke rack (warehouse_stock).
     * Jadi Outbound sekarang ikut pola yang sama dengan Inbound: `component` +
     * `component_name` string biasa, sumbernya stok riil (RackTrackingService::
     * stockPerItem()), bukan lagi FK ke matrix_partcode. Lihat PROJECT-NOTES.md.
     */
    private array $tables = ['wms_so_lines', 'wms_picking_lines', 'wms_do_lines'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('component')->nullable()->after('id');
                $t->string('component_name')->nullable()->after('component');
            });

            // Backfill dari data existing (kalau ada) supaya riwayat lama tidak hilang.
            DB::statement("
                update {$table} as line
                set component = mp.partcode, component_name = mp.model_name
                from matrix_partcode as mp
                where line.matrix_partcode_id = mp.id
            ");

            DB::table($table)->whereNull('component')->update(['component' => '—']);

            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('matrix_partcode_id');
                $t->string('component')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('matrix_partcode_id')->nullable()->after('id')->constrained('matrix_partcode');
            });

            DB::statement("
                update {$table} as line
                set matrix_partcode_id = mp.id
                from matrix_partcode as mp
                where line.component = mp.partcode
            ");

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['component', 'component_name']);
            });
        }
    }
};
