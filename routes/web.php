<?php

use App\Http\Controllers\ProductionAdminController;
use App\Http\Controllers\SpkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductionAdminController::class, 'dashboard']);

// ── Input Produksi ──
Route::get('/input-proses', [ProductionAdminController::class, 'inputProses'])->name('input.proses');
Route::get('/input-hasil',  [ProductionAdminController::class, 'inputHasil'])->name('input.hasil');
Route::post('/production-entries', [ProductionAdminController::class, 'storeProductionEntry'])->name('production-entries.store');
Route::get('/production-entries/{entry}/edit', [ProductionAdminController::class, 'editProductionEntry'])->name('production-entries.edit');
Route::put('/production-entries/{entry}', [ProductionAdminController::class, 'updateProductionEntry'])->name('production-entries.update');
Route::delete('/production-entries/{entry}', [ProductionAdminController::class, 'destroyProductionEntry'])->name('production-entries.destroy');
Route::get('/rework', [ProductionAdminController::class, 'reworkPage'])->name('rework.index');
Route::get('/rework-results', [ProductionAdminController::class, 'reworkResultsPage'])->name('rework-results.index');
Route::post('/rework-results', [ProductionAdminController::class, 'storeReworkResult'])->name('rework-results.store');
Route::get('/rework-results/{result}/edit', [ProductionAdminController::class, 'editReworkResult'])->name('rework-results.edit');
Route::put('/rework-results/{result}', [ProductionAdminController::class, 'updateReworkResult'])->name('rework-results.update');
Route::delete('/rework-results/{result}', [ProductionAdminController::class, 'destroyReworkResult'])->name('rework-results.destroy');
Route::get('/rework-results-export', [ProductionAdminController::class, 'exportReworkResults'])->name('rework-results.export');
Route::get('/binding-reject-stock', [ProductionAdminController::class, 'bindingRejectStockPage'])->name('binding-reject-stock.index');
Route::post('/binding-reject-stock', [ProductionAdminController::class, 'storeBindingRejectStock'])->name('binding-reject-stock.store');
Route::get('/binding-reject-stock/{stock}/edit', [ProductionAdminController::class, 'editBindingRejectStock'])->name('binding-reject-stock.edit');
Route::put('/binding-reject-stock/{stock}', [ProductionAdminController::class, 'updateBindingRejectStock'])->name('binding-reject-stock.update');
Route::delete('/binding-reject-stock/{stock}', [ProductionAdminController::class, 'destroyBindingRejectStock'])->name('binding-reject-stock.destroy');
Route::get('/binding-reject-stock-export', [ProductionAdminController::class, 'exportBindingRejectStock'])->name('binding-reject-stock.export');

// ── Dashboard ──
Route::get('/dashboard', [ProductionAdminController::class, 'dashboard'])->name('production.dashboard');

// ── Master Data ──
Route::get('/masters',                 [ProductionAdminController::class, 'masters'])->name('masters');
Route::get('/masters/{section}',       [ProductionAdminController::class, 'masters'])
    ->whereIn('section', ['buyers', 'parts', 'sizes', 'mappings', 'processes', 'operators'])
    ->name('masters.section');
Route::get('/masters/{type}/{id}/edit', [ProductionAdminController::class, 'editMaster'])
    ->whereIn('type', ['buyers', 'operators', 'parts', 'sizes', 'processes'])
    ->name('masters.edit');
Route::put('/masters/{type}/{id}', [ProductionAdminController::class, 'updateMaster'])
    ->whereIn('type', ['buyers', 'operators', 'parts', 'sizes', 'processes'])
    ->name('masters.update');

Route::get('/masters/buyers/create',   [ProductionAdminController::class, 'createBuyer'])->name('buyers.create');
Route::post('/masters/buyers',         [ProductionAdminController::class, 'storeBuyer'])->name('buyers.store');
Route::delete('/masters/buyers/{buyer}', [ProductionAdminController::class, 'destroyBuyer'])->name('buyers.destroy');

Route::get('/masters/operators/create', [ProductionAdminController::class, 'createOperator'])->name('operators.create');
Route::get('/masters/operators/export', [ProductionAdminController::class, 'exportOperators'])->name('operators.export');
Route::get('/masters/operators/import', [ProductionAdminController::class, 'importOperatorsForm'])->name('operators.import.form');
Route::post('/masters/operators/import', [ProductionAdminController::class, 'importOperators'])->name('operators.import');
Route::post('/masters/operators', [ProductionAdminController::class, 'storeOperator'])->name('operators.store');
Route::delete('/masters/operators/{operator}', [ProductionAdminController::class, 'destroyOperator'])->name('operators.destroy');

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
Route::get('/reports/production-hourly', [ProductionAdminController::class, 'productionHourlyExport'])
    ->name('reports.production-hourly');

// ── API ──
Route::get('/api/masters',              [ProductionAdminController::class, 'apiMasters']);
Route::post('/api/production-entries',  [ProductionAdminController::class, 'apiProductionEntries']);
Route::get('/api/dashboard-summary',    [ProductionAdminController::class, 'dashboardSummary'])
    ->name('production.dashboard.summary');
Route::get('/api/reports/fg',           [ProductionAdminController::class, 'apiFgReport']);
