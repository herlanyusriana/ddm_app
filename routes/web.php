<?php

use App\Http\Controllers\ProductionAdminController;
use App\Http\Controllers\SpkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductionAdminController::class, 'inputProses']);

// ── Input Produksi ──
Route::get('/input-proses', [ProductionAdminController::class, 'inputProses'])->name('input.proses');
Route::get('/input-hasil',  [ProductionAdminController::class, 'inputHasil'])->name('input.hasil');
Route::post('/production-entries', [ProductionAdminController::class, 'storeProductionEntry'])->name('production-entries.store');

// ── Dashboard ──
Route::get('/dashboard', [ProductionAdminController::class, 'dashboard'])->name('production.dashboard');

// ── Master Data ──
Route::get('/masters',                 [ProductionAdminController::class, 'masters'])->name('masters');
Route::get('/masters/{section}',       [ProductionAdminController::class, 'masters'])
    ->whereIn('section', ['buyers', 'parts', 'sizes', 'mappings', 'processes'])
    ->name('masters.section');

Route::get('/masters/buyers/create',   [ProductionAdminController::class, 'createBuyer'])->name('buyers.create');
Route::post('/masters/buyers',         [ProductionAdminController::class, 'storeBuyer'])->name('buyers.store');
Route::delete('/masters/buyers/{buyer}', [ProductionAdminController::class, 'destroyBuyer'])->name('buyers.destroy');

Route::get('/masters/parts/create',    [ProductionAdminController::class, 'createPart'])->name('parts.create');
Route::get('/masters/parts/export',    [ProductionAdminController::class, 'exportParts'])->name('parts.export');
Route::get('/masters/parts/import',    [ProductionAdminController::class, 'importPartsForm'])->name('parts.import.form');
Route::post('/masters/parts/import',   [ProductionAdminController::class, 'importParts'])->name('parts.import');
Route::post('/masters/parts',          [ProductionAdminController::class, 'storePart'])->name('parts.store');
Route::delete('/masters/parts/{part}', [ProductionAdminController::class, 'destroyPart'])->name('parts.destroy');

Route::get('/masters/sizes/create',    [ProductionAdminController::class, 'createSize'])->name('sizes.create');
Route::get('/masters/sizes/export',    [ProductionAdminController::class, 'exportSizes'])->name('sizes.export');
Route::get('/masters/sizes/import',    [ProductionAdminController::class, 'importSizesForm'])->name('sizes.import.form');
Route::post('/masters/sizes/import',   [ProductionAdminController::class, 'importSizes'])->name('sizes.import');
Route::post('/masters/sizes',          [ProductionAdminController::class, 'storeSize'])->name('sizes.store');
Route::delete('/masters/sizes/{size}', [ProductionAdminController::class, 'destroySize'])->name('sizes.destroy');

Route::post('/masters/mappings',       [ProductionAdminController::class, 'storeMapping'])->name('mappings.store');

// ── SPK (PPIC) ──
Route::get('/spk',                           [SpkController::class, 'index'])->name('spk.index');
Route::get('/spk/create',                    [SpkController::class, 'create'])->name('spk.create');
Route::post('/spk',                          [SpkController::class, 'store'])->name('spk.store');
Route::get('/spk/{spk}',                     [SpkController::class, 'show'])->name('spk.show');
Route::get('/spk/{spk}/print',               [SpkController::class, 'print'])->name('spk.print');
Route::get('/spk/{spk}/kanban-card',         [SpkController::class, 'kanbanCard'])->name('spk.kanban');
Route::delete('/spk/{spk}',                  [SpkController::class, 'destroy'])->name('spk.destroy');

// ── Warehouse ──
Route::get('/warehouse',                     [SpkController::class, 'warehouseIndex'])->name('warehouse.index');
Route::post('/warehouse/spk/{spk}/prepare',  [SpkController::class, 'warehousePrepare'])->name('warehouse.prepare');

// ── Reports ──
Route::get('/reports/fg',       [ProductionAdminController::class, 'fgReportPage'])->name('reports.fg');
Route::get('/reports/fg/print', [ProductionAdminController::class, 'fgReportPrint'])->name('reports.fg.print');

// ── API ──
Route::get('/api/masters',              [ProductionAdminController::class, 'apiMasters']);
Route::post('/api/production-entries',  [ProductionAdminController::class, 'apiProductionEntries']);
Route::get('/api/reports/fg',           [ProductionAdminController::class, 'apiFgReport']);
