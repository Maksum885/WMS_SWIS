<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inbound\AsnController;
use App\Http\Controllers\Inbound\GrnController;
use App\Http\Controllers\Inbound\PutawayController;
use App\Http\Controllers\Inbound\SupplierController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterData\ComponentMasterController;
use App\Http\Controllers\Outbound\CustomerController;
use App\Http\Controllers\Outbound\DeliveryOrderController;
use App\Http\Controllers\Outbound\PickingController;
use App\Http\Controllers\Outbound\SalesOrderController;
use App\Http\Controllers\RackMonitoringController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

Route::prefix('rack')->name('rack.')->group(function () {
    Route::get('/', [RackMonitoringController::class, 'index'])->name('index');
    Route::get('/all-slots', [RackMonitoringController::class, 'allSlots'])->name('all-slots');
    Route::get('/{rack}/slots', [RackMonitoringController::class, 'slots'])->name('slots');
    Route::get('/slot/{storageBin}', [RackMonitoringController::class, 'slotDetail'])->name('slot-detail');
    Route::get('/search', [RackMonitoringController::class, 'search'])->name('search');
});

Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi');

Route::prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [LaporanController::class, 'index'])->name('index');
    Route::get('/export/excel', [LaporanController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [LaporanController::class, 'exportPdf'])->name('export.pdf');
});

// ===== Master Data: katalog komponen, sumber cetak label barcode buat
// registrasi ke aplikasi mainform. Terpisah dari Inbound/Outbound karena
// sifatnya data referensi, bukan langkah transaksi. =====
Route::name('master.')->group(function () {
    Route::get('components', [ComponentMasterController::class, 'index'])->name('components.index');
    Route::get('components/create', [ComponentMasterController::class, 'create'])->name('components.create');
    Route::post('components', [ComponentMasterController::class, 'store'])->name('components.store');
    Route::get('components/{component}/edit', [ComponentMasterController::class, 'edit'])->name('components.edit');
    Route::put('components/{component}', [ComponentMasterController::class, 'update'])->name('components.update');
    Route::post('components/print', [ComponentMasterController::class, 'print'])->name('components.print');
});

// ===== Inbound: Supplier -> ASN -> Goods Receive -> Put Away -> Put Away Summary =====
// Tambahan, tidak mengganti menu monitoring di atas. Tanpa prefix URL khusus
// ("/wms" atau "/inbound") — tiap resource langsung di root, mis. /suppliers,
// /asns, dst, biar terasa menyatu dengan sistem, bukan modul terpisah.
Route::name('inbound.')->group(function () {
    Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);

    Route::get('asns', [AsnController::class, 'index'])->name('asns.index');
    Route::get('asns/create', [AsnController::class, 'create'])->name('asns.create');
    Route::post('asns', [AsnController::class, 'store'])->name('asns.store');
    Route::get('asns/{asn}', [AsnController::class, 'show'])->name('asns.show');
    Route::post('asns/{asn}/confirm', [AsnController::class, 'confirm'])->name('asns.confirm');
    Route::get('asns/{asn}/pdf', [AsnController::class, 'exportPdf'])->name('asns.pdf');

    Route::get('grns', [GrnController::class, 'index'])->name('grns.index');
    Route::get('grns/create', [GrnController::class, 'create'])->name('grns.create');
    Route::post('grns', [GrnController::class, 'store'])->name('grns.store');
    Route::get('grns/{grn}', [GrnController::class, 'show'])->name('grns.show');
    Route::get('grns/{grn}/pdf', [GrnController::class, 'exportPdf'])->name('grns.pdf');

    Route::get('putaways', [PutawayController::class, 'index'])->name('putaways.index');
    Route::get('putaways/create', [PutawayController::class, 'create'])->name('putaways.create');
    Route::post('putaways', [PutawayController::class, 'store'])->name('putaways.store');
    Route::get('putaways/{putaway}', [PutawayController::class, 'show'])->name('putaways.show');
    Route::get('putaways/{putaway}/pdf', [PutawayController::class, 'exportPdf'])->name('putaways.pdf');

    // Put Away Summary dipindah jadi tab "D" di Reports (bukan menu sendiri) —
    // ini redirect supaya link/bookmark lama tidak 404 begitu saja.
    Route::redirect('on-hand', '/laporan?tab=putaway')->name('on-hand');
});

// ===== Outbound: Customer -> Sales Order -> Picking -> Delivery Order =====
Route::name('outbound.')->group(function () {
    Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);

    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
    Route::get('sales-orders/{salesOrder}/pdf', [SalesOrderController::class, 'exportPdf'])->name('sales-orders.pdf');

    Route::get('pickings', [PickingController::class, 'index'])->name('pickings.index');
    Route::get('pickings/create', [PickingController::class, 'create'])->name('pickings.create');
    Route::post('pickings', [PickingController::class, 'store'])->name('pickings.store');
    Route::get('pickings/{picking}', [PickingController::class, 'show'])->name('pickings.show');
    Route::get('pickings/{picking}/pdf', [PickingController::class, 'exportPdf'])->name('pickings.pdf');

    Route::get('delivery-orders', [DeliveryOrderController::class, 'index'])->name('delivery-orders.index');
    Route::get('delivery-orders/create', [DeliveryOrderController::class, 'create'])->name('delivery-orders.create');
    Route::post('delivery-orders', [DeliveryOrderController::class, 'store'])->name('delivery-orders.store');
    Route::get('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'show'])->name('delivery-orders.show');
    Route::get('delivery-orders/{deliveryOrder}/pdf', [DeliveryOrderController::class, 'exportPdf'])->name('delivery-orders.pdf');
});
