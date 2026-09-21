<?php

namespace App\Services;

use App\Models\Inbound\PutawayLine;
use App\Models\MatrixStorageBin;
use App\Models\WarehouseStock;
use Illuminate\Support\Collection;

/**
 * Titik pusat query untuk modul monitoring WMS SWIS (dulu bernama "SWIS Inventory
 * Tracking" — diganti nama 2026-09-18, lalu jadi "SWISS WMS" lalu "WMS SWIS"
 * 2026-09-21, lihat PROJECT-NOTES.md).
 *
 * Pemetaan konsep dokumen -> data existing:
 * - "Item / SKU"     -> warehouse_stock.component (+ component_name, unit)
 * - "Box"            -> warehouse_stock.bin_name === db_agv.m_matrix_storage_bin.qr_id
 * - "Isi box"         -> SUM(qty) per (bin_name, component) dari ledger warehouse_stock, qty > 0
 * - "Rack"            -> m_matrix_storage_bin.side (R1..R8, 8 rack sesuai dokumen)
 * - "Kolom (1-9)"     -> m_matrix_storage_bin.column_no (C1..C9), C1 di KANAN s/d C9 di KIRI
 *   (tampak samping dari depan rak — sesuai foto referensi resmi gudang, 2026-09-09)
 * - "Layer (1-5)"     -> m_matrix_storage_bin.stack (1..5), Layer 1 di baris paling
 *   BAWAH s/d Layer 5 paling ATAS
 * - Kode slot **"R{rack}C{kolom}{layer}"** — digit kolom & layer digabung LANGSUNG
 *   tanpa huruf pemisah, mis. R1C11 = Rak1/Kolom1/Layer1, R1C95 = Rak1/Kolom9/Layer5.
 *   Parsing aman karena kolom selalu 1 digit (1-9) diikuti layer 1 digit (1-5).
 *   Sumber: legenda label resmi gudang ("KETERANGAN LABEL") yang dikirim user.
 *   Ini SUPERSEDES 2 hal dari instruksi sebelumnya (2026-09-09 lebih awal hari ini):
 *   (a) format lama "R{rack}C{kolom}L{layer}" (pakai huruf L) — DIGANTI, tanpa L;
 *   (b) urutan kolom lama (C1 kiri, C9 kanan) — DIBALIK jadi C1 kanan, C9 kiri.
 *   Dokumen PDF paling awal malah menulis format ketiga "R{rack}-H{h}-V{v}" — sudah
 *   tidak dipakai lagi sejak revisi pertama.
 *
 * KETERBATASAN DATA: tabel yang idealnya jadi log riwayat pergerakan box<->rack
 * (db_agv.t_status_rack, t_task_order, t_status_agv) KOSONG di snapshot dev ini —
 * kemungkinan karena dump diambil saat robot idle, atau memang belum dipakai live.
 * "Riwayat Transaksi" karena itu didekati dari ledger warehouse_stock (remark IN/OUT),
 * dengan asumsi rack/slot SAAT INI (bukan rack/slot pada waktu transaksi itu terjadi).
 * Perlu dikonfirmasi ke programmer apakah t_status_rack terisi saat sistem live.
 */
class RackTrackingService
{
    /** 8 rack resmi sesuai dokumen. Side lain (RA staging, CONVE1/2 conveyor) dikecualikan. */
    public const RACKS = ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7', 'R8'];

    public const COLUMNS = 9; // H1..H9
    public const ROWS = 5;    // V1..V5
    public const TOTAL_SLOTS = 360;

    public function slotCode(MatrixStorageBin $bin): string
    {
        $col = (int) str_replace('C', '', (string) $bin->column_no);

        return "{$bin->side}C{$col}{$bin->stack}";
    }

    /**
     * Semua 360 kode slot yang mungkin (R{rack}C{kolom}{layer}), dihitung dari
     * konstanta — TIDAK query live ke pgsql_agv. Dipakai sebagai daftar pilihan
     * lokasi di form WMS Put Away/Picking (referensi kode saja, lihat catatan di
     * DATABASE.md soal kenapa location_code bukan FK). Sengaja tidak difilter
     * status FULL/EMPTY karena itu status box robot AGV, domain berbeda dari WMS.
     */
    public function allLocationCodes(): array
    {
        $codes = [];
        foreach (self::RACKS as $rack) {
            for ($col = 1; $col <= self::COLUMNS; $col++) {
                for ($layer = 1; $layer <= self::ROWS; $layer++) {
                    $codes[] = "{$rack}C{$col}{$layer}";
                }
            }
        }

        return $codes;
    }

    /**
     * Kode slot yang STATUS-nya benar-benar EMPTY saat ini (baca live dari
     * m_matrix_storage_bin, read-only). Dipakai sebagai pilihan lokasi tujuan
     * di form Put Away — supaya staf tidak bisa "konfirmasi" naruh barang di
     * slot yang sebenarnya sudah terisi secara fisik. Ditambahkan 2026-09-20
     * setelah dicek langsung ke source code aplikasi WinForms (Mainform) yang
     * mengendalikan robot AGV — robotnya sendiri TIDAK dikontrol dari Laravel
     * ini (lihat DATABASE.md/PROJECT-NOTES.md), tapi WMS tetap bisa membaca
     * status live-nya untuk validasi, tanpa menulis apa pun ke robot.
     */
    public function emptyLocationCodes(): array
    {
        return MatrixStorageBin::whereIn('side', self::RACKS)
            ->where('status', 'EMPTY')
            ->get()
            ->map(fn (MatrixStorageBin $bin) => $this->slotCode($bin))
            ->values()
            ->all();
    }

    /**
     * Kode slot yang STATUS-nya FULL saat ini — dipakai sebagai pilihan lokasi
     * ASAL di form Picking (cuma masuk akal ambil barang dari slot yang
     * memang terisi). Sama prinsipnya dengan emptyLocationCodes() di atas.
     */
    public function occupiedLocationCodes(): array
    {
        return MatrixStorageBin::whereIn('side', self::RACKS)
            ->where('status', 'FULL')
            ->get()
            ->map(fn (MatrixStorageBin $bin) => $this->slotCode($bin))
            ->values()
            ->all();
    }

    /**
     * Daftar component yang SUDAH NYATA ada di warehouse_stock — dipakai sebagai
     * saran/datalist di form ASN & GRN (Inbound), supaya staf/mahasiswa terarah ke
     * kode komponen yang sungguhan dipakai gudang SWIS ini, bukan mengarang kode
     * baru sembarangan (mis. contoh dari video/PDF referensi WMS lain yang TIDAK
     * ada padanannya di sini). Tetap berupa saran, bukan FK — komponen baru yang
     * memang belum pernah tercatat masih boleh diketik manual (gudang bisa
     * menerima jenis barang baru), tapi defaultnya mengarahkan ke data nyata.
     */
    public function knownComponents(): Collection
    {
        return WarehouseStock::query()
            ->whereNotNull('component')
            ->where('component', '!=', '')
            ->stockSummary()
            ->orderBy('component')
            ->get();
    }

    /**
     * Rekap qty yang sudah di-Put Away lewat WMS, per komponen (+ breakdown per
     * lokasi) — dipakai sebagai tab "D · WMS Put Away Summary" di Reports.
     * SENGAJA digabung ke Reports (bukan menu sendiri) atas permintaan user:
     * ini laporan/agregat read-only, bukan langkah kerja seperti ASN/GRN/Put
     * Away, jadi tempatnya memang di Reports, bukan sidebar Inbound.
     *
     * PENTING: ini BUKAN stok fisik yang sebenarnya — cuma total yang sudah
     * tercatat lewat ASN→GRN→Put Away di WMS ini, tidak memotong pemakaian/
     * konsumsi (itu domain Report A · Stock per Item yang baca warehouse_stock
     * asli). Dua angka ini BISA beda untuk component yang sama, bukan bug.
     */
    public function putAwaySummary(?string $search = null): Collection
    {
        return PutawayLine::query()
            ->join('wms_grn_lines', 'wms_putaway_lines.grn_line_id', '=', 'wms_grn_lines.id')
            ->selectRaw('wms_grn_lines.component, wms_grn_lines.component_name, wms_grn_lines.uom, SUM(wms_putaway_lines.qty) as qty_put_away')
            ->when($search, function ($q) use ($search) {
                $q->where(fn ($qq) => $qq->where('wms_grn_lines.component', 'ilike', "%{$search}%")
                    ->orWhere('wms_grn_lines.component_name', 'ilike', "%{$search}%"));
            })
            ->groupBy('wms_grn_lines.component', 'wms_grn_lines.component_name', 'wms_grn_lines.uom')
            ->orderBy('wms_grn_lines.component')
            ->get();
    }

    /** Breakdown lokasi untuk putAwaySummary() — dikelompokkan per component. */
    public function putAwaySummaryByLocation(): Collection
    {
        return PutawayLine::query()
            ->join('wms_grn_lines', 'wms_putaway_lines.grn_line_id', '=', 'wms_grn_lines.id')
            ->selectRaw('wms_grn_lines.component, wms_putaway_lines.location_code, SUM(wms_putaway_lines.qty) as qty')
            ->groupBy('wms_grn_lines.component', 'wms_putaway_lines.location_code')
            ->havingRaw('SUM(wms_putaway_lines.qty) > 0')
            ->get()
            ->groupBy('component');
    }

    /** Semua slot dalam satu rack (R1..R8), terurut H lalu V. */
    public function slotsForRack(string $rack): Collection
    {
        return MatrixStorageBin::where('side', $rack)
            ->get()
            ->sortBy([
                fn ($b) => (int) str_replace('C', '', (string) $b->column_no),
                fn ($b) => (int) $b->stack,
            ])
            ->values();
    }

    /** Isi box saat ini (qr_id) -> koleksi item (component, component_name, unit, qty). */
    public function boxContents(string $qrId): Collection
    {
        return WarehouseStock::selectRaw('component, component_name, unit, SUM(qty) as qty')
            ->where('bin_name', $qrId)
            ->groupBy('component', 'component_name', 'unit')
            ->havingRaw('SUM(qty) > 0')
            ->get();
    }

    /** Cari lokasi berdasarkan kode box (qr_id) ATAU nama/kode item (component/component_name). */
    public function search(string $query): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        // 1) Cocok langsung sebagai kode box.
        $byBox = MatrixStorageBin::whereRaw('qr_id ILIKE ?', ["%{$query}%"])
            ->whereIn('side', self::RACKS)
            ->get();

        if ($byBox->isNotEmpty()) {
            return $byBox;
        }

        // 2) Cocok sebagai nama/kode item -> ambil semua box yang menyimpannya, lalu cari slotnya.
        $boxIds = WarehouseStock::where(function ($q) use ($query) {
            $q->where('component', 'ilike', "%{$query}%")
                ->orWhere('component_name', 'ilike', "%{$query}%");
        })
            ->whereNotNull('bin_name')->where('bin_name', '!=', '')
            ->groupBy('bin_name')
            ->havingRaw('SUM(qty) > 0')
            ->pluck('bin_name');

        if ($boxIds->isEmpty()) {
            return collect();
        }

        return MatrixStorageBin::whereIn('qr_id', $boxIds)
            ->whereIn('side', self::RACKS)
            ->get();
    }

    /** Kartu ringkasan dashboard. */
    public function dashboardCards(): array
    {
        $totalItem = WarehouseStock::whereNotNull('component')->where('component', '!=', '')
            ->distinct('component')->count('component');

        $totalSlotFilled = MatrixStorageBin::whereIn('side', self::RACKS)->where('status', 'FULL')->count();

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $boxInToday = WarehouseStock::whereDate('update_date', $today)->where('remark', 'IN')
            ->distinct('bin_name')->count('bin_name');
        $boxOutToday = WarehouseStock::whereDate('update_date', $today)->where('remark', 'OUT')
            ->distinct('bin_name')->count('bin_name');
        $boxInYesterday = WarehouseStock::whereDate('update_date', $yesterday)->where('remark', 'IN')
            ->distinct('bin_name')->count('bin_name');
        $boxOutYesterday = WarehouseStock::whereDate('update_date', $yesterday)->where('remark', 'OUT')
            ->distinct('bin_name')->count('bin_name');

        return [
            'total_item' => $totalItem,
            'total_box_aktif' => $totalSlotFilled, // 1 slot = 1 box
            'slot_terisi' => $totalSlotFilled,
            'total_slot' => self::TOTAL_SLOTS,
            'okupansi_pct' => self::TOTAL_SLOTS > 0 ? round($totalSlotFilled / self::TOTAL_SLOTS * 100, 1) : 0,
            'box_in_today' => $boxInToday,
            'box_out_today' => $boxOutToday,
            'box_in_yesterday' => $boxInYesterday,
            'box_out_yesterday' => $boxOutYesterday,
        ];
    }

    /**
     * Item dengan qty_total TERENDAH (tapi tetap > 0, lihat havingRaw di
     * stockPerItem) — dipakai kartu "Items Running Low" di dashboard. Tidak
     * ada kolom minimum-stock di database manapun, jadi "low" di sini murni
     * relatif terhadap item lain (stok paling sedikit saat ini), bukan
     * dibandingkan ambang batas buatan.
     */
    public function lowestItemByStock(): ?object
    {
        return $this->stockPerItem(null, null, 'qty', 'asc')->first();
    }

    /**
     * Ringkasan per item untuk panel "Item Overview" di dashboard: qty saat ini + jumlah
     * pergerakan (transaksi in+out) dalam N hari terakhir, diurutkan dari paling aktif.
     * Beda fokus dari Transaction History (per-transaksi, kronologis) — ini per-item,
     * gabungan snapshot stok + seberapa sering item itu bergerak.
     */
    public function itemOverview(int $days, int $limit = 8): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $movementCounts = WarehouseStock::selectRaw('component, COUNT(*) as movement_count')
            ->whereNotNull('component')->where('component', '!=', '')
            ->whereIn('remark', ['IN', 'OUT'])
            ->where('update_date', '>=', $start)
            ->groupBy('component')
            ->pluck('movement_count', 'component');

        return $this->stockPerItem()
            ->map(function ($item) use ($movementCounts) {
                $item->movement_count = (int) ($movementCounts[$item->component] ?? 0);

                return $item;
            })
            ->sortByDesc('movement_count')
            ->values()
            ->take($limit);
    }

    /** Jumlah slot terisi per rack (untuk bar chart utilisasi), urut R1..R8. */
    public function rackUtilization(): Collection
    {
        $filled = MatrixStorageBin::whereIn('side', self::RACKS)
            ->where('status', 'FULL')
            ->selectRaw('side, COUNT(*) as filled')
            ->groupBy('side')
            ->pluck('filled', 'side');

        return collect(self::RACKS)->map(fn ($rack) => [
            'rack' => $rack,
            'filled' => (int) ($filled[$rack] ?? 0),
            'capacity' => self::ROWS * self::COLUMNS,
            'pct' => round((($filled[$rack] ?? 0) / (self::ROWS * self::COLUMNS)) * 100, 1),
        ]);
    }

    /** Tren jumlah box masuk/keluar N hari terakhir (untuk line chart), distinct box per hari. */
    public function inOutTrend(int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = WarehouseStock::selectRaw('DATE(update_date) as tgl, remark, COUNT(DISTINCT bin_name) as jml')
            ->where('update_date', '>=', $start)
            ->whereIn('remark', ['IN', 'OUT'])
            ->groupBy('tgl', 'remark')
            ->get();

        $labels = [];
        $in = [];
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->translatedFormat('D');
            $in[] = (int) $rows->firstWhere(fn ($r) => $r->tgl === $date && $r->remark === 'IN')?->jml ?? 0;
            $out[] = (int) $rows->firstWhere(fn ($r) => $r->tgl === $date && $r->remark === 'OUT')?->jml ?? 0;
        }

        return compact('labels', 'in', 'out');
    }

    /**
     * Laporan stok per item: SKU, nama, qty total, daftar rack tempat tersebar.
     * Bisa difilter per rack & kata kunci, dan diurutkan — dipakai baik untuk
     * tampilan layar MAUPUN export (Excel/PDF), supaya hasil unduhan konsisten
     * dengan apa yang sedang difilter/diurutkan user di layar.
     */
    public function stockPerItem(?string $rackFilter = null, ?string $search = null, string $sortBy = 'sku', string $sortDir = 'asc'): Collection
    {
        $sortColumn = match ($sortBy) {
            'qty' => 'qty_total',
            'nama' => 'component_name',
            default => 'component',
        };

        $items = WarehouseStock::selectRaw('component, component_name, unit, SUM(qty) as qty_total')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('component', 'ilike', "%{$search}%")
                        ->orWhere('component_name', 'ilike', "%{$search}%");
                });
            })
            ->groupBy('component', 'component_name', 'unit')
            ->havingRaw('SUM(qty) > 0')
            ->orderBy($sortColumn, $sortDir)
            ->get();

        // Peta bin_name -> rack (side), dari data rak saat ini.
        $binToRack = MatrixStorageBin::whereIn('side', self::RACKS)
            ->whereNotNull('qr_id')->where('qr_id', '!=', '')
            ->pluck('side', 'qr_id');

        $result = $items->map(function ($item) use ($binToRack) {
            $boxes = WarehouseStock::where('component', $item->component)
                ->whereNotNull('bin_name')->where('bin_name', '!=', '')
                ->groupBy('bin_name')
                ->havingRaw('SUM(qty) > 0')
                ->pluck('bin_name');

            $racks = $boxes->map(fn ($box) => $binToRack[$box] ?? null)->filter()->unique()->sort()->values();

            $item->racks = $racks;

            return $item;
        });

        // Filter per rack diterapkan setelah $racks dihitung (butuh join manual di atas,
        // tidak praktis dilakukan langsung lewat SQL WHERE).
        if ($rackFilter) {
            $result = $result->filter(fn ($item) => $item->racks->contains($rackFilter))->values();
        }

        return $result;
    }

    /**
     * Laporan C: ringkasan mutasi (total qty masuk/keluar) per item dalam rentang tanggal.
     * Basis datanya sama dengan transactionHistory() (ledger warehouse_stock), hanya saja
     * diagregasi per item alih-alih ditampilkan per baris transaksi.
     */
    public function mutationSummary(
        ?string $dateFrom,
        ?string $dateTo,
        ?string $rackFilter = null,
        ?string $search = null,
        string $sortBy = 'sku',
        string $sortDir = 'asc'
    ): Collection {
        $binToRack = MatrixStorageBin::whereIn('side', self::RACKS)
            ->whereNotNull('qr_id')->where('qr_id', '!=', '')
            ->pluck('side', 'qr_id');

        $rows = WarehouseStock::selectRaw('component, component_name, unit, bin_name, remark, qty')
            ->whereNotNull('component')->where('component', '!=', '')
            ->whereNotNull('bin_name')->where('bin_name', '!=', '')
            ->whereIn('remark', ['IN', 'OUT'])
            ->when($dateFrom, fn ($q) => $q->whereDate('update_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('update_date', '<=', $dateTo))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('component', 'ilike', "%{$search}%")
                        ->orWhere('component_name', 'ilike', "%{$search}%");
                });
            })
            ->when($rackFilter, function ($q) use ($binToRack, $rackFilter) {
                $binsInRack = $binToRack->filter(fn ($side) => $side === $rackFilter)->keys();
                $q->whereIn('bin_name', $binsInRack);
            })
            ->get();

        $summary = $rows->groupBy('component')
            ->map(function ($group) {
                $first = $group->first();
                $masuk = (int) $group->where('remark', 'IN')->sum('qty');
                $keluar = (int) abs($group->where('remark', 'OUT')->sum('qty'));

                return (object) [
                    'component' => $first->component,
                    'component_name' => $first->component_name,
                    'unit' => $first->unit,
                    'total_masuk' => $masuk,
                    'total_keluar' => $keluar,
                    'net' => $masuk - $keluar,
                    'jumlah_box' => $group->pluck('bin_name')->unique()->count(),
                ];
            });

        $sortColumn = match ($sortBy) {
            'nama' => 'component_name',
            'masuk' => 'total_masuk',
            'keluar' => 'total_keluar',
            'net' => 'net',
            'box' => 'jumlah_box',
            default => 'component',
        };

        $sorted = $sortDir === 'desc' ? $summary->sortByDesc($sortColumn) : $summary->sortBy($sortColumn);

        return $sorted->values();
    }

    /**
     * Riwayat pergerakan box (pendekatan dari ledger item, lihat catatan keterbatasan
     * di atas kelas ini). Filter: tanggal dari/sampai, rack, jenis event.
     */
    public function transactionHistory(
        ?string $dateFrom,
        ?string $dateTo,
        ?string $rack,
        ?string $event,
        int $perPage = 10,
        string $sort = 'waktu',
        string $dir = 'desc'
    ) {
        $binToRack = MatrixStorageBin::whereIn('side', self::RACKS)
            ->whereNotNull('qr_id')->where('qr_id', '!=', '')
            ->get()
            ->keyBy('qr_id');

        $query = WarehouseStock::whereNotNull('bin_name')->where('bin_name', '!=', '')
            ->whereIn('remark', ['IN', 'OUT'])
            ->when($dateFrom, fn ($q) => $q->whereDate('update_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('update_date', '<=', $dateTo))
            ->when($event, fn ($q) => $q->where('remark', $event))
            // Filter rack diterapkan lewat daftar bin_name yang SAAT INI ada di rack tsb
            // (keterbatasan: posisi box bisa saja sudah berpindah sejak transaksi terjadi).
            ->when($rack, function ($q) use ($binToRack, $rack) {
                $binsInRack = $binToRack->filter(fn ($bin) => $bin->side === $rack)->keys();
                $q->whereIn('bin_name', $binsInRack);
            });

        // Klik header "Waktu" -> urut update_date. Klik header "Event" -> kelompokkan
        // remark (IN/OUT) dulu, lalu waktu terbaru sebagai tie-breaker biar tetap rapi.
        $sortColumn = $sort === 'event' ? 'remark' : 'update_date';
        $query->orderBy($sortColumn, $dir);
        if ($sortColumn !== 'update_date') {
            $query->orderByDesc('update_date');
        }

        $paginated = $query->paginate($perPage)->withQueryString();

        $paginated->getCollection()->transform(function ($row) use ($binToRack) {
            $bin = $binToRack[$row->bin_name] ?? null;
            $row->rack_slot = $bin ? $this->slotCode($bin) : null;
            $row->rack = $bin?->side;

            return $row;
        });

        return $paginated;
    }
}
