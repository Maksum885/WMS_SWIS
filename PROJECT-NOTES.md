# SWISS WMS

> **Ganti nama (2026-09-18)**: aplikasi ini sebelumnya bernama "SWIS Inventory
> Tracking". Diganti jadi **SWISS WMS** ("SWISS" — dobel-S, mengikuti penamaan folder
> project `SWISS-POLIBATAM`, bukan typo) karena scope aplikasi sudah berkembang dari
> sekadar monitoring read-only jadi juga mencakup modul operasional (ASN, GRN, Put
> Away, Sales Order, Picking, Delivery Order — lihat bagian "Modul WMS" di bawah).
> Nama fasilitas fisik gudangnya sendiri (dari dokumen spesifikasi awal) tetap "SWIS"
> (Smart Warehouse Integrated System, satu-S) — itu nama tempat, bukan nama aplikasi
> ini, jadi tidak diubah di kutipan/rujukan historis.

**Laravel + PostgreSQL**, dua bagian:
1. **Modul monitoring** (read-only) — tracking item, box, dan slot rack pada Smart
   Warehouse Integrated System (SWIS), fasilitas pembelajaran mahasiswa Politeknik
   Batam. Ini bagian asli aplikasi ini, 4 menu resmi (lihat di bawah).
2. **Modul WMS** (baru, 2026-09-18/19, TERTULIS ke database) — alur operasional
   gudang penuh: ASN → GRN → Put Away → On Hand (inbound), Sales Order → Picking →
   Delivery Order (outbound). **Inbound dan Outbound sudah sama-sama punya UI
   lengkap** (controller+route+view, sidebar "WMS · Inbound" & "WMS · Outbound"),
   sudah diuji end-to-end. Lihat bagian "Modul WMS" di `DATABASE.md` untuk detail
   skema, dan cari "Modul WMS" di file ini untuk keputusan desainnya.

Project **terpisah** dari aplikasi desktop WinForms (C#) di `../Mainform` — keduanya
cuma berbagi database PostgreSQL yang sama, tidak digabung.

Spesifikasi resmi ada di `SWIS-Inventory-Tracking-Dokumentasi.pdf` (dikirim user) dan
mockup desain di `swis-inventory-mockup.html` — layout/warna/font pada layout Blade
(`resources/views/layouts/app.blade.php`) mengikuti mockup itu secara sengaja (IBM Plex
Sans/Mono, sidebar gelap, Chart.js).

## Prinsip inti (dari dokumentasi resmi — JANGAN dilanggar)

- **Read-only sepenuhnya.** Tidak ada form input manual, tidak ada menu Master Data,
  tidak ada halaman login/role. Semua data dibaca dari database yang sudah ditulis oleh
  sistem AGV/ACR existing.
- 4 menu resmi saja: **Dashboard, Rack Monitoring, Riwayat Transaksi, Laporan**.
- Cakupan gudang: **8 rack (R1-R8) × 45 slot (9 kolom × 5 layer) = 360 slot**.
  Kode slot **FINAL (update 2026-09-09 sore, berdasarkan foto label resmi gudang
  yang dikirim user — "KETERANGAN LABEL")**: **`R{rack}C{kolom}{layer}`** — digit
  kolom & layer digabung LANGSUNG tanpa huruf pemisah, mis. `R1C11` = Rak1/Kolom1/
  Layer1, `R1C95` = Rak1/Kolom9/Layer5. **Kolom 1 di KANAN, Kolom 9 di KIRI** (tampak
  dari depan rak), **Layer 1 di BAWAH, Layer 5 di ATAS**.
  Riwayat revisi format kode (jangan bingung kalau nemu versi lama di riwayat chat):
  1. Draf PDF paling awal: `R{rack}-H{h}-V{v}`
  2. Revisi user (pagi): `R{rack}C{kolom}L{layer}`, kolom 1=kiri, kolom 9=kanan
  3. **FINAL (sore, dari foto label resmi)**: `R{rack}C{kolom}{layer}` (tanpa L),
     kolom 1=**kanan**, kolom 9=**kiri** — kebalikan dari revisi #2.
  Implementasi grid di `resources/views/rack/index.blade.php` pakai CSS `grid-column`/
  `grid-row` eksplisit per slot (bukan urutan dokumen dari query) supaya orientasi
  akurat — jangan render berdasar urutan data mentah, itu menghasilkan grid acak
  (bug yang sempat terjadi & sudah diperbaiki 2x). Rumus final (grid polos, TANPA
  baris header kolom/kolom label layer — itu sempat dicoba lalu di-revert atas
  permintaan user): `gridColumn = COLUMNS+1-kolom`, `gridRow = ROWS-layer+1`.

  **Visualisasi 3D (2026-09-09 malam)**: halaman Rack Monitoring sekarang menampilkan
  panel 3D (Three.js, CDN `cdnjs three.js r128`, WebGL global build tanpa OrbitControls
  — rotasi drag manual + auto-rotate diimplementasikan sendiri di script) PALING ATAS,
  sebelum grid 2D interaktif. Menampilkan 8 rack (2 baris x 4, meniru foto RAK1-4
  depan/RAK5-8 belakang dari referensi user) dengan warna live per slot (endpoint baru
  `GET /rack/all-slots`, lihat `RackMonitoringController::allSlots()`), plus panel
  legenda kode di sampingnya. Grid 2D di bawahnya (tab per rack, klik slot, pencarian)
  tetap versi sederhana seperti sebelum redesign "TAMPAK SAMPING" — user minta di-revert.

  **Update tampilan rak 3D (2026-09-09 malam, setelah user kirim foto rak asli)**:
  ditambah struktur rak — 4 tiang biru (`frameColor`) + palang merah tiap layer
  (`beamColor`) via `buildRackFrame()`, meniru foto fisik gudang. Box hanya muncul
  untuk slot **FULL** sebagai keranjang biru (`crateColor`, mesh `visible=false` by
  default lalu di-`true`-kan saat fetch `/rack/all-slots` menemukan status FULL) — slot
  EMPTY tidak render keranjang sama sekali (rak kelihatan kosong, bukan box abu-abu).

  **Update arah susunan (2026-09-09 malam, revisi ke-2 setelah user anotasi foto)**:
  8 rack TIDAK lagi disusun melebar ke samping (4 lebar x 2 baris) — sekarang memanjang
  ke belakang: pasangan (R1,R2) paling depan berhadapan lewat lorong tengah, (R3,R4) di
  belakangnya, (R5,R6) lebih belakang lagi, (R7,R8) paling belakang. Lihat perhitungan
  `pairIdx`/`sideIdx`/`originX`/`originZ` di script. Palang/beam dasar (level bawah,
  `l===0`) juga dihapus atas permintaan user — beam sekarang cuma di tiap layer 1-5.

  **Update urutan penempatan (2026-09-10)**: 3D pakai array terpisah `RACK_ORDER_3D`
  = `['R1','R5','R2','R6','R3','R7','R4','R8']` (BUKAN `RACKS` polos R1..R8). Hasil:
  kolom KANAN layar depan→belakang = R1,R2,R3,R4; kolom KIRI depan→belakang = R5,R6,R7,R8.
  Kamera juga dibuat lebih menghadap lurus (`rotY` 0.25→0.1) + zoom lebih dekat
  (`radius` 34→26) supaya urutan pasangan jauh tidak "melintir". Label DAN data box
  realtime otomatis ikut nama rack di `RACK_ORDER_3D` (mesh key & fetch sama-sama
  `R{n}C{kolom}{layer}`) — ganti isi array ini kalau mau atur ulang posisi rack.
  **Catatan jujur**: saya tidak bisa memverifikasi visual WebGL-nya sendiri (tidak ada
  browser/GPU di sisi saya) — sudah dicek sintaks JS valid (`node --check`) dan CDN
  Three.js reachable, tapi hasil render sebenarnya perlu dicek langsung oleh user.

  **Redesign layout Rack Monitoring (2026-09-10, sesuai mockup ke-2 user)**:
  - Grid 2D coklat/emas DIHAPUS.
  - Iso 3D dipindah jadi **kecil di kolom kanan** (`.rack3d-mini`, 230px) + panel
    Detail slot di bawahnya. Layout: `.rackmon-layout` (1fr / 300px).
  - **Update (2026-09-10 sore)**: "Tampak depan" rak terpilih yang tadinya CSS flat
    diganti jadi **scene 3D kedua** (`initRackFront3D`, canvas `#rackFront3d`, 440px) —
    rak tunggal dirender Three.js, tampak depan sedikit menyudut. Box FULL = keranjang
    biru 3D, klik via **raycasting** → `window.showSlotDetailByBin(storage_bin)`.
    Data slot rak aktif dikirim dari controller sbg `$slotsData` (`{col,layer,status,
    storage_bin,code}`). Jadi halaman ini punya **2 WebGL context**: besar (rak aktif)
    + kecil (iso semua rack). Pencarian & `?highlight=` menyorot box di scene besar
    lewat `window.rackFrontFocus(code)` (ubah warna mesh jadi emas + buka detail).
  - **Label kode di keranjang (2026-09-10)**: tiap keranjang FULL di scene besar dapat
    label kode (mis. `R1C15`) di 3 sisi (depan + kiri + kanan) via `addCrateLabels()` —
    canvas texture (`makeLabelTexture`) di `PlaneGeometry` child mesh. Raycasting tetap
    non-rekursif jadi label plane tidak mengganggu klik box.
  - **Graying**: kalau user sudah pilih rack lewat tab (`rackExplicit` = `$request->has('rack')`),
    rack selain yang aktif di-abu-abukan di 3D (`setRackGrayed()` — tiap rack punya
    array material sendiri di `rackObjects[rackName]`). Kalau baru masuk menu (`/rack`
    tanpa query) → semua rack full warna.
  - Detail slot hanya terisi saat `.crate` diklik (default: placeholder "Klik salah satu box").
  - Panel "Keterangan kode" lama dihapus dari layout ini.
- Satu box bisa berisi lebih dari satu jenis item.
- Satu slot rack menyimpan satu box pada satu waktu.

## Pemetaan konsep dokumen → data existing (lihat `app/Services/RackTrackingService.php`)

| Konsep dokumen | Sumber data existing |
|---|---|
| Item / SKU | `db_swis.public.warehouse_stock.component` (+ component_name, unit) |
| Box | `warehouse_stock.bin_name` **===** `db_agv.m_matrix_storage_bin.qr_id` (match langsung, terverifikasi) |
| Isi box (many-to-many) | Agregat `SUM(qty) GROUP BY (bin_name, component)` dari ledger `warehouse_stock`, qty > 0 — tidak ada tabel `BOX_ITEMS` literal, tapi ledger ini mengimplementasikan relasi yang sama |
| Rack | `m_matrix_storage_bin.side` — **hanya R1..R8** yang dihitung (lihat `RackTrackingService::RACKS`) |
| Slot H (1-9) | `m_matrix_storage_bin.column_no` (format "C1".."C9") |
| Slot V (1-5) | `m_matrix_storage_bin.stack` |
| Status slot | `m_matrix_storage_bin.status` (FULL/EMPTY) |

**Verifikasi struktur (2026-09-09, data lokal)**: `side` R1..R8 masing-masing punya
persis 45 baris (column_no C1-C9 × stack 1-5) = 360 total — **cocok persis** dengan
dokumen. Di luar 8 rack ini ada `RA` (5 slot staging) dan `CONVE1`/`CONVE2` (titik
conveyor, side='0') — **dikecualikan** dari Rack Monitoring karena bukan storage rack
sesuai alur fisik di dokumen (Inbound→Sortir→AGV→Conveyor+ACR→**Rack storage**→Outbound).

## Keterbatasan data yang perlu diketahui

1. **Riwayat Transaksi bukan log box↔rack asli.** Tabel yang idealnya jadi log ini
   (`db_agv.t_status_rack`, `t_status_rack_trial`, `t_task_order`, `t_status_agv`)
   **kosong** di snapshot dev (kemungkinan dump diambil saat robot idle, ATAU memang
   belum dipakai live — **perlu dikonfirmasi ke programmer**). Sebagai gantinya,
   Riwayat Transaksi memakai ledger `warehouse_stock` (remark IN/OUT) dan me-resolve
   rack/slot lewat posisi box **SAAT INI** di `m_matrix_storage_bin` — bukan posisi
   persis pada waktu transaksi terjadi. Kolom di UI dinamai "Lokasi Box (kini)" +
   footnote singkat (`resources/views/transaksi/index.blade.php`). Kalau nanti
   terkonfirmasi `t_status_rack` terisi di produksi, ganti sumber data di
   `RackTrackingService::transactionHistory()` ke tabel itu untuk akurasi penuh.
   **Update (2026-09-10)**: tabel riwayat sekarang juga menampilkan **Item** (SKU +
   nama) dan **Qty** (bertanda +/-, hijau/merah) per transaksi — ini data ASLI
   per-baris dari ledger `warehouse_stock`, akurat (beda dari kolom lokasi yang cuma
   perkiraan).
   **Update (2026-09-11)**: footnote/note kuning dihapus total (user minta) — kolom
   "Lokasi Box (kini)" dianggap cukup jelas dari namanya sendiri. Ditambah:
   - **Sorting**: klik header **Waktu** (toggle terkini/terlama) atau **Event**
     (kelompokkan MASUK dulu / KELUAR dulu) via query `?sort=waktu|event&dir=asc|desc`,
     lihat `TransaksiController::index()` + `RackTrackingService::transactionHistory()`
     (parameter `$sort`, `$dir`, default `waktu`/`desc`).
   - **Pagination**: 10 baris/halaman (bukan 30), pager custom Sebelumnya/Selanjutnya
     (BUKAN `$movements->links()` bawaan Laravel — itu butuh Tailwind yang tidak dipakai
     di app ini, hasilnya bakal polos tak berstyle). Kalau nanti bikin pager di halaman
     lain, ikuti pola custom ini (`.pager`, `.pager-btn`, dst di layout), jangan pakai
     `->links()` langsung.
   - Filter tanggal/rack/event sekarang punya label kecil di atasnya (`.filter-field`)
     biar lebih jelas fungsinya.
   - Polish visual: zebra-striping baris tabel, hover row jadi biru muda (`--accent-soft`).
2. **Utilisasi rack di Laporan adalah snapshot saat ini**, bukan time-series historis
   per periode (data historis okupansi tidak tersedia tanpa tabel log di atas).
3. Ada box dengan `qr_id` yang sama muncul di >1 slot rack sekaligus di data dev
   (mis. BOX013) — data quality issue di snapshot test, bukan bug kode. Kalau muncul
   lagi di data produksi, perlu ditelusuri ke programmer.
4. Data dev lokal jumlahnya sedikit (cuma 3 distinct item, 30 baris ledger) — cukup
   untuk verifikasi fungsional, tapi chart/tabel akan terlihat jauh lebih hidup begitu
   connect ke data produksi asli.

## Struktur menu & implementasi

| Menu | Route | Controller | Isi |
|---|---|---|---|
| 01 Dashboard | `/dashboard`, `/dashboard/data` (JSON) | `DashboardController` | Kartu ringkasan (klikable, link ke halaman terkait), chart utilisasi rack (bar), chart tren in/out 7 hari (line) |

**Update Dashboard final (2026-09-12)**: dijadikan versi final —
- **Auto-refresh 30 detik** (polling `GET /dashboard/data`, bukan websocket — alasan sama
  seperti Rack Monitoring: penulis data sesungguhnya di luar Laravel) — update angka
  kartu + data 2 chart di tempat tanpa reload (`refreshDashboard()` di
  `dashboard/index.blade.php`). **Indikator visual "Live" DIHAPUS** (user minta) —
  polling tetap jalan diam-diam di background, cuma tidak ada teks/titik berdenyut
  yang menampilkannya lagi. Kalau nanti perlu indikator lagi, tinggal render ulang;
  logic `refreshDashboard()` tidak berubah.
- **Panel AI Insight (placeholder "Fase 2" dari dokumen PDF awal) DIHAPUS** (2026-09-12,
  user tanya perlu/tidak) — membangun AI beneran (integrasi API, prompt, dst) itu scope
  besar di luar sekadar polish, jadi kotak placeholder kosong dilepas saja daripada
  nongkrong tidak berfungsi di dashboard "final". Bisa dibangun ulang nanti kalau
  benar-benar dibutuhkan — bukan dihapus karena dianggap tidak penting selamanya.
- **Kartu jadi tautan** ke halaman terkait: Total Item & Slot Terisi → Laporan, Box
  Aktif → Rack Monitoring, Box Masuk/Keluar Hari Ini → Riwayat Transaksi dengan
  `?dari=<hari ini>&sampai=<hari ini>` otomatis terisi (deep link, bukan cuma ke
  halaman kosong). Tiap kartu dapat ikon (pakai ulang SVG nav sidebar yang sama,
  bukan icon baru, biar konsisten & pasti valid).
- Kartu "Box Masuk/Keluar" nilainya diwarnai (hijau=masuk, merah=keluar, `.stat-in`/
  `.stat-out`) — konsisten dengan warna qty di Riwayat Transaksi.
- Panel chart dapat caption penjelas singkat di bawah judul (`.panel-caption`),
  konsisten dengan pola panel di halaman Laporan.
| 02 Rack Monitoring | `/rack`, `/rack/{rack}/slots` (JSON), `/rack/slot/{storageBin}` (JSON), `/rack/search` (JSON) | `RackMonitoringController` | Pencarian box/item → lokasi, tab 8 rack, grid 9×5, detail slot on-click (AJAX), isi box |
| 03 Riwayat Transaksi | `/transaksi` | `TransaksiController` | Log box masuk/keluar, filter tanggal/rack/event, pagination |
| 04 Laporan | `/laporan`, `/laporan/export/excel`, `/laporan/export/pdf` | `LaporanController` | Stok per item + rack tersebar, utilisasi rack, export CSV (label "Excel" — dibuka native oleh Excel, tanpa dependency berat) & PDF (`barryvdh/laravel-dompdf`) |

**Update Laporan (2026-09-11)**: kartu ringkasan sempat ditambah lalu **DIHAPUS LAGI**
(2026-09-12) — user mempertanyakan kenapa perlu, dan memang duplikat info Dashboard +
tidak diminta di spek PDF asli. Tabel "Utilisasi Rack" tetap dapat bar visual per baris
(`.occ-bar-fill`, warna hijau/emas/merah — low/mid/high) menggantikan angka polos.

**Update filter + export custom (2026-09-12)**: `RackTrackingService::stockPerItem()`
sekarang terima `$rackFilter, $search, $sortBy, $sortDir` — dipakai SAMA PERSIS oleh
tampilan layar (`LaporanController::index()`) dan kedua export (`exportExcel`,
`exportPdf`), lewat helper privat `readFilters()`. Jadi export Excel/PDF sekarang ikut
filter rack + pencarian + sortir yang sedang aktif (sebelumnya export selalu ambil
SEMUA data mentah, tidak peduli apa yang difilter di layar — ini yang dikomplain user).
Filter rack diterapkan di PHP (bukan SQL) karena butuh `$item->racks` yang baru
dihitung setelah query awal. Sorting SKU/Nama/Qty sekarang **server-side** (query
`?sort=sku|nama|qty&dir=asc|desc`, pola sama seperti Riwayat Transaksi) — bukan lagi
client-side JS, supaya ikut kebawa ke export. Kalau nanti SKU sudah ratusan/ribuan,
tambahkan pagination juga (belum ada).
Tidak ada field "jenis/kategori" di data (`warehouse_stock` cuma punya component/
component_name/unit) — kalau user minta filter "per jenis" lagi, klarifikasi dulu
maksudnya field mana, jangan mengarang kolom yang tidak ada.

**Update struktur halaman "selayaknya laporan" (2026-09-12)**: user bilang halaman masih
terasa kurang seperti "menu laporan pada umumnya". Dirombak jadi:
- Header meta di atas: judul "Laporan Inventory" + waktu generate (`now()->translatedFormat`)
  + tombol **Cetak** (`window.print()`) di samping Excel/PDF.
- Filter dibungkus panel tersendiri (`.report-panel`, class `no-print`) + **filter chip**
  yang bisa dihapus satu-satu (`.search-chip` dipakai ulang dari Rack Monitoring) atau
  "Hapus semua filter" sekaligus.
- Tiap tabel dibungkus `.report-panel` dengan header "Laporan A · Stok per Item" /
  "Laporan B · Utilisasi Rack" + caption penjelas (termasuk penjelasan kenapa Utilisasi
  Rack belum bisa per-periode — keterbatasan data, lihat bagian atas file ini).
- **CSS print** (`@media print`) — sidebar/topbar/filter form disembunyikan, panel jadi
  border tipis polos, siap dicetak langsung dari browser (Ctrl+P) selain lewat export
  Excel/PDF. `.th-sort` di-nonaktifkan (bukan disembunyikan — kalau di-`display:none`
  teks headernya ikut hilang karena teksnya ada DI DALAM elemen itu, bukan di luar).

Semua query terpusat di `app/Services/RackTrackingService.php` — jangan duplikasi logic
query di controller, tambah method baru di service kalau perlu data baru.

## Modul WMS (baru, 2026-09-18)

Dipicu user kirim 2 contoh dokumen WMS Polibatam lain (`...-Put Away.pdf`,
`...-Good Receive.pdf`) + video referensi (tidak bisa saya tonton — tidak ada akses
video/browser di sesi ini, analisa murni dari 2 PDF itu), minta implementasi alur WMS
penuh: **Inbound** ASN → Good Receive Note → Put Away → On Hand, **Outbound** Sales
Order → Picking → Delivery Order.

**Keputusan desain (hasil diskusi, JANGAN diubah tanpa konfirmasi ulang ke user):**
1. **Manual, BUKAN integrasi robot.** Put Away & Picking dicatat manual oleh
   staf/operator setelah kerjaan fisik selesai (form konfirmasi), TIDAK menulis ke
   `t_task_order`/schema `db_agv` untuk menggerakkan robot AGV. Alasan: (a) risiko
   keselamatan fisik kalau salah — saya tidak pernah buka/ubah kode WinForms/AGV
   sepanjang project ini, tidak tahu persis kontrak/protokolnya; (b) `t_task_order`
   selalu kosong di tiap snapshot sepanjang project, jadi bahkan pola pemakaian live-nya
   sendiri tidak jelas; (c) prinsip asli sistem ini sejak awal memang "tidak
   mengoperasikan robot" — reverse itu butuh keputusan lebih besar dari sekadar chat.
   Kalau nanti mau diintegrasikan, arah yang lebih aman: WinForms/AGV yang menulis
   status ke suatu tempat yang dibaca modul ini (read-only), bukan sebaliknya.
2. **Nama sistem diganti jadi "SWISS WMS"** (dari "SWIS Inventory Tracking") — lihat
   catatan di bagian atas file ini. Label "WMS" dipilih user sebagai nama modul
   sekaligus masuk ke nama sistem (bukan diganti jadi istilah lain).
3. **WMS = TAMBAHAN, BUKAN pengganti.** REVISI dari keputusan awal ("gantikan
   sebagian") — user klarifikasi ulang (2026-09-18, lanjutan diskusi yang sama):
   4 menu monitoring existing (Dashboard/Rack Monitoring/Riwayat Transaksi/Laporan)
   **TIDAK ada yang dihapus/diganti**. Modul WMS jadi menu/bagian baru berdampingan.
   Alasan user: nama part di contoh PDF (MN04-PBL dst) itu cuma referensi/inspirasi,
   bukan data yang harus ditiru — yang penting proses bisnisnya nyata sesuai kondisi
   lapangan.
4. **Master Part `wms_parts` DIHAPUS** (dibuat lalu di-rollback di sesi yang sama,
   setelah keputusan #3 di atas) — GANTI jadi:
   - **Inbound** (`wms_asn_lines`, `wms_grn_lines`): field `component` + `component_name`
     langsung di baris (string biasa, BUKAN FK) — meniru persis konvensi
     `warehouse_stock.component` milik sistem lama, karena source data itu memang
     tidak punya tabel master komponen formal (sengaja konsisten, bukan bikin
     struktur baru yang tidak ada presedennya).
   - **Outbound** (`wms_so_lines`, `wms_picking_lines`, `wms_do_lines`): FK
     **`matrix_partcode_id`** LANGSUNG ke tabel `matrix_partcode` yang sudah nyata ada
     (data real: partcode `10001` "Botol Aqua", `12` "MMGP Mineral") — bukan master
     part terpisah lagi. Sudah dites pakai data asli ini via tinker, jalan.
5. Skema final (14 tabel `wms_*`, migration `database/migrations/2026_09_18_*`,
   model `app/Models/Wms/*`) — didokumentasikan di `DATABASE.md` bagian "Modul WMS",
   BUKAN di sini. Baru sebatas skema+model, sudah dites end-to-end 2x (versi awal
   dengan wms_parts, lalu versi revisi dengan matrix_partcode/component) pakai data
   nyata, lalu data ujinya dihapus lagi (tabel kosong).
6. **UI Inbound selesai dibangun** (2026-09-18, sesi lanjutan): Supplier, ASN, GRN,
   Put Away, On Hand — controller+route+Blade view lengkap, sidebar ditambah section
   "WMS · Inbound", CSS form baru ditambahkan ke `resources/css/app.css` lalu
   `npm run build` (bukan cuma dev server). Dites end-to-end lewat HTTP asli (curl):
   buat Supplier → ASN → confirm → GRN (prefill dari ASN) → Put Away (prefill dari
   GRN, isi lokasi `R1C11`) → GRN outstanding jadi 0 → On Hand tampil benar. Data uji
   sudah dihapus.
7. **UI Outbound selesai dibangun** (2026-09-19, sesi lanjutan): Customer, Sales
   Order, Picking, Delivery Order — controller+route+Blade view lengkap, sidebar
   ditambah section "WMS · Outbound". Tidak perlu CSS baru (semua class dari sesi
   Inbound dipakai ulang, dikonfirmasi lewat grep). Beda struktural dari Inbound:
   Outbound cuma 3 langkah (SO→Picking→DO) bukan 4, karena `Picking` sudah
   menggabungkan "ambil barang" + "konfirmasi lokasi asal" jadi satu langkah (tidak
   ada analog GRN terpisah). Dites end-to-end lewat HTTP asli (curl): Customer → SO
   (line pilih `matrix_partcode_id` real, partcode "10001"/"Botol Aqua") → confirm SO
   → Picking (prefill dari SO, isi lokasi `R2C33`) → SO outstanding jadi 0 →
   Delivery Order (prefill dari Picking) → relasi SO↔Picking↔DO tampil benar di
   semua halaman show. Data uji sudah dihapus, 4 menu lama + Inbound dicek tetap
   normal.

   Detail lengkap kedua sesi UI ini (nama file, class CSS, apa yang belum
   divalidasi seperti `location_code`, keterbatasan tracking qty delivered vs
   picked) ada di `DATABASE.md` bagian "Modul WMS" → "Sudah dikerjakan" /
   "Belum dikerjakan". **Modul WMS (Inbound + Outbound) sekarang punya UI lengkap
   end-to-end** — kerja berikutnya (kalau ada) kemungkinan besar penyempurnaan
   (validasi location_code, halaman edit, dsb), bukan lagi bangun UI dari nol.
8. **Perbaikan konsep & UX** (2026-09-19, dipicu pertanyaan user langsung soal
   `lot_no`/`serial_no`/`pallet_id` & relasi On Hand vs Laporan):
   - `serial_no` & `pallet_id` **DIHAPUS** dari `wms_grn_lines` — tidak ada
     padanannya di sistem nyata (unit fisik gudang ini "box"/QR code, bukan barang
     serialize satuan atau pallet). Sama seperti kasus `wms_parts` sebelumnya:
     kebawa dari contoh PDF referensi, bukan kebutuhan nyata. `lot_no`/expiry date
     TETAP dipakai (justifikasi nyata: barang konsumen FaceWash/Shampoo dst punya
     nomor batch & kadaluarsa beneran).
   - Qty di semua halaman WMS sekarang pakai helper `fmt_qty()` (tampil `10`,
     bukan `10.00`) — bukan `number_format` biasa.
   - Notifikasi dipisah warna per jenis: sukses=hijau, info/netral=indigo,
     error=merah (`.note-success`/`.note-info`/`.note-danger` di `app.css`) —
     sebelumnya semua sama-sama amber/kuning sehingga pesan sukses dan info
     prefill terlihat identik.
   - Menu **"On Hand" diganti jadi "Put Away Summary"** (cuma label, route/URL
     tetap `wms.on-hand`) — nama lama menyiratkan ini stok otentik terkini,
     padahal cuma audit sepihak dari alur WMS (`SUM(putaway_lines.qty)`), TIDAK
     baca `warehouse_stock` sama sekali dan TIDAK potong konsumsi produksi.
     Bisa beda angka dari Laporan → Stok per Item untuk component yang sama —
     bukan bug, konsekuensi dari WMS = ledger tambahan terpisah, bukan pengganti.
     Caption halaman sekarang eksplisit jelasin ini + link ke Laporan.

   Detail lengkap tiap poin ada di `DATABASE.md` bagian "Modul WMS" →
   "Sudah dikerjakan (2026-09-19, sesi perbaikan konsep & UX)".
9. **Sidebar & URL WMS dirapikan** (2026-09-19, sesi lanjutan lagi, dipicu
   feedback langsung dari screenshot user):
   - URL WMS **TIDAK lagi berawalan `/wms`** (mis. `/delivery-orders`, bukan
     `/wms/delivery-orders`) — `routes/web.php` diubah dari
     `Route::prefix('wms')->name('wms.')` jadi `Route::name('wms.')` saja (URL
     prefix dibuang, route NAME `wms.*` tetap dipertahankan supaya seluruh
     pemanggilan `route('wms.xxx')` di puluhan file Blade tidak perlu diubah).
     Dicek tidak ada bentrok URL dengan route lama (`dashboard`, `rack`,
     `transaksi`, `laporan`) lewat `route:list`.
   - Label sidebar **"WMS · Inbound"/"WMS · Outbound" jadi "Inbound"/"Outbound"**
     saja (buang prefix "WMS ·").
   - **Outbound dibedakan dari Inbound lewat warna oranye** (section label +
     nav item aktif), Inbound tetap indigo (warna aksen utama app, dipakai juga
     di 4 menu monitoring). Oranye ini **bukan tebakan asal** — diambil dari
     Tailwind `orange-600`, warna yang sudah ada persis di logo Polibatam sendiri
     (huruf "o" di wordmark "poli**b**atam") supaya tetap konsisten dengan
     identitas visual kampus, bukan warna baru yang asing.
   - Area logo Multi Mitra Guna (bawah sidebar) diberi caption kecil "In
     collaboration with" + border pemisah, tetap di background putih terang
     (TIDAK ikut diwarnai) supaya teks hitam "MULTI MITRA GUNA" yang baked-in di
     PNG logo (`public/images/MMG-Logo.png`) tetap kontras — sengaja tidak
     menaruh warna oranye/indigo langsung di belakang logo itu.
   - Tidak pakai library/plugin sidebar eksternal (Flowbite/Preline/dst) —
     PC produksi di lokasi SWIS tidak ada akses internet (lihat catatan CDN
     font/Chart.js/Three.js sebelumnya), jadi dependency baru berarti harus
     di-vendor manual juga. Sidebar tetap HTML+Tailwind buatan sendiri,
     cukup untuk kompleksitas saat ini.
10. **Rename namespace "Wms" -> "Inbound"/"Outbound" + fix konsep, supaya
    Inbound/Outbound benar-benar "menyesuaikan" sistem SWIS nyata** (2026-09-20,
    dipicu feedback: "jangan sampai di luar batas sistem... inbound dan outbound
    nya yang harus menyesuaikan sistem SWIS yang ada... dibuat pembelajaran
    mahasiswa"):
    - **Struktur folder & namespace di-refactor total**, bukan cuma URL:
      `app/Models/Wms/*` -> `app/Models/Inbound/*` + `app/Models/Outbound/*`,
      `app/Http/Controllers/Wms/*` -> `.../Inbound/*` + `.../Outbound/*`,
      `resources/views/wms/*` -> `resources/views/inbound/*` +
      `resources/views/outbound/*`. Route name prefix `wms.` juga diganti jadi
      `inbound.`/`outbound.` (bukan dipertahankan seperti keputusan sesi
      sebelumnya — user eksplisit minta folder internalnya ikut diubah, bukan
      cuma disembunyikan di URL). Nama tabel database (`wms_*`, 14 tabel) TETAP
      TIDAK diubah — itu murni internal, tidak pernah terlihat user/mahasiswa,
      migrasi ulang cuma menambah risiko tanpa manfaat UX apa pun.
    - **`location_code` sekarang benar-benar divalidasi** terhadap 360 kode
      slot nyata (`Rule::in($this->rack->allLocationCodes())`) di
      `PutawayController::store()` & `PickingController::store()` — sebelumnya
      cuma `required|string|max:20`, artinya staf/mahasiswa bisa saja
      mengetik lokasi yang tidak ada di rak fisik dan sistem tetap menerimanya.
      Ini persis jenis "keluar batas sistem" yang dikeluhkan user. Sudah dites:
      lokasi palsu (`X9Z99`) ditolak, lokasi nyata (`R1C11`) diterima.
    - **Kode component di ASN/GRN sekarang disarankan dari data nyata** —
      method baru `RackTrackingService::knownComponents()` mengambil daftar
      component yang SUDAH ADA di `warehouse_stock` (bukan bikin master baru),
      dipasang sebagai `<datalist>` + auto-fill nama/UOM di form ASN & GRN.
      Field tetap bisa diisi manual (barang baru boleh masuk), tapi sekarang
      MENGARAHKAN ke data yang sungguhan dipakai gudang SWIS ini, bukan
      sekadar nama karangan seperti contoh sebelumnya ("FW123 FaceWash").
    - **SUDAH DIPUTUSKAN (2026-09-20, jangan tanya ulang ke user): WMS TIDAK
      menulis ke `warehouse_stock`.** Sempat ditanyakan ke user apakah Put
      Away/Picking perlu ikut bikin baris IN/OUT di `warehouse_stock` (ledger
      asli yang dibaca Dashboard/Rack Monitoring/Laporan) supaya transaksi WMS
      "kelihatan" di menu monitoring lama juga — user pilih opsi yang
      direkomendasikan: **TETAP TERPISAH**. Alasan: box tanpa QR code asli
      (tidak pernah discan robot) kalau ditulis ke `warehouse_stock` akan
      mencemari ledger utama dan berpotensi bikin Rack Monitoring tidak akurat
      (karena `m_matrix_storage_bin` — sumber kebenaran posisi fisik box —
      TIDAK ikut terupdate oleh WMS). Jadi WMS tetap jadi **jejak dokumen
      administratif terpisah** (ASN/GRN/SO/DO), bukan live inventory yang
      menyatu dengan data sensor/robot. Mahasiswa belajar alur dokumen WMS
      yang benar, sambil tetap paham ini simulasi administratif di atas data
      nyata (component/location tervalidasi), bukan pengganti sistem robot.
11. **Redesign visual mengikuti mockup yang diberikan user** (2026-09-20):
    sidebar navy gelap (`slate-900`) + aksen emas (warna emas diambil dari
    logo Multi Mitra Guna, bukan sembarang), kartu KPI Dashboard dikasih
    border-kiri + badge ikon berwarna (biru/ungu/hijau/merah per kartu, dari
    palet Tailwind baku), 3 panel Dashboard (Utilization per rack, In/out
    trend, Item Overview) dikasih header strip navy dengan ikon — lewat class
    baru `.panel-dark-head` yang HANYA dipakai di Dashboard, TIDAK mengubah
    `.panel`/`.panel-title` dasar yang masih dipakai Rack Monitoring (supaya
    halaman itu tidak ikut berubah tanpa diminta). Logo tetap Polibatam di
    atas / MMG di bawah, caption "In collaboration with" DIHAPUS sesuai
    permintaan. Nav Inbound aktif = biru langit, Outbound aktif = emas (tetap
    dibedakan seperti sebelumnya, cuma warnanya disesuaikan supaya kebaca di
    atas navy).

    **Revisi logo (masih 2026-09-20, user komplain "bg nya sendiri-sendiri")**:
    awalnya logo dikasih kotak background sendiri (putih di atas, gradient
    emas di bawah) supaya kontras. User minta dihapus semua background. Tapi
    setelah dicoba tanpa background sama sekali (cuma `object-contain`, tidak
    ada filter), ketauan masalah nyata: logo Polibatam punya teks navy gelap
    ("p", "li" di wordmark "polibatam") yang NYARIS TAK TERLIHAT di atas
    sidebar navy — dibuktikan lewat screenshot headless Chrome sebelum
    dilaporkan ke user. Solusinya BUKAN kotak background lagi, tapi CSS
    `filter: drop-shadow(...)` berlapis di `.brand-plate img` yang bikin efek
    glow/halo putih tipis mengikuti bentuk logo (bukan kotak persegi) — jadi
    tetap "tanpa background" sesuai permintaan literal user, tapi teks navy
    & teks hitam "MULTI MITRA GUNA" tetap kebaca. Diverifikasi lagi lewat
    screenshot + crop-zoom ke kedua logo, baru dilaporkan selesai.

    **Sengaja TIDAK ditambahkan** dari mockup: ikon hamburger (collapse
    sidebar), ikon lonceng notifikasi, dan avatar/nama user "Admin" di
    topbar — ketiganya di mockup mengimplikasikan fitur yang TIDAK ada di
    aplikasi ini (tidak ada sistem login/auth sama sekali, tidak ada sistem
    notifikasi). Menambahkannya cuma sebagai dekorasi kosong dianggap
    berisiko menyesatkan (mirip prinsip yang sama dengan kenapa location_code
    & component divalidasi ke data nyata — jangan pura-pura ada kapabilitas
    yang sebenarnya tidak ada). Kalau nanti user memang mau sistem
    login/notifikasi sungguhan, itu fitur baru yang perlu didiskusikan
    terpisah, bukan sekadar tempelan visual.
    Verifikasi dilakukan pakai screenshot headless Chrome (`chrome.exe
    --headless=new --screenshot=...`), bukan cuma cek HTTP 200 — dicek
    Dashboard, Rack Monitoring (pastikan TIDAK ikut berubah), ASN create,
    Customers index.
12. **Investigasi mekanisme robot AGV yang SEBENARNYA** (2026-09-20, dipicu
    user komplain "kok manual, kan semua beroperasi lewat robot"):
    - User menekankan ulang bahwa gudang SWIS ini secara fisik dioperasikan
      robot, jadi Put Away/Picking manual dianggap tidak masuk akal. Daripada
      menebak, saya baca LANGSUNG source code aplikasi WinForms
      (`../Mainform`, project C# terpisah yang TERNYATA ada di komputer yang
      sama — folder `Mainform/Mainform/*.cs`).
    - **Temuan penting**: robot AGV TIDAK dikontrol lewat tabel database
      (`db_agv.t_task_order`, yang memang selalu kosong — BUKAN itu
      mekanismenya, jadi dugaan awal salah total). Mekanisme sebenarnya:
      - **REST API** ke `http://192.168.50.134/AGV/api/reqTaskCtu.php`
        (`FormMultiSend.cs`/`FormTrayRack.cs`, fungsi `sendBinCtu(depart,
        dest, qrId)`) — kirim JSON `{depart, dest}` buat gerakkan tray/box
        antara conveyor (`CONVE205501013` = input, `CONVE104501013` =
        output) dan slot rak spesifik.
      - **Modbus TCP** langsung ke `192.168.51.150:502` (pakai library
        `EasyModbus`) — kontrol level rendah, kemungkinan buat conveyor/PLC.
      - Begitu command dikirim (atau bahkan GAGAL — kode aslinya update
        status di blok `catch` juga!), WinForms app LANGSUNG
        `UPDATE db_agv.m_matrix_storage_bin SET status='FULL'/'EMPTY',
        qr_id=...` — optimistic update, bukan callback konfirmasi dari
        robot. Ini celah reliabilitas yang SUDAH ADA di sistem asli
        (bukan sesuatu yang WMS ini perkenalkan).
      - `cbSlot` (dropdown pilihan slot tujuan di WinForms) TERNYATA JUGA
        tidak difilter ke status EMPTY saja (cuma filter by `side`/rack) —
        jadi WMS Laravel bukan satu-satunya yang punya "gap" ini, cuma
        WMS sekarang JUSTRU lebih ketat (lihat poin di bawah).
    - **Kenapa BELUM disambungkan langsung ke robot** (bukan menolak, tapi
      hambatan nyata): (a) `192.168.50.134`/`192.168.51.150` itu IP LAN
      lokal — Laravel app ini perlu jalan di jaringan yang sama, belum
      dikonfirmasi apakah PC deployment nanti punya akses; (b) kalau
      WinForms & Laravel WMS sama-sama bisa kirim command ke robot yang
      sama & update tabel status yang sama, berisiko bentrok/balapan
      command tanpa mekanisme locking; (c) Modbus TCP itu kontrol hardware
      langsung, butuh paham register map & interlock keselamatan sebelum
      aman disentuh dari kode baru. **BELUM DIPUTUSKAN** apakah/kapan mau
      integrasi penuh — perlu didiskusikan dulu soal akses jaringan &
      strategi coexist dengan WinForms sebelum ada kode yang benar-benar
      kirim command ke robot.
    - **SUDAH DIKERJAKAN sebagai langkah aman (tidak nyentuh robot sama
      sekali)**: method baru `RackTrackingService::emptyLocationCodes()` &
      `occupiedLocationCodes()` — baca LIVE status `m_matrix_storage_bin`
      (read-only, koneksi `pgsql_agv`). Put Away sekarang cuma menawarkan
      slot yang **benar-benar EMPTY** saat ini (bukan 360 slot penuh tanpa
      pandang status seperti sebelumnya), Picking cuma menawarkan slot yang
      **benar-benar FULL**. Divalidasi dua kali (saat form dibuka DAN saat
      submit, supaya tidak race dengan perubahan status di antara
      keduanya). Sudah dites end-to-end: submit ke slot yang FULL saat
      Put Away DITOLAK, submit ke slot EMPTY DITERIMA. Ini SECARA STRICT
      lebih ketat dari WinForms aslinya (yang cuma filter by rack, tidak
      by status) — WMS jadi lebih akurat terhadap kondisi fisik gudang
      tanpa perlu kirim command apa pun ke robot.
13. **Refresh tampilan/warna per-menu, dikerjakan SATU MENU PER SATU MENU**
    (dimulai 2026-09-20, atas permintaan user eksplisit — jangan borongan
    semua halaman sekaligus). Pola yang dipakai (supaya konsisten dilanjutkan
    ke menu berikutnya): tambah class MODIFIER baru, JANGAN ubah class dasar
    yang dipakai lintas-halaman (biar halaman yang belum "digarap" gilirannya
    tidak ikut berubah tanpa diminta):
    - `.btn-primary-in` (biru, `sky-600`) / `.btn-primary-out` (emas,
      `amber-500`) — tombol aksi utama, tempel manual gantikan `.btn-primary`
      di halaman section terkait.
    - `.table-wrap-in` / `.table-wrap-out` — tint header tabel (`sky-50`/
      `amber-50`), tempel sebagai class tambahan di `.table-wrap`.
    - `.report-panel-in` / `.report-panel-out` — garis aksen 4px di atas
      panel form (`border-t-sky-500`/`border-t-amber-500`), tempel sebagai
      class tambahan di `.report-panel`.
    - **Menu yang SUDAH digarap**: Suppliers (index/create/edit) — biru,
      selesai & dites (screenshot + cek regresi ke semua halaman lain).
    - **Menu yang BELUM digarap** (masih pakai warna lama/indigo default,
      giliran berikutnya kalau user lanjut): ASN, Goods Receive, Put Away,
      Put Away Summary (Inbound — bakal pakai `-in`/biru), Customers, Sales
      Order, Picking, Delivery Order (Outbound — bakal pakai `-out`/emas).
    - Class `.btn-primary-out`/`.table-wrap-out`/`.report-panel-out` SUDAH
      dibuat di `app.css` (sekalian, karena murah), tapi BELUM dipakai di
      view manapun — nunggu giliran Outbound digarap.
14. **Bahasa flash message diseragamkan ke Inggris** (2026-09-20) — 12 pesan
    `session('status')`/`withErrors` di controller (Supplier/ASN/GRN/Put
    Away/Customer/Sales Order/Picking/Delivery Order) sebelumnya tercampur
    Indonesia (mis. "Supplier ... berhasil ditambahkan.") padahal seluruh
    label/tombol/UI sudah Inggris sejak awal — sekarang semua Inggris
    ("Supplier ... was added successfully."). **Aturan baku ke depan: SEMUA
    teks yang tampil di UI (termasuk flash message/error dinamis) harus
    Inggris**, bukan cuma label statis — chat balasan ke user tetap Bahasa
    Indonesia (tidak berubah, itu aturan terpisah).
15. **Semua caption/hint penjelasan dihapus dari halaman WMS** (2026-09-20,
    by user choice saat ditanya, bukan cuma untuk ASN — diterapkan sekaligus
    ke SEMUA halaman Inbound & Outbound, bukan nunggu giliran "1 menu per 1
    menu" seperti soal warna, karena ini soal konten/bahasa bukan tema
    visual). Yang dihapus: kalimat `.report-panel-caption` yang menjelaskan
    konsep (mis. "Supplier's plan of what will be shipped...") dan banner
    `.note-info` yang menjelaskan perilaku UI (mis. hint auto-fill
    component, hint "hanya slot EMPTY/FULL yang ditawarkan"). Yang
    DIPERTAHANKAN (bukan "caption penjelasan", tapi info/aksi fungsional):
    - Subtitle jenis dokumen yang pendek (mis. "Advance Shipment Notice" di
      bawah nomor ASN, `{{ $supplier->code }}` di halaman edit) — bukan
      kalimat penjelasan, cuma label identitas.
    - Banner "Prefilled from ASN/Picking X — switch to manual entry" — ada
      link fungsional yang jadi satu-satunya cara pindah ke mode manual,
      bukan sekadar hiasan penjelasan.
    - Put Away Summary: dipersingkat drastis (dari 2 kalimat panjang jadi
      "Not the true stock figure — see Reports → Stock per Item for that."),
      TIDAK dihapus total — link ke Reports itu penting supaya user tidak
      salah kira angka WMS ini stok asli (sudah dibahas panjang lebar
      sebelumnya kenapa ini rawan disalahpahami).
16. **PO Number + export PDF untuk ASN** (2026-09-20, dipicu user kirim contoh
    PDF "ASN Report" dari sistem WMS SMK N 1 Tanjungpinang — sama seperti
    referensi video/PDF sebelumnya, DIADAPTASI, bukan ditiru mentah):
    - **`po_number`** — kolom baru nullable di `wms_asns` (migration
      `2026_09_20_093700_add_po_number_to_wms_asns_table.php`), referensi
      nomor Purchase Order internal gudang ke supplier. Cuma teks bebas,
      TIDAK di-FK ke tabel PO manapun (sistem ini tidak punya modul
      Purchasing) — sama prinsipnya dengan `lot_no` di GRN.
    - **Field dari PDF referensi yang SENGAJA TIDAK ditiru**: Container No,
      Truck No, BL (Bill of Lading), AJU/AJU Date, TPT, Ship Document, Net
      Weight/Volume — itu semua field logistik ekspor-impor/customs, TIDAK
      relevan untuk SWIS (gudang lokal, bukan freight forwarding). Pola yang
      sama seperti kasus serial_no/pallet_id yang dihapus sebelumnya: pakai
      HANYA field yang benar-benar sesuai kondisi SWIS.
    - **Export PDF** — route baru `GET asns/{asn}/pdf` (`inbound.asns.pdf`),
      `AsnController::exportPdf()` pakai `\Pdf::loadView(...)` (dompdf,
      sudah dipakai sebelumnya di `LaporanController`), view baru
      `resources/views/inbound/asns/pdf.blade.php`. Layout letterhead
      terinspirasi dari referensi PDF (nomor dokumen kanan-atas, judul besar,
      info box 2 kolom, tabel item, footer) tapi isinya field ASN kita yang
      sungguhan (Supplier/PO Number/Status/Expected Date/Created/Remark/line
      items), bukan field customs yang tidak relevan. **Watermark "SWISS
      POLIBATAM"** diagonal berulang, opacity rendah (`0.06`), pakai CSS
      `transform: rotate()` + `position: fixed` (dompdf v3.1.6 sudah
      mendukung ini). Tombol "Download PDF" ditambah di halaman show ASN.
      Sudah dites: PDF ter-generate benar (dicek isi PDF-nya via Read tool,
      bukan cuma cek ukuran file), watermark & semua data tampil sesuai.
    - **Pola ini baru diterapkan untuk ASN saja** — kalau user mau menu lain
      (GRN, Put Away, SO, dst) juga bisa export PDF, itu permintaan
      terpisah, belum otomatis ada di menu lain.
17. **Header tabel digelapkan SECARA GLOBAL, di SEMUA menu sekaligus**
    (2026-09-20) — beda dari poin 13 (warna per-section, satu menu per satu
    menu) karena user eksplisit minta ini berlaku "di semua menu/di sistem
    ini", bukan bertahap. Diubah di `@layer base`, rule dasar `th` (bukan
    class tambahan) — jadi otomatis berlaku ke SEMUA tabel, termasuk
    halaman yang belum pernah disentuh sesi-sesi sebelumnya (Rack
    Monitoring, Transaction History, Reports).
    - `th`: `bg-slate-700` + `text-white` (sebelumnya `bg-slate-50` +
      `text-slate-700`, nyaris tak kelihatan bedanya dari body).
    - `.th-sort:hover`/`.th-sort.active` (kolom bisa-disortir, dipakai di
      Transaction History & Reports): diganti dari `text-indigo-700`
      (nyaris invisible di atas bg gelap) jadi `text-amber-300` (emas
      terang, kelihatan jelas & konsisten dengan tema emas sidebar).
    - **Override khusus mode cetak** (`@media print`): `th` dipaksa balik
      ke terang (`#F1F5F9` bg + `#334155` teks) — browser biasanya BUANG
      background gelap saat print (hemat tinta), yang kalau dibiarkan bikin
      teks putih jadi tak kelihatan di atas kertas putih. Jadi versi layar
      = gelap, versi kertas/print = tetap terang seperti sebelumnya. Export
      PDF Laporan (`laporan/pdf.blade.php`) & ASN (`inbound/asns/pdf.blade.php`)
      TIDAK kepengaruh — itu view dompdf terpisah, tidak pakai `app.css`.
    - Override `.table-wrap-in`/`.table-wrap-out` (biru/emas muda) yang
      sempat dipasang khusus Suppliers di poin 13 **DICABUT lagi** (cuma
      hover baris yang masih beda tipis per section) — supaya tidak ada
      satu pun halaman yang headernya beda sendiri dari yang lain, sesuai
      permintaan "tidak membingungkan".
    - Dites lewat screenshot headless Chrome di 3 halaman: Suppliers (sudah
      pernah disentuh), Transaction History & Rack Monitoring (belum
      pernah disentuh sebelumnya) — semua header sekarang seragam gelap,
      tidak ada regresi ke 13 halaman lain (dicek HTTP 200 semua).
18. **Judul topbar halaman detail dobel, dirapikan di SEMUA menu sekaligus**
    (2026-09-20) — `@section('title', 'ASN ' . $asn->asn_no)` dkk bikin
    topbar tampil "ASN ASN-260920-003" (kata "ASN" ketulis 2x, karena nomor
    dokumennya sendiri sudah berawalan "ASN-"). Sama pola di 6 halaman show:
    ASN, GRN, Put Away, Sales Order, Picking, Delivery Order — SEMUA diubah
    jadi cuma nomor dokumennya saja (`$asn->asn_no`, `$grn->grn_no`, dst,
    tanpa prefix kata). Konteks jenis dokumen tetap kelihatan dari subtitle
    kecil di bawah nomor pada body halaman (mis. "Advance Shipment Notice")
    — jadi tidak hilang informasinya, cuma tidak dobel di topbar. User minta
    ini diterapkan ke "menu-menu lainnya juga" sekaligus, bukan bertahap.
19. **Export PDF untuk GRN** (2026-09-20) — pola identik dengan ASN (poin 16):
    route `GET grns/{grn}/pdf` (`inbound.grns.pdf`), `GrnController::exportPdf()`,
    view `resources/views/inbound/grns/pdf.blade.php` (letterhead sama,
    watermark "SWIS POLIBATAM" sama). Field yang ditampilkan: Supplier,
    Source ASN, Status, Received Date, Transport Mode/Vehicle No, Remark,
    dan tabel line items (Component/Description/Lot No/Mfg Date/Expired
    Date/Qty Received/Qty Put Away/Outstanding/UOM) — field customs dari
    referensi PDF (Container No/BL/AJU/dst) tetap TIDAK dipakai, sama
    alasannya seperti ASN. Tombol "Download PDF" ditambah di halaman show
    GRN. Sudah dites: PDF ter-generate benar (dicek isi PDF-nya).
    - **Catatan berulang soal "Created tidak realtime"**: user sempat
      tanya lagi (kedua kalinya, sebelumnya juga soal ASN) kenapa timestamp
      "Created" tidak ikut berubah ke jam sekarang. Sudah dijelaskan lagi:
      itu memang BENAR perilakunya (mencatat kapan dibuat, bukan jam
      berjalan) — bukan bug. Kalau muncul pertanyaan sama untuk ketiga
      kalinya di menu lain, kemungkinan user sebenarnya mau indikator
      "X waktu lalu" yang live — pertimbangkan tawarkan proaktif itu.

      **KOREKSI (2026-09-20, sama hari): TERNYATA BUG BENERAN.** User
      laporan lagi di Put Away: "Confirmed At" tampil jam 11:11 padahal baru
      dibuat jam 18:11 — selisih PERSIS 7 jam. Dicek: `config('app.timezone')`
      di `config/app.php` masih default **`UTC`**, sedangkan jam di topbar
      (JS, browser lokal) sudah benar WIB (Asia/Jakarta, UTC+7). Jadi 2x
      "keluhan Created tidak realtime" sebelumnya (ASN & GRN) BUKAN salah
      paham user seperti yang saya kira — itu bug asli, cuma kebetulan
      terlihat seperti kesalahpahaman karena selisihnya konsisten.
      **SUDAH DIPERBAIKI**: `'timezone' => 'Asia/Jakarta'` di `config/app.php`
      (`php artisan config:clear` setelahnya). Ini FIX GLOBAL — berlaku ke
      SEMUA `now()`/timestamp di SELURUH aplikasi (Created/Confirmed At/dst
      di semua menu), bukan cuma Put Away. Diverifikasi: `now()` &
      `date_default_timezone_get()` sama-sama Asia/Jakarta, dan record baru
      yang dibuat lewat HTTP request sungguhan menunjukkan jam yang cocok
      dengan jam sistem asli. **Pelajaran**: kalau user melaporkan hal yang
      sama 2x dengan pola selisih yang KONSISTEN (bukan acak), curigai bug
      asli duluan, jangan keburu asumsikan salah paham — should've dicek
      dari laporan pertama.
20. **UX & fitur tambahan dipicu 1 pesan besar user** (2026-09-20, soal
    tampilan Put Away):
    - **Tombol "Back to List" dipindah + dikasih ikon panah**, di SEMUA 6
      halaman show sekaligus (ASN/GRN/Put Away/SO/Picking/DO) — sebelumnya
      warnanya sama (`.btn-secondary`) dengan tombol lain (Download PDF,
      dst) di baris bawah, jadi tidak langsung dikenali mata. Sekarang jadi
      class baru `.back-link` (ikon panah kiri + teks, bukan tombol
      berbingkai), ditaruh di POJOK KIRI ATAS konten (sebelum panel),
      terpisah dari baris tombol aksi lain. Div `.detail-actions` yang jadi
      kosong di Delivery Order (dulu isinya cuma tombol ini) sudah dibersihkan.
    - **Tabel Outstanding/Qty to Put Away & Outstanding/Qty to Pick dirapikan**
      — kolom qty sekarang rata kanan konsisten (header + isi input angka),
      sebelumnya header "Outstanding" rata kanan tapi header qty di
      sebelahnya rata kiri, kelihatan berantakan. Fix di CSS global
      (`.line-items-table input[type="number"] { text-align:right; }`) +
      tambah class `ta-right` ke header terkait di Put Away & Picking.
    - **Pemilih lokasi rak 3D** (permintaan terbesar) — sebelumnya Rack
      Location cuma input teks + `<datalist>` biasa, user bilang bikin
      bingung. Dibuat modal baru berisi visual 3D (REUSE gaya & teknik dari
      Rack Monitoring: kamera orbit drag/scroll, raycasting klik box, label
      teks di canvas texture) yang menampilkan 45 slot 1 rak sekaligus —
      hijau = boleh dipilih (ada di `$locationCodes` dari controller, EMPTY
      untuk Put Away / FULL untuk Picking), abu-abu = tidak bisa diklik.
      Ada tab R1-R8 buat pindah rak. Klik slot hijau -> isi otomatis input
      lokasi baris terkait -> modal tutup. File baru (dipakai bareng Put
      Away & Picking, DRY): `resources/views/partials/rack-picker-modal.blade.php`
      (HTML modal) + `resources/views/partials/rack-picker-script.blade.php`
      (JS Three.js, terima `$locationCodes` dari scope parent). Diverifikasi
      lewat: cek DOM ter-render benar (PICKABLE set berisi kode asli),
      `node --check` buat validasi sintaks JS (tidak ada browser automation
      terpasang di environment ini buat tes klik interaktif sungguhan — jadi
      confidence-nya dari kode yang di-reuse persis dari Rack Monitoring yang
      SUDAH terbukti jalan, bukan dari klik manual yang diverifikasi).
      Input teks manual TETAP ada (tidak dihapus) sebagai alternatif kalau
      staf sudah hafal kodenya, tombol 3D cuma opsi tambahan di sebelahnya.
    - **Export PDF untuk Put Away** — pola identik ASN/GRN (poin 16/19):
      route `GET putaways/{putaway}/pdf`, `PutawayController::exportPdf()`,
      view `inbound/putaways/pdf.blade.php`, watermark "SWIS POLIBATAM".
      Field: Source GRN, Supplier, Status, Date, Staff, tabel lines
      (Component/Description/Qty/Location/Confirmed At/Confirmed By).
    - Semua sudah dites end-to-end lewat HTTP asli (bukan cuma buka
      halaman): submit Put Away sungguhan, cek Confirmed At jamnya benar
      (bukti fix timezone), download PDF, screenshot halaman show & create
      buat verifikasi visual. 15 halaman lain dicek tetap 200.
21. **"Put Away Summary" DIPENSIUNKAN sebagai menu sidebar, DIPINDAH jadi
    tab "D" di Reports** (2026-09-20, atas usulan user — dan SETUJU, ini IA
    yang lebih benar): user menunjukkan Put Away Summary itu sifatnya
    laporan/agregat read-only (sama seperti Report A/B/C yang sudah ada di
    Reports), bukan langkah kerja transaksional seperti ASN/GRN/Put Away —
    jadi seharusnya tidak jadi menu sidebar sendiri.
    - Sidebar Inbound sekarang kembali **4 item** (Suppliers, ASN, Goods
      Receive, Put Away) — simetris dengan Outbound yang juga 4 item.
    - `LaporanController` (sistem monitoring LAMA) sekarang **query juga ke
      tabel WMS** (`wms_putaway_lines`/`wms_grn_lines`, lewat method baru
      `RackTrackingService::putAwaySummary()` & `putAwaySummaryByLocation()`)
      — ini PERTAMA KALI controller lama query tabel `wms_*`. Ini BUKAN
      pelanggaran prinsip "additive, jangan sentuh sistem lama" — prinsip
      itu soal WMS tidak boleh MENULIS ke tabel lama tanpa izin; ini cuma
      MEMBACA (read-only) tabel WMS dari controller lama, atas permintaan
      eksplisit user, murni penggabungan UI. `warehouse_stock`/
      `m_matrix_storage_bin` tetap tidak disentuh WMS sama sekali.
    - Tab baru "D · WMS Put Away Summary" di `laporan/index.blade.php`,
      ikut pola 3 tab lama persis (checkbox export, filter search, dst) —
      rack-filter DIMATIKAN khusus tab ini (belum ada logic filter-by-rack
      di `putAwaySummary()`, daripada tampil tapi tidak berefek/membingungkan).
      Export CSV & PDF (`laporan/pdf.blade.php`) ikut ditambah section D.
    - **`OnHandController` & route `inbound.on-hand` DIHAPUS** — URL lama
      `/on-hand` di-`Route::redirect()` ke `/laporan?tab=putaway` (bukan
      404 mendadak, buat jaga-jaga kalau ada yang bookmark). View lama
      `resources/views/inbound/on-hand/` dihapus.
    - Dites: tab D tampil data asli (5 component, breakdown lokasi benar),
      export CSV & PDF section D berfungsi, tab A/B/C & export lama TIDAK
      berubah perilakunya, redirect `/on-hand` -> `/laporan?tab=putaway`
      jalan (302), 13 halaman lain tetap 200.
22. **Tombol "Back to List" dikasih border+background** (2026-09-21) — versi
    sebelumnya (poin 20, cuma teks+ikon polos) ternyata masih dianggap
    "tidak ramah dimata" oleh user. Sekarang `.back-link` (satu class,
    dipakai di 6 halaman) jadi bentuk pill: `rounded-full border
    border-slate-400 bg-white`, hover jadi `border-indigo-600 bg-indigo-50`.
    Sengaja bentuk BULAT (bukan persegi seperti `.btn-primary`/`.btn-secondary`)
    biar tetap gampang dibedakan dari tombol aksi lain meski sekarang
    sama-sama punya border+bg.
23. **Default UOM "pc" -> "Pcs" di semua form** (2026-09-21) — user perhatikan
    konsisten "pc" huruf kecil di semua line-item template (ASN, GRN, Sales
    Order, Delivery Order manual entry) padahal di tempat lain (badge qty,
    dst) sudah "Pcs". Diseragamkan jadi "Pcs" di 4 file `<template>` HTML +
    2 fallback JS (`?? 'pc'` di auto-fill known-component ASN & GRN). Field
    tetap bisa diedit manual, ini cuma nilai default awal.
24. **Export PDF untuk Sales Order** (2026-09-21) — pola sama seperti ASN/
    GRN/Put Away (poin 16/19/20): route `GET sales-orders/{salesOrder}/pdf`
    (`outbound.sales-orders.pdf`), `SalesOrderController::exportPdf()`,
    view `outbound/sales-orders/pdf.blade.php`, watermark "SWIS POLIBATAM".
    Field: Customer, Order Date, Status, Required Date, Remark, tabel lines
    (Part Code/Description/Qty Ordered/Qty Picked/Qty Remaining/UOM).
    **Tambahan baru dari referensi PDF user** (bagian yang MEMANG relevan,
    beda dari Container No/BL/AJU yang dibuang di ASN/GRN sebelumnya):
    blok tanda tangan "Prepared By" / "Approved By" di bagian bawah (garis
    kosong buat ditandatangani fisik setelah dicetak) — masuk akal untuk
    dokumen yang akan dicetak & disetujui manual, tidak butuh data
    tersimpan apa pun, murni elemen cetak. Tombol "Download PDF" ditambah
    di halaman show SO. Dites end-to-end (buat SO sungguhan -> download PDF
    -> isi PDF diverifikasi benar -> data uji dihapus), 8 halaman lain
    dicek tetap 200.
25. **Export PDF untuk Picking** (2026-09-21) — pola sama seperti
    ASN/GRN/Put Away/SO: route `GET pickings/{picking}/pdf`
    (`outbound.pickings.pdf`), `PickingController::exportPdf()`, view
    `outbound/pickings/pdf.blade.php`, watermark "SWIS POLIBATAM". Field:
    Source SO, Customer, Status, Date, Staff, tabel lines (Part
    Code/Description/Lot No/Qty Picked/Pick Location) + kolom **Check**
    (kotak centang kosong per baris, buat staf centang fisik saat verifikasi
    barang yang diambil — diadaptasi dari referensi PDF user yang juga
    punya kolom serupa). Tanda tangan "Picked By" / "Checked By" di bawah
    (sama polanya dengan Prepared By/Approved By di SO, cuma label beda
    sesuai konteks picking). Tombol "Download PDF" ditambah di halaman show
    Picking. Dites pakai record NYATA yang sudah ada (Picking id=2, bukan
    data buatan sesi ini, jadi tidak perlu dihapus) — isi PDF diverifikasi
    benar, 11 halaman lain dicek tetap 200.
26. **Export PDF untuk Delivery Order — MELENGKAPI semua 6 dokumen WMS**
    (2026-09-21): route `GET delivery-orders/{deliveryOrder}/pdf`
    (`outbound.delivery-orders.pdf`), `DeliveryOrderController::exportPdf()`,
    view `outbound/delivery-orders/pdf.blade.php`, watermark "SWIS
    POLIBATAM". Field: Sales Order, Customer, Source Picking, Status,
    Delivery Date, Vehicle No, Driver, Remark, tabel lines (Part
    Code/Description/Qty Delivered/UOM/Lot No). Tanda tangan "Created By" /
    "Acknowledged By (Receiver)" — label disesuaikan konteks DO (customer
    yang menerima barang, bukan staf internal, makanya labelnya beda dari
    Prepared/Approved di SO atau Picked/Checked di Picking). Ketemu &
    sekalian diperbaiki 1 sisa `?? 'pc'` (huruf kecil) di
    `DeliveryOrderController::create()` yang lolos dari sapuan poin 23
    kemarin (itu cuma nyapu file `resources/views`, bukan `app/Http`).
    Dites pakai record NYATA (DeliveryOrder id=2) — isi PDF diverifikasi
    benar (termasuk UOM lama "pc" lowercase dari sebelum fix poin 23, itu
    memang data lama, bukan bug baru), 11 halaman lain dicek tetap 200.

    **Modul PDF WMS sekarang LENGKAP untuk semua 6 dokumen**: ASN (16),
    GRN (19), Put Away (20), Sales Order (24), Picking (25), Delivery
    Order (26) — semua pakai pola letterhead+watermark "SWIS POLIBATAM"
    yang identik, field yang sungguhan ada di sistem (bukan field customs
    dari PDF referensi yang tidak relevan), dan tanda tangan fisik di
    dokumen yang masuk akal untuk dicetak (SO/Picking/DO).
27. **Simplifikasi halaman Laporan (`/laporan`)** (2026-09-21) — dipicu user
    bilang "masih terhitung tidak ramah pengguna" lalu minta "langsung
    keempatnya" atas 4 usulan yang saya ajukan. Semua di
    `resources/views/laporan/index.blade.php` + CSS baru di `app.css`,
    tanpa ubah `LaporanController` (logic filter/section-nya sudah
    mendukung ini dari awal):
    1. Blok checkbox "Include in download" (4 section A-D) dijadikan
       `<details>`/`<summary>` native HTML — collapsed by default, expand
       kalau user memang mau kustomisasi. Zero-JS, tetap ikut ke-submit
       form walau visualnya tertutup karena `LaporanController::readFilters()`
       sudah fallback ke `[$activeTab]` kalau `sections[]` tidak dikirim.
    2. Caption kontekstual pendek (`.filter-context-note`) di atas baris
       filter, isinya beda per tab (pakai `@switch($activeTab)`) — supaya
       field filter yang muncul/hilang antar tab (search/rack/tanggal)
       terasa disengaja, bukan seperti bug UI.
    3. Tombol "Print" (window.print()) dihapus dari `.report-actions` —
       redundan karena tombol PDF sudah cover kebutuhan cetak/simpan.
    4. Tab D ("WMS Put Away Summary") dikasih class `.rack-tab-warn`
       (border+text amber, kalau aktif jadi solid amber) supaya beda
       secara visual dari Tab A/B/C yang indigo — sinyal "laporan ini
       beda jenis" tanpa user harus baca banner info di bawahnya dulu.
       Dites: `npm run build` sukses, `php artisan view:cache` (blade
       compile check) sukses, curl semua 4 tab (`stok`/`utilisasi`/
       `mutasi`/`putaway`) tetap 200, screenshot headless Chrome
       (`tab=stok` & `tab=putaway`) diperiksa visual — hasil sesuai: tab
       D oranye, caption filter berubah per tab, checkbox tertutup
       default, tombol Print sudah tidak ada.
28. **Follow-up refinement halaman Laporan** (2026-09-21, sama hari,
    dipicu screenshot user atas hasil poin 27) — 4 hal:
    1. Header kolom yang bisa di-sort (Part Code/Item Name/Total Qty di
       Report A, Part Code/Item Name/Total In/Out/Net/Box Count di
       Report C) tidak lagi berubah warna emas + tidak lagi tampil
       panah naik/turun saat jadi kolom aktif — dihapus `.th-sort.active`
       di `app.css` dan pemanggilan `$sortIcon(...)` di blade. Sortir
       tetap jalan (klik header tetap reorder data via query
       `sort`/`dir`), cuma indikator visualnya yang dibuang.
    2. Sel Part Code di tabel Report A/C/D (`$item->component`,
       `$m->component`, `$p->component`) dilepas dari class `.mono` —
       user merasa warnanya beda dari kolom lain. Dicek pixel-level
       pakai screenshot + sampling warna (Python PIL): HEX-nya
       sebenarnya identik (`rgb(15,23,43)`, slate-900 bawaan body) di
       kedua kolom — bedanya cuma kesan visual dari font monospace vs
       proporsional di ukuran kecil. Class tetap dilepas supaya
       glyph-nya benar-benar sama, menghilangkan kesan beda itu.
    3. Badge ikon-warning+angka di tab "B · Rack Utilization" diganti
       jadi teks polos "(1)" tanpa ikon — user minta ikonnya
       diubah/dihilangkan.
    4. Nama tab D "WMS Put Away Summary" -> "Put Away Summary" (buang
       prefix "WMS") di semua tempat: label tab, checkbox include-in-
       download, judul panel Report D, komentar Blade, dan judul di
       `laporan/pdf.blade.php` — supaya konsisten dengan tab A/B/C yang
       memang tidak pakai prefix modul.
    Dites: `npm run build` sukses, `php artisan view:clear` + `php -l`
    (syntax check) sukses, semua 4 tab tetap 200, query sort
    (`sort=sku&dir=asc`) tetap 200, screenshot ulang diperiksa visual.
29. **Menu baru "Component Master" — katalog komponen untuk registrasi ke
    aplikasi mainform** (2026-09-21), dipicu pertanyaan user soal istilah
    "pemberian nama" komponen baru di form ASN, lalu diminta buatkan menu
    baru. Tabel baru `wms_component_masters` (component_code unique,
    component_name, default_uom, qty_label, source manual/asn) —
    **beda tujuan dari `wms_parts` yang dulu dihapus** (lihat poin
    sebelumnya): itu untuk normalisasi FK internal yang tidak perlu, ini
    untuk katalog referensi eksternal (dipakai isi manual ke aplikasi
    barcode "Hiik-code" di luar sistem). Dua jalur pengisian: manual
    lewat menu baru, ATAU otomatis ke-upsert (`firstOrCreate`) saat kode
    baru diketik di form ASN (`AsnController::store()`), plus ASN's
    known-components datalist sekarang gabungan dari `warehouse_stock`
    DAN `wms_component_masters` supaya auto-fill nama jalan dari kedua
    sumber. Sidebar dapat section baru "Master Data" (ditaruh sebelum
    Inbound). Tombol "Print (selected)" awalnya sempat dirancang generate
    gambar barcode Code128 (sampai pasang package
    `picqer/php-barcode-generator` — instalasinya berkali-kali macet di
    `unzip.EXE` bawaan Git Bash sampai akhirnya ketahuan `ext-zip` PHP
    belum aktif di `C:\php83\php.ini`, diaktifkan), TAPI setelah user
    klarifikasi barcode-nya memang dicetak langsung dari aplikasi
    Hiik-code (bukan dari sini), package itu **dilepas lagi** (composer.json
    & composer.lock dibersihkan) dan tombolnya disederhanakan jadi
    "Download PDF (selected)" — cuma export PDF daftar Component
    Code/Name/UOM/Qty (pola sama dengan PDF WMS lain), tanpa gambar
    barcode. Beberapa ronde revisi tampilan menyusul (semua dipicu
    feedback user langsung): banner/caption penjelasan dihapus (ternyata
    ini sudah jadi aturan tetap sejak poin sebelumnya untuk semua
    halaman WMS — sempat lupa diterapkan ke menu baru ini), tombol
    "+ Add Component"/"Save Component" awalnya dikasih warna ungu custom
    lalu diganti biru (`.btn-primary-in`) menyamai ASN/Inbound, garis
    atas form dihapus (ASN juga tidak pakai), TAPI warna sidebar "Master
    Data" dikembalikan ke ungu (`.nav-section-label-master`/
    `.nav-item-master` — direstore di `app.css`) karena user minta warna
    itu tetap dipertahankan meski tombol form-nya biru. Ikon panah
    download juga dibuang dari tombol PDF (disamakan pola "Download PDF"
    polos tanpa ikon yang sudah dipakai di semua show page WMS) — minta
    ini juga memicu pembersihan ikon panah yang sama di tombol
    Excel/PDF halaman Laporan (poin 30).
30. **Ikon panah download dibuang dari tombol export Laporan** (2026-09-21,
    imbas langsung dari poin 29) — tombol "Excel"/"PDF" di
    `laporan/index.blade.php` yang tadinya `&#8681; Excel`/`&#8681; PDF`
    diubah jadi teks polos, lalu atas permintaan user ditambah kata
    "Download" juga → "Download Excel"/"Download PDF", konsisten dengan
    tombol PDF di halaman show WMS lain.
31. **Dashboard: panel baru "WMS Activity"** (2026-09-21) — dipicu
    pertanyaan user "menu sudah banyak, ada yang perlu direvisi di
    dashboard?". Diagnosis: 4 card lama (`DashboardController` lewat
    `RackTrackingService`) semuanya soal rack/warehouse_stock, TIDAK ada
    satupun yang merefleksikan modul WMS (ASN-DO) atau Component Master
    yang baru dibangun. Solusi yang dipilih (bukan 1 card per langkah WMS
    biar tidak penuh): 1 panel ringkas berisi daftar hal yang butuh
    tindak lanjut, cuma tampil kalau count > 0 (kalau semua beres,
    tampil "All caught up"). 5 kemungkinan baris (dihitung di
    `DashboardController::wmsActivity()`, query `whereHas` + raw
    subquery buat qty outstanding, BUKAN load semua baris ke PHP):
    ASN draft belum dikonfirmasi, GRN dengan sisa Put Away, SO draft
    belum dikonfirmasi, SO confirmed yang masih ada sisa qty belum
    dipicking, dan Component Master asal ASN yang qty_label-nya masih 0
    (belum siap dicetak). Tiap baris klik langsung ke halaman terkait
    dengan filter status yang sesuai. Ikut pola auto-refresh 30 detik
    yang sudah ada (`dashboard.data` JSON endpoint + `renderWmsActivity()`
    JS), jadi tidak perlu reload halaman. Dites: query manual via tinker
    reflection (hasil cocok sama data asli: 1 ASN draft), endpoint
    `/dashboard` & `/dashboard/data` 200, `npm run build` sukses,
    screenshot diperiksa visual.
32. **Card "Top Item by Stock" -> "Items Running Low"** (2026-09-21) —
    user tanya apa card ini perlu diganti; setelah diskusi beberapa opsi
    (Racks Near Full, Dead Stock, Throughput, dll) user pilih "Items
    Running Low". Sempat dicek dulu: **tidak ada kolom minimum-stock/
    reorder-point di database manapun**, jadi "low" tidak bisa dihitung
    terhadap ambang batas nyata — ditanyakan ke user via pertanyaan
    pilihan, dipilih opsi paling jujur (bukan minimum-stock buatan/nebak
    angka): item dengan qty TERENDAH saat ini secara relatif, mirror
    persis logika `topItemByStock()` yang lama cuma dibalik arah sortir
    ('asc' bukan 'desc'). `RackTrackingService::topItemByStock()`
    **dihapus** (sudah tidak ada pemanggil lain, dicek grep dulu sebelum
    hapus) diganti `lowestItemByStock()`, tetap pakai `havingRaw('SUM(qty)
    > 0')` yang sudah ada di `stockPerItem()` jadi item dengan stok 0
    otomatis tidak muncul (bukan exclude tambahan). Icon card diganti
    dari bintang jadi trending-down. Dites: JSON `/dashboard/data`
    dicek manual — hasil `lowest_item` cocok data asli (B12 "Cap" qty 3,
    lebih rendah dari B13 "AIR MINERAL" qty 14 yang dulu jadi top item),
    `npm run build` sukses, screenshot diperiksa visual.
33. **ASN sekarang WAJIB relasi ke Component Master — tidak bisa lagi
    auto-create komponen baru** (2026-09-21), REVISI dari poin 29. User
    minta: hapus kolom Source di Component Master, source-nya dianggap
    manual saja, dan baris ASN "pakai relasi dari master, jangan daftar
    langsung di ASN". Perubahan:
    - Migration baru `drop_source_from_wms_component_masters_table` —
      kolom `source` (manual/asn) DIHAPUS dari `wms_component_masters`
      (bukan cuma disembunyikan dari tampilan). Model & controller ikut
      dibersihkan dari referensi `source`.
    - `AsnController::create()`: variable `$knownComponents` (gabungan
      warehouse_stock + Component Master, dari poin 29) **dihapus**,
      diganti `$components` = `ComponentMaster::orderBy('component_code')
      ->get()` — SATU-SATUNYA sumber sekarang, tidak lagi baca
      warehouse_stock sama sekali untuk form ASN. `RackTrackingService
      $rack` juga dilepas dari constructor karena sudah tidak dipakai.
    - Form ASN (`inbound/asns/create.blade.php`): kolom "Component Code" +
      "Component Name" (2 input teks bebas + datalist) diganti jadi SATU
      kolom "Component" berisi `<select>` pilih dari Component Master —
      **persis pola yang sudah dipakai Sales Order untuk `matrix_partcode_id`**
      (`<option>{{ code }} — {{ name }}</option>`), bukan bikin pola baru.
      UOM tetap auto-terisi dari `default_uom` milik komponen terpilih
      (`data-uom` di option + JS `change` listener), field tetap bisa
      diedit manual kalau pengiriman pakai satuan beda.
    - `AsnController::store()`: validasi `lines.*.component` sekarang
      `exists:wms_component_masters,component_code` (menolak kode yang
      belum terdaftar — dites lewat HTTP asli, submit kode
      `NOTREGISTERED` hasilnya redirect back TANPA bikin ASN). Field
      `component_name` TIDAK lagi diterima dari client — nama selalu
      di-lookup ulang di server dari Component Master berdasarkan kode
      yang dipilih, supaya nama yang tersimpan di baris ASN selalu
      konsisten dengan master (tidak bisa disisipi nama beda dari yang
      terdaftar). Blok `ComponentMaster::firstOrCreate(...)` yang dulu
      auto-registrasi kode baru (poin 29) **dihapus total**.
    - Dashboard `wmsActivity()`: baris "Component (from ASN) needs Qty on
      Label filled" diganti jadi "Component Master with Qty on Label not
      yet set" (filter `source='asn'` dihapus, sekarang berlaku ke SEMUA
      komponen tanpa pandang asal — karena source sudah tidak ada lagi).
    - Konsekuensi yang perlu diketahui: kalau field Component Master
      kosong (belum ada komponen didaftarkan sama sekali), form ASN akan
      menampilkan pesan "No components registered... Add one there
      before creating an ASN" dan dropdown-nya kosong — user WAJIB ke
      Component Master dulu sebelum bisa bikin ASN pertama kali. Ini
      memang tujuan yang diminta user dari awal ("mendaftarkan
      item/component-nya dulu sebelum di menu asn").
    - GRN blind-receive (`grns/create.blade.php` tanpa ASN) **TIDAK
      disentuh** — user cuma minta perubahan ini "di menu ASN", jadi GRN
      tetap pakai `RackTrackingService::knownComponents()` (dari
      warehouse_stock) seperti sebelumnya, belum direlasikan ke Component
      Master.
    Dites lengkap: tinker (validasi exists lolos/gagal sesuai harapan,
    component_name ke-resolve benar dari master), HTTP asli end-to-end
    (buat ASN via curl dengan kode terdaftar → berhasil & nama server-side
    benar; submit kode tak terdaftar → ditolak, tidak ada row baru),
    regresi semua menu (`dashboard`, `components`, `asns`, `grns`,
    `putaways`, `suppliers`, `laporan`, dst) tetap 200, screenshot
    Component Master (kolom Source sudah hilang) & form ASN (dropdown
    relasi) diperiksa visual.
34. **Follow-up poin 33: Component Code & Component Name dipisah lagi jadi
    2 kolom** (2026-09-21) — versi pertama poin 33 sempat menggabung
    keduanya jadi satu dropdown "Component" (mirip Part Code di SO), tapi
    user minta dipisah lagi kayak sebelumnya: kolom "Component Code" tetap
    `<select>` (relasi ke Component Master, isinya cuma kode), kolom
    "Component Name" jadi `input readonly` terpisah yang otomatis terisi
    ngikutin kode yang dipilih (data `component_name` ditaruh di
    `data-name` pada tiap `<option>`, di-render ke input readonly lewat
    JS `change` listener). Tetap satu relasi/sumber data yang sama
    (Component Master) cuma tampilannya 2 kolom, bukan 1. Dites: dump-DOM
    (data-name/data-uom di semua option cocok data asli), HTTP end-to-end
    (submit kode terdaftar → line tersimpan dengan nama benar dari
    master).
35. **Teks label rak (R1C23 dst) di modal "Pick a Rack Location" diperjelas**
    (2026-09-21) — user lapor teks-nya buram di modal picker Put Away/
    Picking, TAPI di Rack Monitoring aman-aman saja. Diagnosis: kode
    `makeLabelTexture()` (canvas texture 320x128, font 46px) di
    `rack-picker-script.blade.php` itu HASIL COPY PERSIS dari
    `rack/index.blade.php` punya `#rackFront3d` (tampilan utama, yang
    dianggap user sudah jelas) — jadi bukan soal kode teksnya beda,
    tapi soal VIEWPORT-nya lebih kecil: `.rack-front-3d` (tampilan utama)
    tingginya 440px & lebar mengikuti panel konten penuh, sedangkan
    `.rack-picker-3d` (modal) cuma 360px tinggi & max-width 720px — scene
    3D yang sama (sudut kamera & jarak sama persis) dirender ke area
    piksel yang lebih kecil, jadi tulisan yang sama besarnya secara
    proporsional keliatan lebih kecil & buram di layar. Perbaikan
    (`app.css` + `rack-picker-script.blade.php`): `.rack-picker-3d`
    440px tinggi (disamakan persis dengan `.rack-front-3d`),
    `.rack-picker-modal` max-width 720px->860px, DAN resolusi
    canvas+font label dibesarkan proporsional 1.25x (320x128/46px ->
    400x160/58px) buat margin ekstra kejelasan. Dites: bikin harness
    HTML statis terpisah (load CSS build + three.min.js asli, buka modal
    otomatis via `openRackPicker()` tanpa perlu data GRN/Put Away nyata,
    karena data uji buat itu ribet disiapkan cuma buat screenshot) —
    hasil screenshot dibandingkan sebelum/sesudah, tulisan kode slot
    (R1C11 dst) jelas terbaca. Regresi: `putaways/create`,
    `pickings/create`, `rack` (Monitoring, dicek TIDAK ikut berubah
    karena filenya beda), `dashboard` tetap 200.
36. **Follow-up poin 35: masih buram, root cause sebenarnya sudut kamera
    miring (bukan cuma resolusi/viewport)** (2026-09-21) — user kirim
    screenshot zoom-in, teksnya masih keliatan blur meski poin 35 sudah
    naikkan resolusi & viewport. Diagnosis ulang: kamera picker ini
    isometrik (miring), TEKS DI PERMUKAAN YANG DILIHAT MIRING itu kena
    "anisotropic aliasing" — beda kelas masalah dari sekadar "resolusi
    kurang", texture filtering standar (mipmap biasa) memang lemah buat
    kasus ini. Perbaikan tambahan di `rack-picker-script.blade.php`:
    - Resolusi canvas dinaikkan LAGI, 400x160/58px -> **640x256/93px**.
    - **`texture.anisotropy = renderer.capabilities.getMaxAnisotropy()`**
      ditambahkan ke tiap label texture — ini fix yang tepat sasaran
      buat blur akibat sudut pandang miring (bukan cuma "gede-gedein
      angka" seperti sebelumnya).
    - `renderer.setPixelRatio` cap dinaikkan 1.5 -> 2, dan
      `antialias: false` -> `true` — worth it karena modal ini cuma
      render 1 rack/45 box sekaligus (jauh lebih ringan dari Rack
      Monitoring yang alasan awal kenapa 1.5/false dipilih di sana).
    - Rack Monitoring (`rack/index.blade.php`) TETAP TIDAK disentuh
      (scope cuma di picker Inbound/Outbound, sesuai poin 35).
    Dites: harness HTML sama seperti poin 35 (build ulang dulu),
    screenshot di-crop 4x lipat pakai Python PIL buat cek ketajaman di
    close-up ekstrem — hasilnya tulisan (R1C11 dst) tetap tajam, tidak
    blur lagi. Regresi: `putaways/create`, `pickings/create`, `rack`,
    `dashboard`, `asns/create` tetap 200.
37. **Follow-up poin 36: user masih lapor blur di screenshot tampilan
    NORMAL (bukan zoom-in)** (2026-09-21) — root cause TERNYATA bukan
    (cuma) resolusi tekstur, tapi ukuran tampilan di layar terlalu kecil:
    di zoom default sebelumnya, 45 slot sekaligus muat dalam 1 frame yang
    relatif kecil (modal 860px, viewport 3D 440px), jadi tiap label cuma
    dapat beberapa belas piksel layar — sekecil apapun sumbernya tetap
    kebaca buram kalau target render-nya sekecil itu. Perbaikan (BUKAN
    nambah resolusi tekstur lagi, tapi bikin semuanya tampil lebih besar
    di layar):
    - `dist` default kamera dipepetin dari `COLUMNS*1.5` ke `COLUMNS*1.05`
      (rak memenuhi frame lebih banyak, tidak nyisa banyak ruang kosong
      di sekitarnya) + batas zoom scroll ikut dipepetin (`0.9-2.4` ->
      `0.7-2.0`) biar user juga bisa zoom lebih dekat lagi kalau perlu.
    - Modal & viewport 3D dibesarkan signifikan: `.rack-picker-modal`
      max-width 860px -> **1100px**, `.rack-picker-3d` tinggi 440px ->
      **580px**.
    Kombinasi "kamera lebih dekat" + "viewport lebih besar" bikin tiap
    label dapat jauh lebih banyak piksel layar dibanding poin 35/36 yang
    cuma naikin resolusi sumbernya doang. Dites: harness HTML yang sama
    (skenario persis kayak screenshot user — 9 kolom terlihat, kode
    berakhiran angka baris berbeda-beda R1C95 s/d R1C15 dst), hasil
    screenshot dibandingkan — SEMUA label termasuk yang di kotak
    putih/abu-abu (paling susah dibaca sebelumnya) sekarang jelas.
    Regresi: `putaways/create`, `pickings/create`, `rack`, `dashboard`,
    `asns/create`, `components` tetap 200.
38. **Nama aplikasi "SWISS WMS" -> "WMS SWIS"** (2026-09-21) — urutan kata
    dibalik atas permintaan user. Diubah di semua tempat:
    `APP_NAME` di `.env` & `.env.example` (ini yang nentuin judul tab
    browser lewat `config('app.name')` di `layouts/app.blade.php`, jadi
    otomatis ke-apply ke semua halaman tanpa perlu ubah per-view), footer
    di 7 PDF (ASN/GRN/Put Away/SO/Picking/DO/Component List) +
    judul di `laporan/pdf.blade.php`, komentar docblock di
    `RackTrackingService.php`, dan baris pembuka `DATABASE.md`. Watermark
    PDF "SWIS POLIBATAM" TIDAK ikut berubah (itu nama sistem+institusi,
    beda dari nama app "SWISS WMS"/"WMS SWIS" yang dipakai di footer —
    dicek jangan sampai ketuker pas replace). `config:clear` dijalankan
    biar config cache lama (kalau ada) tidak nyangkut nama lama. Dites:
    title tag browser (`curl` cek `<title>`) berubah jadi "... — WMS
    SWIS", PDF ASN nyata (id=2) di-download & footer-nya kebaca "WMS
    SWIS — Politeknik Negeri Batam", regresi semua menu tetap 200.
39. **`matrix_partcode` DILEPAS dari Outbound (SO/Picking/DO) — diganti stok
    riil (`warehouse_stock`)** (2026-09-23) — bug produksi: user deploy ke
    komputer SWIS (`git pull` + migrate), buka Sales Order → dropdown Part
    Code kosong, pesan "No finished-good part codes found in matrix_partcode".
    Investigasi: `matrix_partcode` TERNYATA tidak pernah terisi lewat jalur
    manapun di operasional nyata — asumsi lama ("tabel master yang sudah ada
    isinya, dipakai ulang apa adanya", lihat poin sebelumnya soal FK Outbound)
    ternyata cuma benar di database DEV (2 baris data uji manual: "10001 Botol
    Aqua", "12 MMGP Mineral", dibuat sendiri buat testing dari sesi-sesi awal).
    Satu-satunya data yang benar-benar mengalir dari operasional nyata adalah
    barang yang di-scan & dikirim ke rack lewat MainForm (masuk ke
    `warehouse_stock`, yang sama dibaca Report A "Stock per Item" & Component
    Master). User awalnya ditawari 2 opsi (pindah ke Component Master, atau isi
    matrix_partcode manual) — user MINTA opsi ketiga: pakai sumber yang sama
    dengan Report A Stock per Item (bukan Component Master, karena "di
    componen master bisa jadi belum di database" — Component Master cuma
    katalog/pendaftaran, belum tentu barangnya benar-benar ada stok).
    Perubahan:
    - Migration baru `replace_matrix_partcode_with_component_on_outbound_lines`
      — `matrix_partcode_id` FK **dihapus** dari `wms_so_lines`,
      `wms_picking_lines`, `wms_do_lines`, diganti kolom `component` +
      `component_name` string (persis pola `wms_asn_lines`). **Backfill
      otomatis** dari FK lama ke kolom baru dijalankan di migration (join ke
      `matrix_partcode` sebelum kolomnya dihapus) — riwayat SO/Picking/DO lama
      TIDAK hilang, dicek manual lewat tinker (SO id=2 dkk tetap kebaca
      "10001 Botol Aqua" dengan benar setelah migrate).
    - `SalesOrderController`, `PickingController`, `DeliveryOrderController`:
      semua query `MatrixPartcode::...` diganti
      `RackTrackingService::stockPerItem()` (RackTrackingService di-inject ke
      SalesOrderController & DeliveryOrderController, sebelumnya tidak pakai).
      Validasi `lines.*.matrix_partcode_id` -> `lines.*.component` dengan rule
      `exists:warehouse_stock,component` (cuma barang yang benar-benar ada
      catatan stoknya yang bisa dipakai). `component_name` di-resolve ulang di
      server dari `stockPerItem()` (bukan dari input client), sama seperti pola
      ASN.
    - Form SO & DO (mode manual): dropdown Part Code sekarang nampilin qty
      stok di label opsinya, mis. "B13 (14 ml in stock)" — biar user langsung
      lihat ketersediaan pas milih, bukan baru ketahuan pas submit gagal.
      Form SO juga dipisah jadi 2 kolom (Part Code + Part Name, readonly
      auto-fill) mengikuti pola ASN yang sudah disetujui user sebelumnya.
    - Semua view show/pdf (SO, Picking, DO) yang tadinya baca
      `$l->matrixPartcode->partcode`/`model_name` diganti `$l->component`/
      `$l->component_name` langsung.
    - `app/Models/MatrixPartcode.php` TIDAK dihapus (masih dipakai `BomList`),
      cuma sudah tidak dipakai modul WMS lagi.
    Dites: lint semua file berubah, `npm run build`, regresi 12 halaman
    (dashboard, components, asns/create, sales-orders index+create,
    pickings index+create, delivery-orders index+create, laporan, rack,
    suppliers) tetap 200, HTTP end-to-end asli (buat SO dgn component
    stok nyata "B13" -> berhasil & nama ke-resolve benar; submit component
    yang tidak ada stoknya -> ditolak, tidak ada row baru), screenshot form
    SO (dropdown isi stok riil, bukan kosong lagi) & show page (data lama
    ASN id=2 tetap utuh) diperiksa visual.
40. **3 perbaikan kecil + fitur Edit ASN/Sales Order** (2026-09-24), dari 1
    pesan besar user (screenshot PDF SO/Picking/DO):
    1. **PDF Component Master langsung download** — `ComponentMasterController
       ::print()` pakai `->stream()` (buka preview di tab baru), diganti
       `->download()` supaya konsisten dengan 6 PDF WMS lain yang semua
       sudah pakai `->download()` sejak awal (baru ketahuan pas dicek —
       cuma Component Master yang kelewatan).
    2. **Teks "Name / Date" dihapus dari kotak tanda tangan** SO ("Prepared
       By"/"Approved By"), Picking ("Picked By"/"Checked By"), DO ("Created
       By"/"Acknowledged By") — user tunjuk screenshot, teks itu bikin
       kesan ada data yang seharusnya keisi otomatis padahal itu memang
       cuma placeholder buat tanda tangan fisik. `.sign-line` sekarang
       cuma garis kosong, `<div class="sign-line"></div>` tanpa isi teks.
    3. **Fitur Edit untuk ASN & Sales Order** — user tanya apa perlu edit
       di 6 menu (ASN/GRN/Put Away/SO/Picking/DO); ditanya balik dulu soal
       skema karena GRN/Put Away/Picking/DO itu TIDAK punya tahap draft
       (begitu disimpan langsung dianggap sudah terjadi fisik — barang
       sudah diterima/ditaruh/diambil/dikirim), beda dari ASN/SO yang
       punya draft->confirm. User pilih: **ASN & SO bisa diedit selama
       masih Draft, GRN/Put Away/Picking/DO TIDAK BISA diedit sama
       sekali** (paling aman, kalau salah harus buat dokumen baru).
       - Route baru `GET/PUT asns/{asn}/edit` & `GET/PUT
         sales-orders/{salesOrder}/edit`. Guard di `edit()` DAN `update()`
         (bukan cuma di edit() — kalau bukan draft, redirect + error,
         dicek dari 2 sisi supaya tidak bisa di-bypass lewat POST
         langsung).
       - View `edit.blade.php` baru untuk keduanya, isinya mirror
         `create.blade.php` tapi prefilled + `@method('PUT')`, dan JS-nya
         perlu prefill baris yang SUDAH ADA (bukan cuma 1 baris kosong
         kayak create) lewat fungsi `addRow(prefill)` yang set value
         select lalu **dispatch event 'change' manual** biar Component
         Name/Part Name ikut auto-fill dari data attribute.
       - Tombol "Edit ASN"/"Edit Sales Order" cuma muncul di show page
         kalau `status === 'draft'`.
       - **Bug ketemu & diperbaiki saat testing** (2 kelas bug beda,
         BUKAN sekadar validasi tambahan):
         a. `@json($model->lines->map(fn ($l) => [...4+ keys...]))` yang
            ditulis LANGSUNG di dalam `<script>` bikin Blade compiler
            error "Unclosed '[' does not match ')'" — directive `@json()`
            Blade ternyata tidak selalu robust untuk expression kompleks
            inline dengan banyak key. Fix: pindahkan komputasi ke
            `@php ... @endphp` block dulu (assign ke variable), baru
            `@json($variable)` yang simpel. Diterapkan ke KEDUA file
            (asns/edit & sales-orders/edit) sekaligus preventif.
         b. `select.dispatchEvent(new Event('change'))` TIDAK bubble
            secara default (constructor `Event` defaultnya `bubbles:
            false`), padahal listener-nya didaftarkan di `body` (event
            delegation), bukan langsung di elemen select — jadi Component
            Name/Part Name selalu kosong pas halaman pertama dibuka meski
            value dropdown-nya sudah benar ke-set. Fix: `new Event
            ('change', { bubbles: true })`.
         c. **Data lama yang komponennya sudah tidak ada di stok/Component
            Master** (mis. SO id=5 pakai "10001" dari data uji
            matrix_partcode lama yang sudah tidak ada di `warehouse_stock`
            sejak poin 39) bikin dropdown edit kosong & validasi
            `update()` menolak simpan ulang — padahal harusnya tetap bisa
            dibuka & disimpan ulang tanpa ganti komponennya. Fix:
            "grandfathering" — `edit()` di kedua controller menambahkan
            komponen milik baris yang sudah ada ke daftar pilihan dropdown
            (walau stoknya 0 / tidak ada di Component Master lagi), dan
            `update()` pakai custom closure rule yang meloloskan kode
            yang sudah dipakai sebelumnya di dokumen itu selain yang
            memang valid saat ini.
    Dites: lint semua file, `npm run build`, regresi 17 halaman tetap 200,
    HTTP end-to-end asli (edit+update ASN id=7 & SO id=5 termasuk baris
    "grandfathered" B12/10001 yang sudah tidak ada di stok/master saat
    ini -> berhasil tersimpan, data diverifikasi lewat tinker), guard
    draft-only dicek (edit ASN/SO yang sudah confirmed -> redirect, bukan
    500), screenshot before/after tiap bug (dropdown kosong -> terisi,
    Part Name kosong -> terisi) dibandingkan visual, PDF SO diperiksa
    kotak tanda tangan sudah bersih tanpa teks "Name / Date".

## Kredensial database (dev lokal)

Sama seperti sebelumnya — lihat riwayat project. Ringkas: PostgreSQL 18 lokal,
`db_swis` (user postgres) untuk warehouse_stock dkk, `postgres`/schema `db_agv` untuk
data rack/AGV (read-only, connection `pgsql_agv` di `config/database.php`).

## Status progress

- [x] Analisis dokumentasi resmi PDF + mockup HTML, verifikasi struktur 8×45=360 slot
      cocok dengan data asli
- [x] `RackTrackingService` — semua query terpusat (dashboard cards, rack utilization,
      trend, box contents, search, stock per item, transaction history)
- [x] 4 controller sesuai menu resmi, route lama (Stock In/Out manual, Monitor,
      Lokasi Rak versi awal) **dihapus** — digantikan struktur ini
- [x] Layout Blade mengikuti mockup (IBM Plex, sidebar gelap, Chart.js)
- [x] Dashboard — 4 kartu, bar chart rack, line chart tren 7 hari, panel AI placeholder
- [x] Rack Monitoring — 8 tab, grid 9×5, klik slot (AJAX), pencarian box/item (AJAX)
- [x] Riwayat Transaksi — filter tanggal/rack/event, pagination, catatan keterbatasan
- [x] Laporan — stok per item + utilisasi rack, export CSV & PDF (dompdf terinstall)
- [x] Semua route + JSON endpoint + export dites end-to-end dengan data nyata (curl)
- [x] Modul WMS Inbound (Supplier, ASN, GRN, Put Away, On Hand) — skema, controller,
      route, Blade view, sidebar, CSS, dites end-to-end dengan curl (2026-09-18)
- [x] Modul WMS Outbound (Customer, Sales Order, Picking, Delivery Order) — skema,
      controller, route, Blade view, sidebar, dites end-to-end dengan curl (2026-09-19)
- [ ] Konfirmasi ke programmer: apakah `t_status_rack`/`t_task_order` terisi saat sistem
      live — kalau ya, upgrade Riwayat Transaksi ke posisi historis akurat
- [ ] Setup akses ke database PC proyek asli — nanti saat testing tahap akhir

## Cara jalankan dev server

```
cd InventoryWeb
php artisan serve --port=8000
```
Buka http://127.0.0.1:8000/dashboard
