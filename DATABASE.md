# Database yang Dipakai Sistem Ini

Dokumen ini menjelaskan **database & tabel mana saja** yang benar-benar dibaca oleh
aplikasi **WMS SWIS** ini (nama lama: "SWIS Inventory Tracking" -> "SWISS WMS" ->
"WMS SWIS" 2026-09-21, lihat `PROJECT-NOTES.md`). Modul monitoring aslinya **read-only sepenuhnya** — tidak
pernah `INSERT`/`UPDATE`/`DELETE` ke database manapun, cuma `SELECT`.

> ⚠️ **Pengecualian (2026-09-18): modul WMS baru.** Klaim "read-only sepenuhnya" di
> atas berlaku untuk sistem monitoring 4-menu asli (Dashboard/Rack Monitoring/Riwayat
> Transaksi/Laporan). Ada tabel-tabel **baru** dengan prefix `wms_` (14 tabel, di
> koneksi `pgsql`/database `db_swis` yang sama) yang **DITULIS** (INSERT/UPDATE) oleh
> modul WMS (ASN/GRN/Put Away/Sales Order/Picking/Delivery Order) — lihat bagian
> "Modul WMS" di akhir dokumen ini. Tabel-tabel lama yang dijelaskan di bawah ini
> (warehouse_stock, m_matrix_storage_bin, dst) TETAP read-only seperti biasa, modul
> WMS tidak menyentuhnya sama sekali — kecuali membaca kode rak (`R1C11` dst) sebagai
> referensi lokasi (lewat `RackTrackingService`, belum diimplementasi) dan **menulis
> FK ke `matrix_partcode`** (tabel master produk jadi yang sudah ada, dipakai ulang
> apa adanya — bukan dibuat master baru).

## Ringkasan: 2 koneksi PostgreSQL terpisah

Aplikasi ini terhubung ke **dua database/schema berbeda sekaligus**, keduanya harus
PostgreSQL yang sama dipakai oleh sistem AGV/WinForms yang sudah berjalan di lokasi
SWIS. Konfigurasinya ada di [`config/database.php`](config/database.php), nilai
aktual (host/port/nama db/user/password) diisi lewat file `.env` (lihat
[`.env.example`](.env.example) untuk daftar variabelnya).

| Koneksi (nama di kode) | Env prefix | Schema | Isi | Akses |
|---|---|---|---|---|
| `pgsql` (default) | `DB_*` | `public` (di database `db_swis`) | Data stok/ledger barang | Baca saja |
| `pgsql_agv` | `DB_AGV_*` | `db_agv` | Data status rak/slot dari sistem robot AGV | Baca saja (dipaksa di kode — lihat di bawah) |

Kalau di PC produksi kedua schema ini sebenarnya satu database Postgres yang sama,
isi `DB_*` dan `DB_AGV_*` dengan host/port yang sama, cuma `DB_AGV_SCHEMA=db_agv`
yang membedakan. Kalau terpisah server, sesuaikan `DB_AGV_HOST`/`DB_AGV_PORT` sendiri.

---

## Koneksi 1 — `pgsql` (schema `public`, database `db_swis`)

### Tabel `warehouse_stock` — **tabel paling penting, dipakai hampir semua fitur**

Model: [`app/Models/WarehouseStock.php`](app/Models/WarehouseStock.php)

Ini **ledger/jurnal transaksi**, BUKAN tabel saldo akhir — tiap baris adalah SATU
transaksi masuk atau keluar. Saldo stok saat ini dihitung dengan `SUM(qty) GROUP BY
component` dari seluruh baris.

| Kolom | Arti |
|---|---|
| `component` | Kode/SKU item (mis. `B13`) — disebut **"Part Code"** di tampilan |
| `component_name` | Nama item (mis. `AIR MINERAL`) |
| `unit` | Satuan (`pc`, `ml`, dst) |
| `qty` | Jumlah — **positif = barang masuk, negatif = barang keluar** |
| `bin_name` | Kode box (QR code) — ini yang menjadi penghubung ke tabel `m_matrix_storage_bin.qr_id` di koneksi AGV (lihat di bawah) |
| `remark` | `IN` atau `OUT` |
| `update_date` | Waktu transaksi |

Dipakai oleh: Dashboard (semua kartu ringkasan, chart), Rack Monitoring (pencarian &
isi box), Riwayat Transaksi (seluruh tabel), Laporan (ketiga tabnya: Stok per Item,
Ringkasan Mutasi).

### Tabel lain yang ADA modelnya tapi BELUM dipakai fitur manapun

Model-model ini sudah dibuat (untuk jaga-jaga fitur masa depan), tapi **tidak ada
satupun controller/halaman yang query ke sini saat ini**:

| Tabel | Model | Rencana kegunaan |
|---|---|---|
| `matrix_partcode` | [`MatrixPartcode.php`](app/Models/MatrixPartcode.php) | Master partcode/produk (`partcode`, `model_name`, `moq`, dst) |
| `bom_list` | [`BomList.php`](app/Models/BomList.php) | Bill of material — komponen penyusun tiap partcode |
| `bin_management` | [`BinManagement.php`](app/Models/BinManagement.php) | Master lokasi bin (beda dari status live robot di bawah) |

---

## Koneksi 2 — `pgsql_agv` (schema `db_agv`, READ-ONLY)

### Tabel `m_matrix_storage_bin` — status rak & slot dari sistem robot AGV

Model: [`app/Models/MatrixStorageBin.php`](app/Models/MatrixStorageBin.php)

**Read-only dipaksa di level kode** — method `save()` dan `delete()` di model ini
sengaja dibuat langsung `throw` error, supaya tidak ada bagian aplikasi web ini yang
bisa menulis ke data robot walau tidak sengaja.

| Kolom | Arti |
|---|---|
| `side` | Kode rack — **hanya `R1`..`R8`** yang dipakai (lihat `RackTrackingService::RACKS`). Nilai lain (`RA`, `CONVE1`, `CONVE2`) itu staging/conveyor, bukan rak penyimpanan, jadi dikecualikan |
| `column_no` | Kolom rak, format `"C1"`..`"C9"` |
| `stack` | Layer rak, `1`..`5` |
| `status` | `FULL` atau `EMPTY` |
| `qr_id` | Kode box yang sedang ada di slot ini — **cocok langsung** dengan `warehouse_stock.bin_name` |
| `storage_bin` | ID unik baris/slot (dipakai untuk endpoint detail slot) |
| `upd_date` | Waktu update terakhir status slot ini |

Cakupan gudang: 8 rack × 9 kolom × 5 layer = **360 slot total**, terverifikasi cocok
persis dengan dokumentasi resmi gudang.

Dipakai oleh: Dashboard (kartu Slots Filled, chart utilisasi), Rack Monitoring
(seluruhnya — grid, 3D, pencarian), Laporan (tab Rack Utilization), Riwayat Transaksi
(resolusi kolom "Lokasi Box" — lihat catatan keterbatasan di bawah).

### Tabel lain di schema ini (ADA tapi kosong/tidak dipakai)

`t_status_rack`, `t_task_order`, `t_status_agv` — kosong di semua snapshot data yang
pernah dilihat sepanjang project ini. **SUDAH TERJAWAB (2026-09-20)**, bukan lagi
dugaan: dicek langsung ke source code aplikasi WinForms (`../Mainform/Mainform/*.cs`,
ada di komputer yang sama) — robot AGV **TIDAK PERNAH** dikontrol lewat tabel-tabel
ini. Mekanisme kontrol robot yang SEBENARNYA:
- **REST API** ke `http://192.168.50.134/AGV/api/reqTaskCtu.php` (lihat
  `FormMultiSend.cs`/`FormTrayRack.cs`, fungsi `sendBinCtu(depart, dest, qrId)`) —
  kirim JSON `{depart, dest}` untuk gerakkan tray antara conveyor
  (`CONVE205501013`=input, `CONVE104501013`=output) dan slot rak.
- **Modbus TCP** langsung ke `192.168.51.150:502` (library `EasyModbus`) — kontrol
  level rendah, kemungkinan besar untuk conveyor/PLC.
- Begitu command dikirim (termasuk saat GAGAL — kode aslinya update status juga di
  blok `catch`!), WinForms app LANGSUNG `UPDATE db_agv.m_matrix_storage_bin SET
  status='FULL'/'EMPTY', qr_id=...` — optimistic update, BUKAN callback konfirmasi
  dari robot. Celah reliabilitas ini sudah ada di sistem asli, independen dari WMS.

Jadi `t_task_order` dkk memang kosong karena **bukan itu jalurnya** — sudah tidak
perlu dikonfirmasi ke programmer lagi soal ini, pertanyaannya sudah terjawab dari
kodenya langsung. Detail lengkap & implikasinya untuk WMS ada di `PROJECT-NOTES.md`
poin 12 (2026-09-20).

Karena itu, kolom "Lokasi Box (kini)" di menu Riwayat Transaksi memakai pendekatan:
posisi box **SAAT INI** dari `m_matrix_storage_bin` (bukan posisi persis saat
transaksi terjadi dulu). Kalau nanti terkonfirmasi tabel di atas terisi, sumber data
ini sebaiknya diganti — lihat `RackTrackingService::transactionHistory()`.

---

## Semua query terpusat di satu tempat

Tidak ada controller yang query langsung ke model — semua logic query (join manual
antar dua koneksi, agregasi, dll) ada di satu file:

**[`app/Services/RackTrackingService.php`](app/Services/RackTrackingService.php)**

Kalau butuh data baru dari tabel yang sudah ada, atau mau mulai pakai salah satu
tabel yang belum aktif di atas, tambahkan method baru di sini — jangan taruh query
di controller.

## Variabel `.env` yang mengatur koneksi ini

Lihat [`.env.example`](.env.example) untuk daftar lengkap dengan komentar. Ringkas:

```
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=...
DB_DATABASE=db_swis
DB_USERNAME=...
DB_PASSWORD=...

DB_AGV_HOST=...
DB_AGV_PORT=...
DB_AGV_DATABASE=...
DB_AGV_SCHEMA=db_agv
DB_AGV_USERNAME=...
DB_AGV_PASSWORD=...
```

---

## Modul WMS (baru, 2026-09-18) — koneksi `pgsql` (database `db_swis`), TERTULIS

Latar belakang: user minta implementasi alur WMS penuh terinspirasi dari
`Hafith Ilyasa Nur Rochmad-4132501003-Put Away.pdf` &
`...-Good Receive.pdf` (contoh dokumen WMS Polibatam lain) + video referensi (tidak
bisa saya tonton, tidak ada akses video/browser). Keputusan final user (setelah 2
putaran diskusi, revisi dari draf pertama):
1. **WMS = TAMBAHAN, BUKAN pengganti.** 4 menu monitoring existing (Dashboard/Rack
   Monitoring/Riwayat Transaksi/Laporan) **TIDAK ada yang dihapus/diganti** — draf
   awal sempat bilang "gantikan sebagian", direvisi user setelah didiskusikan lebih
   dalam. WMS jadi menu/halaman baru berdampingan.
2. Put Away & Picking = **pencatatan manual saja**, TIDAK menggerakkan robot AGV
   (tidak menulis ke `t_task_order`/schema `db_agv` sama sekali).
3. **Part di contoh PDF cuma referensi, bukan data yang harus ditiru** — proses
   bisnisnya harus nyambung ke data ASLI yang sudah ada di sistem, bukan bikin
   master data fiktif paralel. Ini mengubah desain part/component (lihat di bawah).

14 tabel baru (bukan 15 — `wms_parts` sempat dibuat lalu dihapus lagi setelah poin 3
di atas), semua prefix `wms_`, migration di `database/migrations/2026_09_18_*`,
model di `app/Models/Wms/*`. Sudah dites end-to-end 2x (versi pertama dgn `wms_parts`
fiktif, lalu versi revisi dgn data asli) pakai tinker — data uji `PT Sumber
Makmur`/`Botol Aqua (matrix_partcode nyata)`/`B13 AIR MINERAL (component nyata)`,
semua relasi jalan, lalu dihapus lagi — tabel kosong, siap dipakai.

### Master data
| Tabel | Isi |
|---|---|
| `wms_suppliers` | code, name, address, phone, email, contact_person, is_active |
| `wms_customers` | sama seperti supplier, untuk pihak yang menerima Sales Order |
| `wms_component_masters` (baru, 2026-09-21) | component_code (unique), component_name, default_uom, qty_label — katalog komponen buat cetak label barcode (Component Code + Qty), dipakai mendaftarkan barang ke aplikasi mainform (via aplikasi Hiik-code eksternal). Diisi manual lewat menu "Master Data → Component Master" SAJA. BEDA tujuan dari `wms_parts` yang dulu dihapus (lihat catatan di bawah) — itu untuk normalisasi FK internal yang tidak perlu, ini untuk referensi eksternal (barcode + mainform), jadi bukan mengulang keputusan yang sama. |

**Revisi 2026-09-21 (poin 33 PROJECT-NOTES)**: kolom `source` (manual/asn) yang
sempat ada DIHAPUS, dan auto-create komponen baru dari form ASN juga DIHAPUS.
Sekarang arah sinkronisasinya SATU ARAH (master → ASN, bukan lagi ASN → master):
`wms_asn_lines.component` masih string biasa (bukan FK, `component`/
`component_name` tetap dipertahankan sebagai snapshot historis persis
seperti alasan awal), TAPI form ASN cuma nawarin dropdown isi dari
`wms_component_masters` (pola dropdown+validasi `exists:` yang sama juga
dipakai Outbound sejak revisi 2026-09-23, lihat bagian Outbound di bawah)
dan `AsnController::store()` validasi `exists:wms_component_masters,
component_code` — komponen yang belum terdaftar di Component Master TIDAK
BISA dipakai di ASN sampai didaftarkan dulu di sana. `component_name` yang
tersimpan di baris ASN di-lookup ulang di server dari master (bukan dari
input client) supaya selalu konsisten. GRN blind-receive (tanpa ASN) TIDAK
kena perubahan ini — masih baca `warehouse_stock` seperti sebelumnya.

### Inbound: ASN → GRN → Put Away
| Tabel | Isi | FK penting |
|---|---|---|
| `wms_asns` | asn_no, expected_date, status (draft/confirmed/closed/cancelled) | `supplier_id` |
| `wms_asn_lines` | **component, component_name** (string, bukan FK — lihat catatan), qty_expected, uom | `asn_id` |
| `wms_grns` | grn_no, received_date, transport_mode, vehicle_no, status | `asn_id` (nullable — boleh "blind receive" tanpa ASN), `supplier_id` |
| `wms_grn_lines` | **component, component_name**, qty_received, uom, **lot_no, mfg_date, expired_date**, status | `grn_id`. Accessor `->qty_put_away` (SUM dari putaway_lines terkait). ~~serial_no, pallet_id~~ DIHAPUS 2026-09-19 — lihat catatan di bawah |
| `wms_putaways` | putaway_no, putaway_date, status, created_by | `grn_id` |
| `wms_putaway_lines` | qty, **location_code** (string biasa, BUKAN FK — lihat catatan di bawah), confirmed_at, confirmed_by | `putaway_id`, `grn_line_id` |

### Outbound: Sales Order → Picking → Delivery Order
| Tabel | Isi | FK penting |
|---|---|---|
| `wms_sales_orders` | so_no, order_date, required_date, status | `customer_id` |
| `wms_so_lines` | **component, component_name**, qty_ordered, uom | `sales_order_id`. Accessor `->qty_picked` (SUM dari picking_lines terkait) |
| `wms_pickings` | picking_no, picking_date, status, created_by | `sales_order_id` |
| `wms_picking_lines` | **component, component_name**, qty_picked, lot_no, **location_code**, confirmed_at, confirmed_by | `picking_id`, `so_line_id` |
| `wms_delivery_orders` | do_no, delivery_date, vehicle_no, driver_name, status | `sales_order_id`, `picking_id` (nullable) |
| `wms_do_lines` | **component, component_name**, qty_delivered, uom, lot_no | `delivery_order_id` |

**Revisi 2026-09-23**: `matrix_partcode_id` FK **DIHAPUS** dari ketiga tabel di atas,
diganti `component`/`component_name` string — Outbound sekarang ikut pola yang
sama persis dengan Inbound. Alasannya BUKAN preferensi desain, tapi temuan
lapangan: user melaporkan dropdown Part Code di Sales Order kosong di komputer
produksi (SWIS) — ternyata `matrix_partcode` memang **tidak pernah terisi lewat
jalur manapun** di operasional nyata (dulu diasumsikan "tabel master yang sudah
ada isinya", padahal isi tabel itu di database dev cuma data uji manual milik
sesi development, bukan sesuatu yang otomatis terisi). Satu-satunya data yang
benar-benar mengalir ke sistem adalah barang yang di-scan & dikirim ke rack lewat
MainForm (masuk ke `warehouse_stock`). Jadi Part Code di SO/Picking/DO sekarang
sumbernya `RackTrackingService::stockPerItem()` — stok riil yang sama dipakai
Report A "Stock per Item" — bukan lagi katalog terpisah. Migration
`2026_09_23_135213_replace_matrix_partcode_with_component_on_outbound_lines`
juga membackfill `component`/`component_name` dari FK lama ke baris yang sudah
ada, supaya riwayat SO/Picking/DO lama tidak hilang. `app/Models/MatrixPartcode.php`
TETAP ADA (masih dipakai `BomList`), cuma sudah tidak dipakai modul WMS lagi.

**On Hand belum jadi tabel** — rencananya dihitung (bukan disimpan) dari
`SUM(putaway) - SUM(picking)` per part+lot+location, mengikuti pola yang sama seperti
`WarehouseStock::stockSummary()` di sistem lama (ledger, bukan saldo tersimpan). Belum
diimplementasi karena baru sampai tahap skema.

**`location_code`** (di `putaway_lines` & `picking_lines`) sengaja **string biasa,
bukan foreign key** — kode rak (`R1C11` dst) datanya ada di koneksi `pgsql_agv` yang
terpisah (database berbeda), Postgres/Laravel tidak bisa FK lintas koneksi. Validasi
kode itu valid/tidak harus dilakukan di application layer (service), pakai
`RackTrackingService` yang sudah ada — **belum diimplementasi**, masih perlu dibuatkan
method validasi + dropdown pemilihan lokasi saat form Put Away/Picking dibangun nanti.

### Sudah diputuskan (jangan tanya ulang ke user)
- ✅ WMS = menu tambahan, sidebar existing (4 menu) **tidak diubah/dihapus sama
  sekali**.
- ✅ Tidak ada master part/item baru — Inbound pakai `component` string (konvensi
  lama), Outbound pakai `matrix_partcode_id` FK ke tabel yang sudah ada.

### Sudah dikerjakan (2026-09-18, sesi UI)
- ✅ **Inbound UI selesai**: controller + route + Blade view lengkap untuk Supplier
  (CRUD tanpa delete), ASN (index/create dgn line item dinamis/show+confirm), GRN
  (index/create dgn prefill dari ASN/show), Put Away (index/create dgn prefill dari
  GRN + input lokasi rak pakai `<datalist>` dari `RackTrackingService::allLocationCodes()`
  /show), dan laporan On Hand (`SUM(putaway_lines.qty)` per component, join ke
  `wms_grn_lines`, breakdown per lokasi).
- ✅ Nomor dokumen auto-generate di controller `create()` (bukan migration/observer):
  pola `{PREFIX}-{ymd}-{counter harian}`, mis. `ASN-260918-001`. User masih bisa
  edit manual sebelum submit (field bukan readonly, cuma prefilled).
- ✅ Sidebar sudah ditambah section "WMS · Inbound" (5 link: Suppliers, ASN, Goods
  Receive, Put Away, On Hand) di `layouts/app.blade.php`, di bawah 4 menu lama yang
  tidak diubah.
- ✅ Sudah diuji end-to-end lewat HTTP asli (curl, bukan cuma tinker): Supplier →
  ASN → confirm ASN → GRN (prefill dari ASN) → Put Away (prefill dari GRN, isi
  `location_code=R1C11`) → cek GRN outstanding jadi 0 → cek On Hand tampil benar.
  Data uji sudah dihapus lagi setelahnya.
- ✅ CSS baru ditambahkan di `resources/css/app.css` (`@layer components`, dekat
  akhir file): `.form-*`, `.btn-primary`/`.btn-secondary`/`.btn-danger-ghost`,
  `.line-items-table` + `.add-line-btn`, `.status-tag` (+varian draft/pending/
  confirmed/completed/cancelled), `.detail-grid`/`.detail-field`, `.empty-state`,
  `.nav-section-label`. Sudah di-`npm run build` (bukan cuma dev server) supaya
  `public/build/manifest.json` konsisten dipakai.

### Sudah dikerjakan (2026-09-19, sesi UI Outbound)
- ✅ **Outbound UI selesai**: controller + route + Blade view lengkap untuk Customer
  (CRUD tanpa delete, mirror persis Supplier), Sales Order (index/create dgn line
  item dinamis pilih `matrix_partcode_id` dari dropdown/show+confirm), Picking
  (index/create dgn prefill dari SO confirmed + input lokasi rak `<datalist>`,
  mirip Put Away tapi 1 langkah — tidak ada tahap terpisah semacam GRN karena
  `picking_lines` sudah langsung punya `location_code`/show), Delivery Order
  (index/create dgn prefill dari Picking ATAU entry manual pilih SO + part code
  langsung/show).
- ✅ Alur lengkap: **SO (draft) → confirm → Picking (pull dari lokasi rak) →
  Delivery Order (kirim ke customer)**. Beda dari Inbound: tidak ada analog GRN di
  sisi Outbound — `Picking` menggabungkan "ambil barang" + "konfirmasi lokasi asal"
  dalam satu langkah (field `location_code` ada langsung di `picking_lines`,
  bukan tabel terpisah).
- ✅ Nomor dokumen auto-generate sama seperti Inbound: `SO-{ymd}-{counter}`,
  `PK-{ymd}-{counter}`, `DO-{ymd}-{counter}`.
- ✅ Sidebar ditambah section "WMS · Outbound" (4 link: Customers, Sales Order,
  Picking, Delivery Order) di bawah section "WMS · Inbound", 4 menu lama & Inbound
  tidak diubah.
- ✅ Diuji end-to-end lewat HTTP asli (curl): Customer → SO (line matrix_partcode_id=1,
  "10001"/"Botol Aqua") → confirm SO → Picking (prefill dari SO, isi
  `location_code=R2C33`) → SO outstanding jadi 0 → Delivery Order (prefill dari
  Picking) → cek relasi SO↔Picking↔DO tampil benar di semua halaman show. Data uji
  sudah dihapus lagi. Semua halaman lama (4 menu monitoring + Inbound) dicek tetap
  200 setelahnya.
- ✅ Tidak ada CSS baru yang perlu ditambah — semua class dari sesi Inbound
  (`.form-*`, `.btn-*`, `.status-tag`, `.line-items-table`, `.detail-grid`, dst)
  dipakai ulang persis, dikonfirmasi lewat grep sebelum nulis view (tidak ada class
  yang hilang).

### Belum dikerjakan
- ~~Belum ada method validasi `location_code`~~ **SUDAH DIKERJAKAN (2026-09-20)** —
  lihat `PROJECT-NOTES.md` poin 12. `location_code` sekarang divalidasi terhadap
  status LIVE `m_matrix_storage_bin`: Put Away cuma terima slot EMPTY
  (`RackTrackingService::emptyLocationCodes()`), Picking cuma terima slot FULL
  (`occupiedLocationCodes()`) — lebih ketat dari sekadar "360 slot valid" yang
  ditulis di paragraf lama ini.
- Belum ada halaman edit/cancel untuk ASN/GRN/Put Away/SO/Picking/DO (cuma
  create+show, sesuai alur "manual staff confirmation" — dianggap dokumen final
  begitu disimpan, bukan draft yang bisa diedit ulang, kecuali ASN & SO yang punya
  action "Confirm").
- **Delivery Order tidak melacak "sudah dikirim berapa" per Picking secara ketat** —
  `wms_do_lines` cuma FK ke `matrix_partcode_id`, TIDAK ke `picking_line_id` spesifik
  (skema sudah final dari sesi sebelumnya, sengaja simpel meniru pola On Hand yang
  agregat per component, bukan per baris). Jadi kalau 1 Picking dipakai bikin >1 DO
  manual, tidak ada pengecekan otomatis supaya total qty delivered tidak melebihi
  qty picked — murni tanggung jawab staf yang input manual. `openPickings` di form
  create DO cuma filter "belum punya DO sama sekali" (`doesntHave('deliveryOrders')`),
  bukan "masih ada sisa qty".

### Sudah dikerjakan (2026-09-19, sesi perbaikan konsep & UX)

Dipicu user menanyakan langsung konsep `lot_no`/`serial_no`/`pallet_id` di GRN, dan
apakah On Hand sama fungsinya dengan Laporan — memaksa evaluasi ulang field mana yang
benar-benar sesuai kondisi lapangan gudang ini vs. yang cuma kebawa dari PDF referensi.

- ✅ **`serial_no` & `pallet_id` DIHAPUS dari `wms_grn_lines`** (migration
  `2026_09_19_084243_drop_serial_no_and_pallet_id_from_wms_grn_lines_table.php`).
  Alasan: unit fisik gudang ini adalah **"box"** (kode QR, kolom `bin_name` di
  `warehouse_stock`), BUKAN barang ter-serialize satuan atau pallet — dua konsep itu
  tidak punya padanan di manapun dalam sistem nyata (dicek: `grep -rn pallet` di
  seluruh kode lama nihil). Field ini kebawa dari contoh PDF WMS Polibatam lain saat
  desain awal skema, bukan dari kebutuhan nyata gudang ini — pengulangan pola
  kesalahan yang sama dengan `wms_parts` yang sudah dikoreksi sebelumnya (lihat
  section di atas), cuma lebih halus (bukan tabel baru, cuma kolom).
- ✅ **`lot_no`, `mfg_date`, `expired_date` DIPERTAHANKAN** — beda dari serial/pallet,
  field ini punya justifikasi nyata: barang yang lewat GRN contoh (FaceWash, Shampoo)
  adalah barang konsumen yang punya nomor batch & tanggal kadaluarsa asli di
  kemasannya. `warehouse_stock` lama memang tidak melacak ini (sistem lama cuma
  fokus posisi box di rak), tapi WMS memang sengaja dibangun untuk menambah
  kapabilitas traceability yang belum ada itu — bukan sekadar meniru sistem lama.
- ✅ **Qty ditampilkan tanpa paksa 2 desimal** — helper baru `fmt_qty()` di
  `app/Support/helpers.php` (global function, didaftarkan lewat
  `composer.json` → `autoload.files`), dipakai di semua Blade WMS menggantikan
  `number_format($x, 2)`. Menampilkan `10` bukan `10.00`, tapi tetap `10.5` kalau
  memang pecahan (`rtrim` trailing zero, bukan hardcode 0 desimal, supaya angka
  pecahan asli tidak hilang presisinya).
- ✅ **Warna notifikasi dipisah per jenis** (sebelumnya semua pesan — sukses,
  info, error — sama-sama pakai `.note` warna amber/kuning, membingungkan karena
  pesan sukses “ASN berhasil dibuat” terlihat sama dengan notifikasi netral
  “Prefilled from ASN”): `.note-success` (hijau, buat flash `session('status')`),
  `.note-info` (indigo, buat banner prefill/keterangan netral seperti di GRN/DO
  create & Put Away Summary), `.note-danger` (merah, buat validation error —
  sebelumnya inline `style=` berulang di 12 file, sekarang class terpusat di
  `resources/css/app.css`). `.note` asli (amber) tetap ada, dipakai kalau memang
  perlu warning/caution di masa depan.
- ✅ **Menu "On Hand" diganti nama jadi "Put Away Summary"** (judul halaman +
  sidebar; route name `wms.on-hand` & URL TIDAK diubah, cuma label). Alasan: nama
  "On Hand" menyiratkan ini angka stok terkini yang otentik, padahal cuma
  `SUM(wms_putaway_lines.qty)` — audit sepihak dari alur WMS saja, TIDAK
  memperhitungkan pemakaian/konsumsi komponen (WMS belum punya alur "material
  issue"), dan sama sekali tidak baca `warehouse_stock` (ledger asli yang
  dipakai Laporan → Stok per Item, mencakup SEMUA sumber transaksi termasuk yang
  di luar WMS). Kedua angka ini **BISA BEDA** untuk component yang sama — bukan
  bug, tapi konsekuensi dari WMS jadi ledger terpisah yang murni tambahan
  (`additive`), bukan pengganti. Caption di halaman sekarang eksplisit menjelaskan
  ini dan mengarahkan ke Laporan untuk angka stok yang sebenarnya.

### Belum dikerjakan (update per 2026-09-19)
- Poin-poin "Belum dikerjakan" di section Inbound & Outbound sebelumnya (validasi
  `location_code`, halaman edit/cancel, tracking qty delivered vs picked per baris)
  **masih berlaku, belum dikerjakan**.
- Kalau nanti butuh field free-text tambahan di GRN untuk kasus yang benar-benar
  nyata (bukan asumsi dari PDF lain), tambahkan lewat migration baru + evaluasi
  yang sama seperti di atas: cek dulu apakah ada padanannya di sistem nyata gudang
  ini sebelum menambah kolom.

### ⚠️ KOREKSI PENTING (2026-09-20): "On Hand"/"Put Away Summary" BUKAN LAGI MENU

Semua paragraf di atas yang menyebut "On Hand" atau "Put Away Summary" sebagai
**menu sidebar sendiri** (dengan `OnHandController`, route `inbound.on-hand`,
view `resources/views/inbound/on-hand/`) itu riwayat lama — **SUDAH DIPENSIUNKAN**.
Atas usulan user (dan disetujui, karena memang IA yang lebih benar): laporan ini
sifatnya read-only/agregat, jadi **DIPINDAH jadi tab "D · WMS Put Away Summary"
di menu Reports** (`laporan/index.blade.php`, query lewat
`RackTrackingService::putAwaySummary()`/`putAwaySummaryByLocation()`), BUKAN
menu sidebar Inbound lagi. Sidebar Inbound sekarang kembali 4 item (Suppliers,
ASN, Goods Receive, Put Away) — simetris dengan Outbound.

`OnHandController` sudah dihapus. URL lama `/on-hand` di-redirect (bukan 404) ke
`/laporan?tab=putaway`. Detail lengkap ada di `PROJECT-NOTES.md` poin 21.

**Implikasi arsitektur**: ini pertama kalinya `LaporanController` (bagian sistem
monitoring LAMA) ikut query tabel `wms_*` — tapi cuma BACA (read-only), bukan
menulis, jadi TIDAK melanggar prinsip "WMS additive, tidak menulis ke sistem
lama tanpa izin". `warehouse_stock`/`m_matrix_storage_bin` tetap sama sekali
tidak disentuh WMS.
