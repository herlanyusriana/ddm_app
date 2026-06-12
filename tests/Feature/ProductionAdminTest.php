<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Part;
use App\Models\Process;
use App\Models\ProductionEntry;
use App\Models\SizeVariant;
use App\Models\Spk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_input_page_shows_entry_workspace_without_master_forms(): void
    {
        Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'sort_order' => 50]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Input Produksi');
        $response->assertSee('Master Data');
        $response->assertSee('Buyer Master');
        $response->assertSee('Part Master');
        $response->assertDontSee('Tambah Buyer');
        $response->assertDontSee('Tambah Part');
        $response->assertDontSee('Report FG dari Packing Good');
        $response->assertDontSee('Flow Information System');
    }

    public function test_app_has_pwa_manifest_and_service_worker(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('/service-worker.js', false);

        $manifest = file_get_contents(public_path('manifest.webmanifest'));
        $worker = file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString('DDM Production Admin', $manifest);
        $this->assertStringContainsString('/pwa-icon.svg', $manifest);
        $this->assertStringContainsString('ddm-production-v1', $worker);
        $this->assertStringContainsString('/offline.html', $worker);
    }

    public function test_master_data_sidebar_has_submenus(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Master Data',
            'Buyer Master',
            'Part Master',
            'Size Master',
            'Process Master',
        ]);
        $response->assertSee('href="/masters/buyers"', false);
        $response->assertSee('href="/masters/parts"', false);
        $response->assertSee('href="/masters/sizes"', false);
        $response->assertSee('href="/masters/processes"', false);
        $response->assertDontSee('Buyer Part Mapping');
    }

    public function test_master_data_has_its_own_page(): void
    {
        $response = $this->get('/masters');

        $response->assertOk();
        $response->assertSee('Master Data');
        $response->assertSee('Buyer Master');
        $response->assertSee('Part Master');
        $response->assertDontSee('Input Good');
    }

    public function test_master_data_sub_pages_are_separated(): void
    {
        Process::factory()->create([
            'name' => 'Warehouse RM',
            'is_input_process' => false,
            'sort_order' => 10,
        ]);

        $buyerPage = $this->get('/masters/buyers');
        $partPage = $this->get('/masters/parts');
        $processPage = $this->get('/masters/processes');

        $buyerPage->assertOk();
        $buyerPage->assertSee('Buyer Master');
        $buyerPage->assertSee('Tambah Buyer');
        $buyerPage->assertDontSee('Tambah Part');

        $partPage->assertOk();
        $partPage->assertSee('Part Master');
        $partPage->assertSee('Tambah Part');
        $partPage->assertDontSee('Tambah Buyer');

        $processPage->assertOk();
        $processPage->assertSee('Process Master');
        $processPage->assertSee('Warehouse RM');
        $processPage->assertDontSee('Tambah Buyer');
    }

    public function test_master_pages_do_not_repeat_sidebar_links_as_top_tabs(): void
    {
        $response = $this->get('/masters/parts');
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, substr_count($html, 'href="/masters/buyers"'));
        $this->assertSame(1, substr_count($html, 'href="/masters/parts"'));
        $this->assertSame(1, substr_count($html, 'href="/masters/sizes"'));
        $this->assertSame(1, substr_count($html, 'href="/masters/processes"'));
    }

    public function test_part_master_uses_fg_excel_columns_without_buyer_mapping(): void
    {
        $response = $this->post('/masters/parts', [
            'classification' => 'FG',
            'code' => '03.01.MAT-08T',
            'name' => '8inch Spring mattress Twin-ORSM01-08T',
            'spec' => '75*39*8inch',
            'width_cm' => 28,
            'depth_cm' => 28,
            'height_cm' => 106,
            'cbm_per_unit' => '0.08',
            'net_weight_pc' => '12.26',
            'gross_weight_pc' => '13.76',
            'package_box' => 1,
            'item_no' => 'MAT-HY-BN-08T',
            'goods_description' => '8 inch Hybrid Spring Mattress Twin',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('parts', [
            'classification' => 'FG',
            'code' => '03.01.MAT-08T',
            'name' => '8inch Spring mattress Twin-ORSM01-08T',
            'spec' => '75*39*8inch',
            'width_cm' => 28,
            'depth_cm' => 28,
            'height_cm' => 106,
            'cbm_per_unit' => '0.08',
            'net_weight_pc' => '12.26',
            'gross_weight_pc' => '13.76',
            'package_box' => 1,
            'item_no' => 'MAT-HY-BN-08T',
            'goods_description' => '8 inch Hybrid Spring Mattress Twin',
        ]);

        $page = $this->get('/masters/parts');
        $page->assertOk();
        $page->assertSee('Spec');
        $page->assertSee('Item No.');
        $page->assertSee('Goods Description');
        $page->assertSee('03.01.MAT-08T');
        $page->assertSee('MAT-HY-BN-08T');
        $page->assertDontSee('Buyer Part Mapping');
    }

    public function test_part_master_is_split_into_fg_wip_and_rm(): void
    {
        Part::factory()->create(['classification' => 'FG', 'code' => 'FG-001', 'name' => 'Finish Good Part']);
        Part::factory()->create(['classification' => 'WIP', 'code' => 'WIP-001', 'name' => 'WIP Part']);
        Part::factory()->create(['classification' => 'RM', 'code' => 'RM-001', 'name' => 'Raw Material Part']);

        $page = $this->get('/masters/parts');

        $page->assertOk();
        $page->assertSee('Finish Good');
        $page->assertSee('WIP');
        $page->assertSee('RM');
        $page->assertSee('FG-001');
        $page->assertSee('WIP-001');
        $page->assertSee('RM-001');

        $createPage = $this->get('/masters/parts/create');
        $createPage->assertOk();
        $createPage->assertSee('name="classification"', false);

        $response = $this->post('/masters/parts', [
            'classification' => 'RM',
            'code' => '01.01',
            'name' => 'Steel Wire',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('parts', [
            'classification' => 'RM',
            'code' => '01.01',
            'name' => 'Steel Wire',
        ]);
    }

    public function test_part_master_list_create_and_delete_are_separated(): void
    {
        $part = Part::factory()->create([
            'classification' => 'FG',
            'code' => '03.01.MAT-08T',
            'name' => '8inch Spring mattress Twin',
        ]);

        $listPage = $this->get('/masters/parts');
        $listPage->assertOk();
        $listPage->assertSee('Tambah Part Baru');
        $listPage->assertSee('03.01.MAT-08T');
        $listPage->assertSee('Hapus');
        $listPage->assertDontSee('name="classification"', false);
        $listPage->assertDontSee('placeholder="03.01.MAT-08T"', false);

        $createPage = $this->get('/masters/parts/create');
        $createPage->assertOk();
        $createPage->assertSee('Tambah Part');
        $createPage->assertSee('name="classification"', false);
        $createPage->assertSee('placeholder="03.01.MAT-08T"', false);
        $createPage->assertDontSee('Hapus');

        $deleteResponse = $this->delete("/masters/parts/{$part->id}");
        $deleteResponse->assertRedirect('/masters/parts');
        $this->assertDatabaseMissing('parts', ['id' => $part->id]);
    }

    public function test_buyer_master_list_create_and_delete_are_separated(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ', 'name' => 'Amazon']);

        $listPage = $this->get('/masters/buyers');
        $listPage->assertOk();
        $listPage->assertSee('Tambah Buyer Baru');
        $listPage->assertSee('AMZ');
        $listPage->assertSee('Hapus');
        $listPage->assertDontSee('name="code"', false);
        $listPage->assertDontSee('placeholder="AMZ"', false);

        $createPage = $this->get('/masters/buyers/create');
        $createPage->assertOk();
        $createPage->assertSee('Tambah Buyer');
        $createPage->assertSee('name="code"', false);
        $createPage->assertSee('placeholder="AMZ"', false);
        $createPage->assertDontSee('Hapus');

        $deleteResponse = $this->delete("/masters/buyers/{$buyer->id}");
        $deleteResponse->assertRedirect('/masters/buyers');
        $this->assertDatabaseMissing('buyers', ['id' => $buyer->id]);
    }

    public function test_size_master_list_create_and_delete_are_separated(): void
    {
        $size = SizeVariant::factory()->create(['code' => '12Q', 'name' => 'Queen']);

        $listPage = $this->get('/masters/sizes');
        $listPage->assertOk();
        $listPage->assertSee('Tambah Size Baru');
        $listPage->assertSee('12Q');
        $listPage->assertSee('Hapus');
        $listPage->assertDontSee('name="code"', false);
        $listPage->assertDontSee('placeholder="12Q"', false);

        $createPage = $this->get('/masters/sizes/create');
        $createPage->assertOk();
        $createPage->assertSee('Tambah Size');
        $createPage->assertSee('name="code"', false);
        $createPage->assertSee('placeholder="12Q"', false);
        $createPage->assertDontSee('Hapus');

        $deleteResponse = $this->delete("/masters/sizes/{$size->id}");
        $deleteResponse->assertRedirect('/masters/sizes');
        $this->assertDatabaseMissing('size_variants', ['id' => $size->id]);
    }

    public function test_part_master_can_export_and_import_excel_csv(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ', 'name' => 'Amazon']);
        Part::factory()->create([
            'buyer_id' => $buyer->id,
            'classification' => 'FG',
            'code' => '03.01.MAT-08T',
            'name' => '8inch Spring mattress Twin',
            'spec' => '75*39*8inch',
        ]);

        $export = $this->get('/masters/parts/export');

        $export->assertOk();
        $export->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $export->assertSee('buyer_code,classification,code,name,spec', false);
        $export->assertSee('03.01.MAT-08T', false);

        $csv = implode("\n", [
            'buyer_code,classification,code,name,spec,width_cm,depth_cm,height_cm,cbm_per_unit,net_weight_pc,gross_weight_pc,package_box,item_no,goods_description',
            'AMZ,FG,03.01.MAT-08T,Updated Mattress,75*39*8inch,28,28,106,0.08,12.26,13.76,1,MAT-HY-BN-08T,8 inch Hybrid Spring Mattress Twin',
            ',RM,01.01,Steel Wire,,,,,,,,,,',
        ]);

        $import = $this->post('/masters/parts/import', [
            'file' => UploadedFile::fake()->createWithContent('parts.csv', $csv),
        ]);

        $import->assertSessionHasNoErrors();
        $import->assertRedirect('/masters/parts');
        $this->assertDatabaseHas('parts', [
            'code' => '03.01.MAT-08T',
            'name' => 'Updated Mattress',
            'buyer_id' => $buyer->id,
            'item_no' => 'MAT-HY-BN-08T',
        ]);
        $this->assertDatabaseHas('parts', [
            'code' => '01.01',
            'classification' => 'RM',
            'name' => 'Steel Wire',
        ]);
        $this->assertDatabaseCount('parts', 2);
    }

    public function test_size_master_can_export_and_import_excel_csv(): void
    {
        SizeVariant::factory()->create(['code' => '12Q', 'name' => 'Queen']);

        $export = $this->get('/masters/sizes/export');

        $export->assertOk();
        $export->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $export->assertSee('code,name', false);
        $export->assertSee('12Q,Queen', false);

        $csv = implode("\n", [
            'code,name',
            '12Q,Queen Updated',
            '8T,Twin',
        ]);

        $import = $this->post('/masters/sizes/import', [
            'file' => UploadedFile::fake()->createWithContent('sizes.csv', $csv),
        ]);

        $import->assertSessionHasNoErrors();
        $import->assertRedirect('/masters/sizes');
        $this->assertDatabaseHas('size_variants', ['code' => '12Q', 'name' => 'Queen Updated']);
        $this->assertDatabaseHas('size_variants', ['code' => '8T', 'name' => 'Twin']);
        $this->assertDatabaseCount('size_variants', 2);
    }

    public function test_dashboard_has_its_own_page(): void
    {
        Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'sort_order' => 50]);

        $response = $this->get('/dashboard?production_date=2026-06-08&shift=2');

        $response->assertOk();
        $response->assertSee('Dashboard Produksi');
        $response->assertSee('Packing');
        $response->assertDontSee('Tambah Buyer');
    }

    public function test_warehouse_process_cannot_be_used_for_good_ng_input(): void
    {
        $buyer = Buyer::factory()->create();
        $part = Part::factory()->create();
        $size = SizeVariant::factory()->create();
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id]);
        $warehouse = Process::factory()->create([
            'name' => 'Warehouse RM',
            'is_input_process' => false,
            'sort_order' => 5,
        ]);

        $response = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $warehouse->id,
            'good_qty' => 10,
            'ng_qty' => 0,
        ]);

        $response->assertSessionHasErrors('process_id');
        $this->assertDatabaseCount('production_entries', 0);
    }

    public function test_part_is_required_only_for_packing_entries(): void
    {
        $buyer = Buyer::factory()->create();
        $part = Part::factory()->create();
        $size = SizeVariant::factory()->create();
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'target_qty' => 5]);
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);
        $packing = Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);

        $sewingResponse = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'size_variant_id' => $size->id,
            'process_id' => $sewing->id,
            'good_qty' => 10,
            'ng_qty' => 1,
        ]);

        $sewingResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $sewing->id,
            'part_id' => null,
            'good_qty' => 10,
            'ng_qty' => 1,
        ]);

        $packingResponse = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 5,
            'ng_qty' => 0,
        ]);

        $packingResponse->assertSessionHasErrors('part_id');

        $packingOkResponse = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 5,
            'ng_qty' => 0,
        ]);

        $packingOkResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $packing->id,
            'part_id' => $part->id,
        ]);
        $this->assertDatabaseHas('spks', ['id' => $spk->id, 'status' => 'Completed']);
    }

    public function test_input_page_marks_part_selector_for_packing_only(): void
    {
        Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);
        Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);
        $spk = Spk::factory()->create();

        $processPage = $this->get('/input-proses');

        $processPage->assertOk();
        $processPage->assertSee('name="spk_id"', false);
        $processPage->assertSee($spk->spk_no);
        $processPage->assertSee('Sewing');
        $processPage->assertDontSee('Packing');
        $processPage->assertDontSee('name="part_id"', false);

        $resultPage = $this->get('/input-hasil');

        $resultPage->assertOk();
        $resultPage->assertSee('Packing');
        $resultPage->assertSee('name="buyer_id"', false);
        $resultPage->assertSee('name="part_id"', false);
        $resultPage->assertSee('name="size_variant_id"', false);
    }

    public function test_fg_report_uses_only_packing_good_quantities(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amz']);
        $part = Part::factory()->create();
        $size8t = SizeVariant::factory()->create(['code' => '8T']);
        $size12q = SizeVariant::factory()->create(['code' => '12Q']);
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'spk_no' => 'SPK-AMZ-001']);
        $otherSpk = Spk::factory()->create(['buyer_id' => $buyer->id, 'spk_no' => 'SPK-AMZ-002']);
        $packing = Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'sort_order' => 50]);
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);

        ProductionEntry::factory()->create([
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size8t->id,
            'process_id' => $packing->id,
            'good_qty' => 55,
            'ng_qty' => 3,
        ]);
        ProductionEntry::factory()->create([
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size12q->id,
            'process_id' => $packing->id,
            'good_qty' => 162,
            'ng_qty' => 0,
        ]);
        ProductionEntry::factory()->create([
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size12q->id,
            'process_id' => $sewing->id,
            'good_qty' => 999,
            'ng_qty' => 0,
        ]);
        ProductionEntry::factory()->create([
            'spk_id' => $otherSpk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size12q->id,
            'process_id' => $packing->id,
            'good_qty' => 30,
            'ng_qty' => 0,
        ]);

        $response = $this->get('/reports/fg?production_date=2026-06-08&shift=2');

        $response->assertOk();
        $response->assertSee('Laporan Hasil Finish Good');
        $response->assertSee('GRAND TOTAL FINISH GOOD');
        $response->assertSee('total = 247pcs');
        $response->assertSee('8T = 55');
        $response->assertSee('12Q = 192');
        $response->assertDontSeeText('999 pcs');

        $filtered = $this->get("/reports/fg?production_date=2026-06-08&shift=2&spk_id={$spk->id}");

        $filtered->assertOk();
        $filtered->assertSee('total = 217pcs');
        $filtered->assertDontSeeText('247 pcs');

        $print = $this->get("/reports/fg/print?production_date=2026-06-08&shift=2&spk_id={$spk->id}");

        $print->assertOk();
        $print->assertSee('LAPORAN HASIL FINISH GOOD');
        $print->assertSee('No. Dokumen');
        $print->assertSee('Prepared By');
        $print->assertSee('Approved By');
    }

    public function test_spk_uses_ppic_sheet_columns(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);

        $response = $this->post('/spk', [
            'spk_no' => 'PO-MD-26-23',
            'spk_date' => '2026-06-11',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'buyer_id' => $buyer->id,
            'po_no' => 'PO-MD-26-23',
            'item' => 'Bonel Spring',
            'style' => '12" Queen',
            'target_qty' => 200,
            'remarks' => 'W~24',
            'shift' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/spk');
        $this->assertDatabaseHas('spks', [
            'spk_no' => 'PO-MD-26-23',
            'spk_date' => '2026-06-11 00:00:00',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'buyer_id' => $buyer->id,
            'po_no' => 'PO-MD-26-23',
            'item' => 'Bonel Spring',
            'style' => '12" Queen',
            'target_qty' => 200,
            'remarks' => 'W~24',
            'shift' => '1',
        ]);

        $page = $this->get('/spk');
        $page->assertOk();
        $page->assertSee('Tanggal SPK');
        $page->assertSee('Dept');
        $page->assertSee('PO');
        $page->assertSee('Item');
        $page->assertSee('Style');
        $page->assertSee('QTY Produksi');
        $page->assertSee('Remarks');
        $page->assertSee('Shift 1');
        $page->assertSee('Bonel Spring');
        $page->assertSee('12&quot; Queen', false);
    }

    public function test_spk_list_create_and_delete_are_separated(): void
    {
        $buyer = Buyer::factory()->create();
        $spk = Spk::create([
            'spk_no' => 'SPK-001',
            'spk_date' => '2026-06-11',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'buyer_id' => $buyer->id,
            'po_no' => 'PO-MD-26-23',
            'item' => 'Pocket Spring',
            'style' => '14" Queen',
            'target_qty' => 200,
            'remarks' => 'W~25',
            'shift' => '3',
        ]);

        $listPage = $this->get('/spk');
        $listPage->assertOk();
        $listPage->assertSee('Buat SPK Baru');
        $listPage->assertSee('SPK-001');
        $listPage->assertSee('Hapus');
        $listPage->assertDontSee('name="spk_no"', false);

        $createPage = $this->get('/spk/create');
        $createPage->assertOk();
        $createPage->assertSee('name="spk_no"', false);
        $createPage->assertSee('name="dept"', false);
        $createPage->assertSee('name="item"', false);
        $createPage->assertDontSee('Hapus');

        $deleteResponse = $this->delete("/spk/{$spk->id}");
        $deleteResponse->assertRedirect('/spk');
        $this->assertDatabaseMissing('spks', ['id' => $spk->id]);
    }

    public function test_spk_print_and_unit_kanban_cards_are_available(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        $spk = Spk::create([
            'spk_no' => 'SPK-UNIT-001',
            'spk_date' => '2026-06-12',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'buyer_id' => $buyer->id,
            'po_no' => 'PO-MD-26-23',
            'item' => 'Pocket Spring',
            'style' => '14" Queen',
            'target_qty' => 3,
            'remarks' => 'W~25',
            'shift' => '2',
        ]);

        $print = $this->get("/spk/{$spk->id}/print");

        $print->assertOk();
        $print->assertSee('SURAT PERINTAH KERJA');
        $print->assertSee('SPK-UNIT-001');
        $print->assertSee('Prepared By');
        $print->assertSee('Approved By');

        $kanban = $this->get("/spk/{$spk->id}/kanban-card");

        $kanban->assertOk();
        $kanban->assertSee('UNIT 001 / 003');
        $kanban->assertSee('UNIT 002 / 003');
        $kanban->assertSee('UNIT 003 / 003');
        $kanban->assertSee('LOT / SPK');
        $kanban->assertDontSee('JANGAN CAMPUR LOT', false);
    }
}
