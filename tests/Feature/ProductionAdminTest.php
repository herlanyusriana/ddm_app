<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Operator;
use App\Models\Part;
use App\Models\Process;
use App\Models\ProductionEntry;
use App\Models\SizeVariant;
use App\Models\Spk;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_dashboard_without_master_forms(): void
    {
        Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'sort_order' => 50]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Dashboard Produksi');
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
        $response->assertSee('data-menu-toggle', false);
        $response->assertSee('app-bottom-nav', false);
        $response->assertSee('data-install-prompt', false);
        $response->assertSee('data-install-button', false);
        $response->assertSee('mobile-bar', false);

        $manifest = file_get_contents(public_path('manifest.webmanifest'));
        $worker = file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString('DDM Production Admin', $manifest);
        $this->assertStringContainsString('"id": "/?source=pwa"', $manifest);
        $this->assertStringContainsString('"display_override"', $manifest);
        $this->assertStringContainsString('/pwa-icon.svg', $manifest);
        $this->assertStringContainsString('/icons/icon-192.png', $manifest);
        $this->assertStringContainsString('/icons/maskable-512.png', $manifest);
        $this->assertStringContainsString('ddm-production-v2', $worker);
        $this->assertStringContainsString('/dashboard', $worker);
        $this->assertStringContainsString('/warehouse', $worker);
        $this->assertStringContainsString('/input-hasil', $worker);
        $this->assertStringContainsString('/offline.html', $worker);
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('icons/maskable-512.png'));
    }

    public function test_master_data_sidebar_has_submenus(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Dashboard',
            'SPK (PPIC)',
            'Warehouse',
            'Input Proses (WIP)',
            'Input Hasil (FG)',
            'Report FG',
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

    public function test_operator_master_is_available_in_sidebar_and_has_separate_pages(): void
    {
        $sidebar = $this->get('/dashboard');

        $sidebar->assertOk();
        $sidebar->assertSee('Operator Master');
        $sidebar->assertSee('href="/masters/operators"', false);

        $list = $this->get('/masters/operators');

        $list->assertOk();
        $list->assertSee('Operator Master');
        $list->assertSee('Tambah Operator Baru');
        $list->assertDontSee('name="operator_code"', false);

        $create = $this->get('/masters/operators/create');

        $create->assertOk();
        $create->assertSee('name="operator_code"', false);
        $create->assertSee('name="name"', false);
    }

    public function test_operator_master_can_create_validate_and_delete_operator(): void
    {
        $create = $this->post('/masters/operators', [
            'operator_code' => '001',
            'name' => 'Siti Aminah',
        ]);

        $create->assertSessionHasNoErrors();
        $create->assertRedirect('/masters/operators');
        $this->assertDatabaseHas('operators', [
            'operator_code' => '001',
            'name' => 'Siti Aminah',
        ]);

        $list = $this->get('/masters/operators');
        $list->assertOk();
        $list->assertSee('001');
        $list->assertSee('Siti Aminah');

        $duplicate = $this->post('/masters/operators', [
            'operator_code' => '001',
            'name' => 'Operator Lain',
        ]);
        $duplicate->assertSessionHasErrors('operator_code');

        $operatorId = DB::table('operators')->where('operator_code', '001')->value('id');
        $delete = $this->delete('/masters/operators/'.$operatorId);

        $delete->assertRedirect('/masters/operators');
        $this->assertDatabaseMissing('operators', ['id' => $operatorId]);
    }

    public function test_operator_master_saves_and_displays_production_fields(): void
    {
        $response = $this->post('/masters/operators', [
            'operator_code' => '0012',
            'name' => 'Siti Aminah',
            'qc_label' => '007',
            'leader_name' => 'Budi',
            'target_prod' => 250,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/masters/operators');
        $this->assertDatabaseHas('operators', [
            'operator_code' => '0012',
            'name' => 'Siti Aminah',
            'qc_label' => '007',
            'leader_name' => 'Budi',
            'target_prod' => 250,
        ]);

        $list = $this->get('/masters/operators');
        $list->assertOk();
        $list->assertSeeInOrder(['No', 'Nama', 'QC Label', 'Group', 'Target Prod']);
        $list->assertSeeInOrder(['0012', 'Siti Aminah', '007', 'Budi', '250']);

        $create = $this->get('/masters/operators/create');
        $create->assertOk();
        $create->assertSee('name="qc_label"', false);
        $create->assertSee('name="leader_name"', false);
        $create->assertSee('name="target_prod"', false);
        $create->assertSee('operator-production-grid', false);
    }

    public function test_operator_production_fields_enforce_numeric_values(): void
    {
        $response = $this->post('/masters/operators', [
            'operator_code' => 'OP-12',
            'name' => 'Siti Aminah',
            'qc_label' => 'QC-7',
            'leader_name' => 'Budi',
            'target_prod' => -1,
        ]);

        $response->assertSessionHasErrors(['operator_code', 'qc_label', 'target_prod']);
        $this->assertDatabaseMissing('operators', ['name' => 'Siti Aminah']);
    }

    public function test_operator_master_can_export_and_import_excel(): void
    {
        DB::table('operators')->insert([
            'operator_code' => '0012',
            'name' => 'Nama Lama',
            'qc_label' => '001',
            'leader_name' => 'Leader Lama',
            'target_prod' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $list = $this->get('/masters/operators');
        $list->assertOk();
        $list->assertSee('href="/masters/operators/export"', false);
        $list->assertSee('href="/masters/operators/import"', false);

        $export = $this->get('/masters/operators/export');
        $export->assertOk();
        $export->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('operator_master_', $export->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $export->headers->get('Content-Disposition'));

        $importPage = $this->get('/masters/operators/import');
        $importPage->assertOk();
        $importPage->assertSee('No');
        $importPage->assertSee('QC LABEL');
        $importPage->assertSee('Target Prod');
        $importPage->assertSee('name="file"', false);

        $xlsx = $this->xlsx([
            ['No', 'Nama', 'QC LABEL', 'Group', 'Target Prod'],
            ['0012', 'Nama Diperbarui', '007', 'Budi', '250'],
            ['0013', 'Operator Baru', '008', 'Andi', '200'],
            ['ABC', 'Nomor Tidak Valid', '009', 'Andi', '200'],
            ['0014', 'Target Tidak Valid', '010', 'Andi', '-1'],
        ]);

        $import = $this->post('/masters/operators/import', [
            'file' => UploadedFile::fake()->createWithContent('operators.xlsx', $xlsx),
        ]);

        $import->assertSessionHasNoErrors();
        $import->assertRedirect('/masters/operators');
        $import->assertSessionHas('status', '2 operator berhasil diimport.');
        $this->assertDatabaseHas('operators', [
            'operator_code' => '0012',
            'name' => 'Nama Diperbarui',
            'qc_label' => '007',
            'leader_name' => 'Budi',
            'target_prod' => 250,
        ]);
        $this->assertDatabaseHas('operators', [
            'operator_code' => '0013',
            'name' => 'Operator Baru',
        ]);
        $this->assertDatabaseMissing('operators', ['operator_code' => 'ABC']);
        $this->assertDatabaseMissing('operators', ['operator_code' => '0014']);
        $this->assertDatabaseCount('operators', 2);
    }

    public function test_input_wip_sidebar_lists_only_wip_processes_in_sort_order(): void
    {
        $sewing = Process::factory()->create([
            'name' => 'Sewing',
            'is_input_process' => true,
            'sort_order' => 30,
        ]);
        $cutting = Process::factory()->create([
            'name' => 'Cutting',
            'is_input_process' => true,
            'sort_order' => 10,
        ]);
        Process::factory()->create([
            'name' => 'Packing',
            'is_input_process' => true,
            'is_fg_process' => true,
            'sort_order' => 50,
        ]);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSeeInOrder(['Input Proses (WIP)', 'Cutting', 'Sewing', 'Input Hasil (FG)']);
        $response->assertSee('href="/input-proses?process_id='.$cutting->id.'"', false);
        $response->assertSee('href="/input-proses?process_id='.$sewing->id.'"', false);
        $response->assertDontSee('href="/input-proses?process_id=3"', false);
    }

    public function test_input_wip_uses_sidebar_process_selection_and_falls_back_safely(): void
    {
        $cutting = Process::factory()->create([
            'name' => 'Cutting',
            'is_input_process' => true,
            'sort_order' => 10,
        ]);
        $sewing = Process::factory()->create([
            'name' => 'Sewing',
            'is_input_process' => true,
            'sort_order' => 30,
        ]);
        Process::factory()->create([
            'name' => 'Packing',
            'is_input_process' => true,
            'is_fg_process' => true,
            'sort_order' => 50,
        ]);

        $selectedPage = $this->get('/input-proses?process_id='.$sewing->id.'&production_date=2026-07-04&shift=2');

        $selectedPage->assertOk();
        $selectedPage->assertSee('name="process_id" value="'.$sewing->id.'"', false);
        $selectedPage->assertSee('Proses aktif');
        $selectedPage->assertSee('Sewing');
        $selectedPage->assertSee(
            'href="/input-proses?process_id='.$cutting->id.'&amp;production_date=2026-07-04&amp;shift=2"',
            false
        );
        $selectedPage->assertDontSee('type="radio" name="process_id"', false);

        $fallbackPage = $this->get('/input-proses?process_id=999999');

        $fallbackPage->assertOk();
        $fallbackPage->assertSee('name="process_id" value="'.$cutting->id.'"', false);

        $fgPage = $this->get('/input-hasil');

        $fgPage->assertOk();
        $fgPage->assertSee('type="radio" name="process_id"', false);
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
            'uom' => 'PCS',
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
            'uom' => 'PCS',
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

    public function test_referenced_buyer_is_archived_and_hidden_from_new_input(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ', 'name' => 'Amazon', 'is_active' => true]);
        Part::factory()->create(['buyer_id' => $buyer->id]);

        $response = $this->delete("/masters/buyers/{$buyer->id}");

        $response->assertRedirect('/masters/buyers');
        $this->assertDatabaseHas('buyers', ['id' => $buyer->id, 'is_active' => false]);
        $this->get('/masters/buyers')->assertSee('Diarsipkan');
        $this->get('/production/input-proses')->assertDontSee('AMZ · Amazon');
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

    public function test_part_master_can_export_and_import_excel_xlsx(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ', 'name' => 'Amazon']);
        Part::factory()->create([
            'buyer_id' => $buyer->id,
            'classification' => 'FG',
            'code' => '03.01.MAT-08T',
            'name' => '8inch Spring mattress Twin',
            'spec' => '75*39*8inch',
            'uom' => 'PCS',
        ]);

        $export = $this->get('/masters/parts/export');

        $export->assertOk();
        $export->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('part_master_', $export->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $export->headers->get('Content-Disposition'));

        $xlsx = $this->xlsx([
            ['buyer_code', 'classification', 'code', 'name', 'spec', 'uom', 'width_cm', 'depth_cm', 'height_cm', 'cbm_per_unit', 'net_weight_pc', 'gross_weight_pc', 'package_box', 'item_no', 'goods_description'],
            ['AMZ', 'FG', '03.01.MAT-08T', 'Updated Mattress', '75*39*8inch', 'PCS', '28', '28', '106', '0.08', '12.26', '13.76', '1', 'MAT-HY-BN-08T', '8 inch Hybrid Spring Mattress Twin'],
            ['', 'RM', '01.01', 'Steel Wire', '', 'KGM', '', '', '', '', '', '', '', '', ''],
        ]);

        $import = $this->post('/masters/parts/import', [
            'file' => UploadedFile::fake()->createWithContent('parts.xlsx', $xlsx),
        ]);

        $import->assertSessionHasNoErrors();
        $import->assertRedirect('/masters/parts');
        $this->assertDatabaseHas('parts', [
            'code' => '03.01.MAT-08T',
            'name' => 'Updated Mattress',
            'buyer_id' => $buyer->id,
            'uom' => 'PCS',
            'item_no' => 'MAT-HY-BN-08T',
        ]);
        $this->assertDatabaseHas('parts', [
            'code' => '01.01',
            'classification' => 'RM',
            'name' => 'Steel Wire',
            'uom' => 'KGM',
        ]);
        $this->assertDatabaseCount('parts', 2);
    }

    public function test_size_master_can_export_and_import_excel_xlsx(): void
    {
        SizeVariant::factory()->create(['production_code' => 'A', 'code' => '12Q', 'point' => 1.3]);

        $export = $this->get('/masters/sizes/export');

        $export->assertOk();
        $export->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('size_master_', $export->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $export->headers->get('Content-Disposition'));

        $xlsx = $this->xlsx([
            ['Code', 'Type', 'Point'],
            ['A', '12Q', '1,3'],
            ['B', '12Q', '0,65'],
        ]);

        $import = $this->post('/masters/sizes/import', [
            'file' => UploadedFile::fake()->createWithContent('sizes.xlsx', $xlsx),
        ]);

        $import->assertSessionHasNoErrors();
        $import->assertRedirect('/masters/sizes');
        $this->assertDatabaseHas('size_variants', ['production_code' => 'A', 'code' => '12Q', 'point' => 1.3]);
        $this->assertDatabaseHas('size_variants', ['production_code' => 'B', 'code' => '12Q', 'point' => 0.65]);
        $this->assertDatabaseCount('size_variants', 2);
    }

    public function test_dashboard_has_its_own_page(): void
    {
        $packing = Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'sort_order' => 50]);
        ProductionEntry::factory()->create([
            'production_date' => '2026-06-08',
            'shift' => '2',
            'process_id' => $packing->id,
            'good_qty' => 12,
            'ng_qty' => 3,
        ]);

        $response = $this->get('/dashboard?production_date=2026-06-08&shift=2');

        $response->assertOk();
        $response->assertSee('Dashboard Produksi');
        $response->assertSee('Total Produksi');
        $response->assertSee('Good');
        $response->assertSee('Reject');
        $response->assertSee('process-dashboard-grid', false);
        $response->assertSee('Packing');
        $response->assertDontSee('Tambah Buyer');
        $response->assertDontSee('<section class="topbar">', false);
    }

    public function test_pages_automatically_use_the_active_jakarta_production_window(): void
    {
        Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);

        $windows = [
            ['2026-07-04 10:00:00', '2026-07-04', '1'],
            ['2026-07-04 18:00:00', '2026-07-04', '2'],
            ['2026-07-05 01:00:00', '2026-07-04', '3'],
        ];

        foreach ($windows as [$now, $expectedDate, $expectedShift]) {
            CarbonImmutable::setTestNow(CarbonImmutable::parse($now, 'Asia/Jakarta'));

            foreach (['/dashboard', '/input-proses', '/input-hasil'] as $path) {
                $response = $this->get($path);

                $response->assertOk();
                $response->assertSee('value="'.$expectedDate.'"', false);
                $response->assertSee(
                    'value="'.$expectedShift.'" selected',
                    false
                );
            }
        }

        CarbonImmutable::setTestNow();
    }

    public function test_manual_date_and_shift_override_the_active_production_window(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-05 01:00:00', 'Asia/Jakarta'));

        $response = $this->get('/dashboard?production_date=2026-06-08&shift=2');

        $response->assertOk();
        $response->assertSee('value="2026-06-08"', false);
        $response->assertSee('value="2" selected', false);
        $response->assertSee('Kembali ke shift aktif');

        CarbonImmutable::setTestNow();
    }

    public function test_automatic_input_uses_the_current_window_at_submission_time(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-05 01:00:00', 'Asia/Jakarta'));
        $spk = Spk::factory()->create(['target_qty' => 100]);
        $process = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true]);

        $page = $this->get('/input-proses');

        $page->assertOk();
        $page->assertSee('name="automatic_window" value="1"', false);

        $response = $this->post('/production-entries', [
            'automatic_window' => '1',
            'spk_id' => $spk->id,
            'production_date' => '2026-07-03',
            'shift' => '1',
            'process_id' => $process->id,
            'good_qty' => 5,
            'reject_qty' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/input-proses?process_id='.$process->id);
        $this->assertDatabaseHas('production_entries', [
            'production_date' => '2026-07-04 00:00:00',
            'shift' => '3',
            'good_qty' => 5,
        ]);

        CarbonImmutable::setTestNow();
    }

    public function test_dashboard_summary_api_returns_good_and_reject_totals(): void
    {
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);
        ProductionEntry::factory()->create([
            'production_date' => '2026-07-04',
            'shift' => '2',
            'process_id' => $sewing->id,
            'good_qty' => 12,
            'ng_qty' => 3,
        ]);

        $response = $this->getJson('/api/dashboard-summary?production_date=2026-07-04&shift=2');

        $response->assertOk();
        $response->assertJsonPath('date', '2026-07-04');
        $response->assertJsonPath('shift', '2');
        $response->assertJsonPath('totals.total_qty', 15);
        $response->assertJsonPath('totals.good_qty', 12);
        $response->assertJsonPath('totals.reject_qty', 3);
        $response->assertJsonPath('processes.0.name', 'Sewing');
        $response->assertJsonPath('processes.0.good_rate', 80);
    }

    public function test_dashboard_contains_realtime_polling_targets(): void
    {
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);

        $response = $this->get('/dashboard?production_date=2026-07-04&shift=1');

        $response->assertOk();
        $response->assertSee('data-realtime-dashboard', false);
        $response->assertSee('data-summary-url="/api/dashboard-summary?production_date=2026-07-04&amp;shift=1"', false);
        $response->assertSee('data-dashboard-total', false);
        $response->assertSee('data-dashboard-good', false);
        $response->assertSee('data-dashboard-reject', false);
        $response->assertSee('data-process-id="'.$sewing->id.'"', false);
        $response->assertSee('5000');
        $response->assertSee('visibilitychange');
    }

    public function test_automatic_dashboard_polling_keeps_following_the_active_shift(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-04 15:59:00', 'Asia/Jakarta'));

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-summary-url="/api/dashboard-summary"', false);
        $response->assertSee('input[name="production_date"]', false);
        $response->assertSee('select[name="shift"]', false);

        CarbonImmutable::setTestNow();
    }

    public function test_warehouse_page_uses_preparation_workflow_layout(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        Spk::factory()->create(['buyer_id' => $buyer->id, 'spk_no' => 'SPK-PENDING', 'target_qty' => 100, 'status' => 'Pending']);
        Spk::factory()->create(['buyer_id' => $buyer->id, 'spk_no' => 'SPK-PREPARED', 'target_qty' => 50, 'status' => 'Material Prepared']);

        $response = $this->get('/warehouse');

        $response->assertOk();
        $response->assertSee('Warehouse');
        $response->assertSee('Material Pending');
        $response->assertSee('Material Siap');
        $response->assertSee('warehouse-workflow-grid', false);
        $response->assertSee('Siapkan Material');
        $response->assertSee('SPK-PENDING');
        $response->assertSee('SPK-PREPARED');
        $response->assertDontSee('<section class="topbar">', false);
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
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'target_qty' => 20]);
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
        $this->assertDatabaseHas('spks', ['id' => $spk->id, 'status' => 'In Production']);
    }

    public function test_production_input_cannot_exceed_spk_lot_total_per_process(): void
    {
        $buyer = Buyer::factory()->create();
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'target_qty' => 100]);
        $process = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);

        ProductionEntry::factory()->create([
            'spk_id' => $spk->id,
            'buyer_id' => $buyer->id,
            'part_id' => null,
            'size_variant_id' => null,
            'process_id' => $process->id,
            'good_qty' => 60,
            'repairable_qty' => 10,
            'scrap_qty' => 0,
            'ng_qty' => 10,
        ]);

        $response = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'process_id' => $process->id,
            'good_qty' => 31,
            'reject_qty' => 0,
        ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertDatabaseMissing('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $process->id,
            'good_qty' => 31,
        ]);

        $ok = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'process_id' => $process->id,
            'good_qty' => 20,
            'reject_qty' => 10,
        ]);

        $ok->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $process->id,
            'good_qty' => 20,
            'repairable_qty' => 10,
            'scrap_qty' => 0,
            'ng_qty' => 10,
        ]);
    }

    public function test_api_production_input_cannot_exceed_spk_lot_total_per_process(): void
    {
        $buyer = Buyer::factory()->create();
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'target_qty' => 50]);
        $process = Process::factory()->create(['name' => 'Binding', 'is_input_process' => true, 'sort_order' => 40]);

        ProductionEntry::factory()->create([
            'spk_id' => $spk->id,
            'buyer_id' => $buyer->id,
            'part_id' => null,
            'size_variant_id' => null,
            'process_id' => $process->id,
            'good_qty' => 45,
            'repairable_qty' => 0,
            'scrap_qty' => 0,
            'ng_qty' => 0,
        ]);

        $response = $this->postJson('/api/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'process_id' => $process->id,
            'good_qty' => 6,
            'reject_qty' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('good_qty');
    }

    public function test_input_pages_separate_wip_and_packing_processes(): void
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
        $processPage->assertSee('name="buyer_id"', false);
        $processPage->assertSee('name="part_id"', false);
        $processPage->assertSee('name="size_variant_id"', false);

        $resultPage = $this->get('/input-hasil');

        $resultPage->assertOk();
        $resultPage->assertSee('Packing');
        $resultPage->assertSee('name="buyer_id"', false);
        $resultPage->assertSee('name="part_id"', false);
        $resultPage->assertSee('name="size_variant_id"', false);
    }

    public function test_wip_and_fg_forms_allow_custom_input_without_spk(): void
    {
        Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);
        Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);
        Buyer::factory()->create(['code' => 'AMZ', 'name' => 'Amazon']);
        Part::factory()->create(['code' => 'ITEM-001', 'name' => 'Pocket Spring']);
        SizeVariant::factory()->create(['code' => '12Q']);

        foreach (['/input-proses', '/input-hasil'] as $path) {
            $page = $this->get($path);

            $page->assertOk();
            $page->assertSee('Custom / Tanpa SPK');
            $page->assertSee('name="spk_id"', false);
            $page->assertDontSee('name="spk_id" required', false);
            $page->assertSee('name="buyer_id"', false);
            $page->assertSee('name="part_id"', false);
            $page->assertSee('name="size_variant_id"', false);
            $page->assertSee('data-custom-production-fields', false);
            $page->assertSee('ITEM-001');
            $page->assertSee('12Q');
        }
    }

    public function test_custom_mode_hide_target_is_attached_to_item_not_buyer(): void
    {
        Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true]);

        $page = $this->get('/input-proses');

        $page->assertOk();
        $this->assertMatchesRegularExpression(
            '/<div class="field">\s*<label>Kode Buyer<\/label>/',
            $page->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<div class="field" data-spk-item-field>\s*<label>Item/',
            $page->getContent()
        );
    }

    public function test_custom_wip_entry_persists_master_data_without_spk(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ']);
        $part = Part::factory()->create(['buyer_id' => $buyer->id, 'code' => 'ITEM-001']);
        $size = SizeVariant::factory()->create(['production_code' => 'A', 'code' => '12Q', 'point' => 1.3]);
        $process = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true]);

        $response = $this->post('/production-entries', [
            'production_date' => '2026-07-04',
            'shift' => '1',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $process->id,
            'good_qty' => 20,
            'reject_qty' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/input-proses?process_id='.$process->id.'&production_date=2026-07-04&shift=1');
        $this->assertDatabaseHas('production_entries', [
            'spk_id' => null,
            'buyer_id' => $buyer->id,
            'part_id' => null,
            'size_variant_id' => $size->id,
            'process_id' => $process->id,
            'good_qty' => 20,
            'ng_qty' => 2,
        ]);

        $history = $this->get('/input-proses?production_date=2026-07-04&shift=1');
        $history->assertOk();
        $history->assertSee('Custom');
        $history->assertSee('12Q');
    }

    public function test_custom_fg_entry_requires_buyer_and_size_but_not_item(): void
    {
        $amazon = Buyer::factory()->create(['code' => 'AMZ']);
        $wayfair = Buyer::factory()->create(['code' => 'WF']);
        $wrongPart = Part::factory()->create([
            'buyer_id' => $wayfair->id,
            'classification' => 'FG',
        ]);
        $size = SizeVariant::factory()->create();
        $packing = Process::factory()->create([
            'name' => 'Packing',
            'is_input_process' => true,
            'is_fg_process' => true,
        ]);

        $missing = $this->post('/production-entries', [
            'production_date' => '2026-07-04',
            'shift' => '1',
            'process_id' => $packing->id,
            'good_qty' => 5,
            'reject_qty' => 0,
        ]);
        $missing->assertSessionHasErrors(['buyer_id', 'size_variant_id']);
        $missing->assertSessionDoesntHaveErrors('part_id');

        $mismatch = $this->post('/production-entries', [
            'production_date' => '2026-07-04',
            'shift' => '1',
            'buyer_id' => $amazon->id,
            'part_id' => $wrongPart->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 5,
            'reject_qty' => 0,
        ]);
        $mismatch->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'process_id' => $packing->id,
            'buyer_id' => $amazon->id,
            'size_variant_id' => $size->id,
            'part_id' => null,
        ]);
    }

    public function test_binding_requires_operator_while_other_processes_do_not(): void
    {
        $buyer = Buyer::factory()->create();
        $size = SizeVariant::factory()->create();
        $operator = Operator::create(['operator_code' => '0012', 'name' => 'Siti', 'target_prod' => 250]);
        $binding = Process::factory()->create(['name' => 'Binding', 'is_input_process' => true]);
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true]);

        $bindingPage = $this->get('/input-proses?process_id='.$binding->id.'&production_date=2026-07-05&shift=1');
        $bindingPage->assertOk();
        $bindingPage->assertSee('name="operator_id"', false);
        $bindingPage->assertSee('name="operator_search"', false);
        $bindingPage->assertSee('list="operator-suggestions"', false);
        $bindingPage->assertSee('0012 · Siti');
        $bindingPage->assertSee('Export History Excel');
        $bindingPage->assertSee('process_id='.$binding->id, false);

        $missing = $this->post('/production-entries', [
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'size_variant_id' => $size->id,
            'process_id' => $binding->id, 'good_qty' => 10, 'reject_qty' => 1,
        ]);
        $missing->assertSessionHasErrors('operator_id');

        $bindingEntry = $this->post('/production-entries', [
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'size_variant_id' => $size->id,
            'operator_id' => $operator->id, 'process_id' => $binding->id,
            'good_qty' => 10, 'reject_qty' => 1,
        ]);
        $bindingEntry->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'process_id' => $binding->id, 'operator_id' => $operator->id, 'part_id' => null,
        ]);

        $otherEntry = $this->post('/production-entries', [
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'size_variant_id' => $size->id,
            'process_id' => $sewing->id, 'good_qty' => 5, 'reject_qty' => 0,
        ]);
        $otherEntry->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'process_id' => $sewing->id, 'operator_id' => null, 'part_id' => null,
        ]);
    }

    public function test_binding_hourly_export_uses_operator_target_and_hour_bucket(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ']);
        $size = SizeVariant::factory()->create(['production_code' => 'A', 'code' => '12Q', 'point' => 1.3]);
        $operator = Operator::create(['operator_code' => '0012', 'name' => 'Siti', 'target_prod' => 250]);
        $binding = Process::factory()->create(['name' => 'Binding', 'is_input_process' => true]);
        ProductionEntry::factory()->create([
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'part_id' => null, 'size_variant_id' => $size->id,
            'process_id' => $binding->id, 'operator_id' => $operator->id,
            'good_qty' => 12, 'ng_qty' => 2,
            'created_at' => '2026-07-05 01:30:00', 'updated_at' => '2026-07-05 01:30:00',
        ]);

        $response = $this->get('/reports/production-hourly?production_date=2026-07-05&shift=1&process_id='.$binding->id);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $sheet = $this->xlsxSheetXml($response->getContent());
        $this->assertStringContainsString('Target', $sheet);
        $this->assertStringContainsString('Jam 1', $sheet);
        $this->assertStringContainsString('0012', $sheet);
        $this->assertStringContainsString('Siti', $sheet);
        $this->assertStringContainsString('250', $sheet);
        $this->assertStringContainsString('AMZ / A-12Q = 12, Reject = 2', $sheet);
        $this->assertStringContainsString('Total Point', $sheet);
        $this->assertStringContainsString('15.6', $sheet);
    }

    public function test_non_binding_hourly_export_omits_operator_identity(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ']);
        $size = SizeVariant::factory()->create(['code' => '12Q']);
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true]);
        ProductionEntry::factory()->create([
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'part_id' => null, 'size_variant_id' => $size->id,
            'process_id' => $sewing->id, 'good_qty' => 8, 'ng_qty' => 1,
            'created_at' => '2026-07-05 01:10:00', 'updated_at' => '2026-07-05 01:10:00',
        ]);

        $response = $this->get('/reports/production-hourly?production_date=2026-07-05&shift=1&process_id='.$sewing->id);

        $response->assertOk();
        $sheet = $this->xlsxSheetXml($response->getContent());
        $this->assertStringContainsString('Buyer', $sheet);
        $this->assertStringContainsString('Size', $sheet);
        $this->assertStringContainsString('AMZ', $sheet);
        $this->assertStringContainsString('12Q', $sheet);
        $this->assertStringNotContainsString('Nama Operator', $sheet);
        $this->assertStringNotContainsString('Target Operator', $sheet);
    }

    public function test_binding_history_displays_hourly_report(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ']);
        $size = SizeVariant::factory()->create(['production_code' => 'A', 'code' => '12Q', 'point' => 1.3]);
        $operator = Operator::create(['operator_code' => '0012', 'name' => 'Siti', 'target_prod' => 250]);
        $binding = Process::factory()->create(['name' => 'Binding', 'is_input_process' => true]);
        ProductionEntry::factory()->create([
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'part_id' => null, 'size_variant_id' => $size->id,
            'process_id' => $binding->id, 'operator_id' => $operator->id,
            'good_qty' => 12, 'ng_qty' => 2,
            'created_at' => '2026-07-05 01:30:00', 'updated_at' => '2026-07-05 01:30:00',
        ]);

        $page = $this->get('/input-proses?process_id='.$binding->id.'&production_date=2026-07-05&shift=1');

        $page->assertOk();
        $page->assertSee('Jam 1');
        $page->assertSee('Nama Operator');
        $page->assertSee('Siti');
        $page->assertSee('08:30 · AMZ / A-12Q = 12, Reject = 2');
        $page->assertSee('Total Point');
        $page->assertSee('TOTAL PER JAM');
        $page->assertSee('G: 12 · R: 2');
    }

    public function test_non_binding_history_matches_hourly_excel(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ']);
        $size = SizeVariant::factory()->create(['code' => '12Q']);
        $sewing = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true]);
        ProductionEntry::factory()->create([
            'production_date' => '2026-07-05', 'shift' => '1',
            'buyer_id' => $buyer->id, 'part_id' => null, 'size_variant_id' => $size->id,
            'process_id' => $sewing->id, 'good_qty' => 8, 'ng_qty' => 1,
            'created_at' => '2026-07-05 01:10:00', 'updated_at' => '2026-07-05 01:10:00',
        ]);

        $page = $this->get('/input-proses?process_id='.$sewing->id.'&production_date=2026-07-05&shift=1');
        $export = $this->get('/reports/production-hourly?production_date=2026-07-05&shift=1&process_id='.$sewing->id);
        $sheet = $this->xlsxSheetXml($export->getContent());
        $expected = '08:10 · Good = 8, Reject = 1';

        $page->assertOk();
        $page->assertSee($expected);
        $page->assertSee('AMZ');
        $page->assertSee('12Q');
        $this->assertStringContainsString($expected, $sheet);
    }

    public function test_production_input_chooses_production_code_for_size_point(): void
    {
        Process::factory()->create(['name' => 'Binding', 'is_input_process' => true]);
        SizeVariant::factory()->create(['production_code' => 'A', 'code' => '10F', 'point' => 2]);
        SizeVariant::factory()->create(['production_code' => 'B', 'code' => '10F', 'point' => 1]);

        $page = $this->get('/input-proses');

        $page->assertOk();
        $page->assertSee('name="production_code"', false);
        $page->assertSee('data-production-code="A"', false);
        $page->assertSee('data-production-code="B"', false);
        $page->assertSee('Code Produksi');
        $page->assertSeeInOrder(['Code Produksi', 'Kode Size']);
        $page->assertSee('option.hidden = !matchesCode', false);
    }

    public function test_monthly_history_displays_operator_per_date_performance_and_matches_excel(): void
    {
        $buyer = Buyer::factory()->create(['code' => 'AMZ']);
        $size = SizeVariant::factory()->create(['production_code' => 'A', 'code' => '10F', 'point' => 2]);
        $operator = Operator::create(['operator_code' => '15', 'name' => 'Fajarudin', 'target_prod' => 20]);
        $binding = Process::factory()->create(['name' => 'Binding', 'is_input_process' => true]);

        foreach ([['2026-07-05', 10], ['2026-07-06', 15]] as [$date, $good]) {
            ProductionEntry::factory()->create([
                'production_date' => $date, 'shift' => '1',
                'buyer_id' => $buyer->id, 'size_variant_id' => $size->id,
                'process_id' => $binding->id, 'operator_id' => $operator->id,
                'good_qty' => $good, 'ng_qty' => 1,
            ]);
        }

        $query = 'history_period=monthly&production_month=2026-07&production_date=2026-07-05&shift=1&process_id='.$binding->id;
        $page = $this->get('/input-proses?'.$query);
        $export = $this->get('/reports/production-hourly?'.$query);
        $sheet = $this->xlsxSheetXml($export->getContent());

        $page->assertOk();
        $page->assertSee('05 Jul 2026');
        $page->assertSee('06 Jul 2026');
        $page->assertSee('Pencapaian Target');
        $page->assertSee('50%');
        $page->assertSee('75%');
        $page->assertDontSee('Jam 1');
        $this->assertStringContainsString('05 Jul 2026', $sheet);
        $this->assertStringContainsString('75%', $sheet);
    }

    public function test_trouble_mode_records_time_range_and_displays_duration_in_history(): void
    {
        $buyer = Buyer::factory()->create();
        $size = SizeVariant::factory()->create(['production_code' => 'A', 'code' => '10F']);
        $operator = Operator::create(['operator_code' => '15', 'name' => 'Fajarudin']);
        $binding = Process::factory()->create(['name' => 'Binding', 'is_input_process' => true]);

        $page = $this->get('/input-proses?process_id='.$binding->id.'&production_date=2026-07-05&shift=1');
        $page->assertOk();
        $page->assertSeeInOrder(['SPK / Lot Produksi', 'Mode Pencatatan']);
        $page->assertSee('value="production"', false);
        $page->assertSee('value="trouble"', false);
        $page->assertSee('name="trouble_type"', false);
        $page->assertSee('name="trouble_start_time"', false);
        $page->assertSee('name="trouble_end_time"', false);

        $response = $this->post('/production-entries', [
            'record_mode' => 'trouble',
            'production_date' => '2026-07-05',
            'shift' => '1',
            'buyer_id' => $buyer->id,
            'size_variant_id' => $size->id,
            'process_id' => $binding->id,
            'operator_id' => $operator->id,
            'trouble_type' => 'Mesin',
            'trouble_start_time' => '09:15',
            'trouble_end_time' => '10:00',
            'trouble_notes' => 'Ganti bearing',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/input-proses?process_id='.$binding->id.'&production_date=2026-07-05&shift=1');
        $this->assertDatabaseHas('production_troubles', [
            'process_id' => $binding->id,
            'operator_id' => $operator->id,
            'trouble_type' => 'Mesin',
            'start_time' => '09:15',
            'end_time' => '10:00',
            'notes' => 'Ganti bearing',
        ]);

        $history = $this->get('/input-proses?process_id='.$binding->id.'&production_date=2026-07-05&shift=1');
        $history->assertSee('History Trouble');
        $history->assertSee('09:15 - 10:00');
        $history->assertSee('45 menit');
        $history->assertSee('Ganti bearing');
    }

    public function test_production_input_uses_good_and_reject_only(): void
    {
        Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);
        $page = $this->get('/input-proses');

        $page->assertOk();
        $page->assertSee('name="good_qty"', false);
        $page->assertSee('name="reject_qty"', false);
        $page->assertDontSee('name="repairable_qty"', false);
        $page->assertDontSee('name="scrap_qty"', false);
        $page->assertSee('Reject');
    }

    public function test_reject_input_creates_rework_debt(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'spk_no' => 'SPK-RWK-001', 'target_qty' => 100]);
        $process = Process::factory()->create(['name' => 'Sewing', 'is_input_process' => true, 'sort_order' => 30]);

        $response = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-17',
            'shift' => '1',
            'process_id' => $process->id,
            'good_qty' => 80,
            'reject_qty' => 5,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $process->id,
            'good_qty' => 80,
            'repairable_qty' => 5,
            'scrap_qty' => 0,
            'ng_qty' => 5,
        ]);

        $rework = $this->get('/rework');
        $rework->assertOk();
        $rework->assertSee('Hutang Rework');
        $rework->assertSee('SPK-RWK-001');
        $rework->assertSee('Sewing');
        $rework->assertSee('5');
    }

    public function test_fg_input_filters_parts_by_selected_spk_buyer(): void
    {
        $amazon = Buyer::factory()->create(['name' => 'Amazon']);
        $wayfair = Buyer::factory()->create(['name' => 'Wayfair']);
        $amazonPart = Part::factory()->create(['buyer_id' => $amazon->id, 'classification' => 'FG', 'code' => 'AMZ-12Q']);
        $wayfairPart = Part::factory()->create(['buyer_id' => $wayfair->id, 'classification' => 'FG', 'code' => 'WF-14Q']);
        $genericPart = Part::factory()->create(['buyer_id' => null, 'classification' => 'FG', 'code' => 'GEN-10T']);
        Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);
        $spk = Spk::factory()->create(['buyer_id' => $amazon->id, 'spk_no' => 'SPK-AMZ-001']);

        $response = $this->get('/input-hasil');

        $response->assertOk();
        $response->assertSee('data-buyer-id="'.$amazon->id.'"', false);
        $response->assertSee('data-part-id="'.$amazonPart->id.'"', false);
        $response->assertSee('data-part-buyer-id="'.$amazon->id.'"', false);
        $response->assertSee('data-part-id="'.$genericPart->id.'"', false);
        $response->assertSee('data-part-buyer-id=""', false);
        $response->assertSee('data-part-id="'.$wayfairPart->id.'"', false);
        $response->assertSee('syncProductionMasterFields', false);
    }

    public function test_fg_input_rejects_part_from_different_buyer(): void
    {
        $amazon = Buyer::factory()->create(['name' => 'Amazon']);
        $wayfair = Buyer::factory()->create(['name' => 'Wayfair']);
        $wrongPart = Part::factory()->create(['buyer_id' => $wayfair->id, 'classification' => 'FG']);
        $size = SizeVariant::factory()->create(['code' => '12Q']);
        $packing = Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);
        $spk = Spk::factory()->create(['buyer_id' => $amazon->id, 'target_qty' => 100]);

        $response = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $amazon->id,
            'part_id' => $wrongPart->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 5,
            'ng_qty' => 0,
        ]);

        $response->assertSessionHasErrors('part_id');
        $this->assertDatabaseMissing('production_entries', [
            'spk_id' => $spk->id,
            'part_id' => $wrongPart->id,
        ]);
    }

    public function test_production_entry_rejects_spk_process_capacity_overflow(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        $part = Part::factory()->create(['buyer_id' => $buyer->id, 'classification' => 'FG']);
        $size = SizeVariant::factory()->create(['code' => '12Q']);
        $packing = Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'target_qty' => 100]);

        $firstResponse = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 60,
            'reject_qty' => 10,
        ]);

        $firstResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $packing->id,
            'good_qty' => 60,
            'repairable_qty' => 10,
        ]);

        $secondResponse = $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 31,
            'reject_qty' => 0,
        ]);

        $secondResponse->assertSessionHasErrors('good_qty');
        $this->assertDatabaseMissing('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $packing->id,
            'good_qty' => 31,
        ]);
    }

    public function test_api_production_entry_rejects_spk_process_capacity_overflow(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        $part = Part::factory()->create(['buyer_id' => $buyer->id, 'classification' => 'FG']);
        $size = SizeVariant::factory()->create(['code' => '12Q']);
        $packing = Process::factory()->create(['name' => 'Packing', 'is_input_process' => true, 'is_fg_process' => true, 'sort_order' => 50]);
        $spk = Spk::factory()->create(['buyer_id' => $buyer->id, 'target_qty' => 100]);

        $this->post('/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 60,
            'reject_qty' => 10,
        ]);

        $response = $this->postJson('/api/production-entries', [
            'spk_id' => $spk->id,
            'production_date' => '2026-06-08',
            'shift' => '2',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'process_id' => $packing->id,
            'good_qty' => 31,
            'reject_qty' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('good_qty');
        $this->assertDatabaseMissing('production_entries', [
            'spk_id' => $spk->id,
            'process_id' => $packing->id,
            'good_qty' => 31,
        ]);
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
            'spk_date' => '2026-06-11',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'shift' => '1',
            'items' => [
                [
                    'buyer_id' => $buyer->id,
                    'po_no' => 'PO-MD-26-23',
                    'item' => 'Bonel Spring',
                    'style' => '12" Queen',
                    'target_qty' => 200,
                    'remarks' => 'W~24',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/spk');
        $this->assertDatabaseHas('spks', [
            'spk_no' => 'SPK-20260611-001',
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

    public function test_spk_can_create_multiple_items_and_new_buyer_with_generated_number(): void
    {
        $amazon = Buyer::factory()->create(['name' => 'Amazon']);

        $response = $this->post('/spk', [
            'spk_date' => '2026-06-17',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'shift' => '2',
            'notes' => 'Produksi harian',
            'items' => [
                [
                    'buyer_id' => $amazon->id,
                    'po_no' => 'PO-AMZ-001',
                    'item' => 'Pocket Spring',
                    'style' => '12" Queen',
                    'target_qty' => 100,
                    'remarks' => 'W~24',
                ],
                [
                    'buyer_name' => 'Wayfair',
                    'po_no' => 'PO-WF-001',
                    'item' => 'Bonel Spring',
                    'style' => '14" Queen',
                    'target_qty' => 200,
                    'remarks' => 'W~25',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/spk');
        $this->assertDatabaseHas('buyers', ['name' => 'Wayfair']);
        $this->assertDatabaseHas('spks', [
            'spk_no' => 'SPK-20260617-001',
            'buyer_id' => $amazon->id,
            'po_no' => 'PO-AMZ-001',
            'item' => 'Pocket Spring',
            'target_qty' => 100,
        ]);
        $this->assertDatabaseHas('spks', [
            'spk_no' => 'SPK-20260617-001',
            'po_no' => 'PO-WF-001',
            'item' => 'Bonel Spring',
            'target_qty' => 200,
        ]);
        $this->assertSame(2, Spk::where('spk_no', 'SPK-20260617-001')->count());
    }

    public function test_spk_item_can_autofill_from_part_master(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        $part = Part::factory()->create([
            'buyer_id' => $buyer->id,
            'classification' => 'FG',
            'code' => '03.01.MAT-12Q',
            'name' => '12inch Spring mattress Queen',
            'spec' => '80*60*12inch',
        ]);
        $size = SizeVariant::factory()->create(['code' => '12Q']);

        $createPage = $this->get('/spk/create');
        $createPage->assertOk();
        $createPage->assertSee('data-part-select', false);
        $createPage->assertSee('partMasterData', false);
        $createPage->assertSee('03.01.MAT-12Q');
        $createPage->assertSee('12inch Spring mattress Queen');

        $response = $this->post('/spk', [
            'spk_date' => '2026-06-17',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'shift' => '1',
            'items' => [
                [
                    'part_id' => $part->id,
                    'po_no' => 'PO-AMZ-012Q',
                    'target_qty' => 120,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('spks', [
            'spk_no' => 'SPK-20260617-001',
            'buyer_id' => $buyer->id,
            'part_id' => $part->id,
            'size_variant_id' => $size->id,
            'item' => '12inch Spring mattress Queen',
            'style' => '80*60*12inch',
            'target_qty' => 120,
        ]);
    }

    public function test_spk_part_selection_only_allows_finish_good_parts(): void
    {
        $buyer = Buyer::factory()->create(['name' => 'Amazon']);
        $fgPart = Part::factory()->create([
            'buyer_id' => $buyer->id,
            'classification' => 'FG',
            'code' => 'FG-12Q',
            'name' => 'Finish Good Mattress',
            'spec' => '12 Queen',
        ]);
        $rmPart = Part::factory()->create([
            'buyer_id' => $buyer->id,
            'classification' => 'RM',
            'code' => 'RM-WIRE',
            'name' => 'Steel Wire',
            'spec' => 'Wire',
        ]);
        $wipPart = Part::factory()->create([
            'buyer_id' => $buyer->id,
            'classification' => 'WIP',
            'code' => 'WIP-POCKET',
            'name' => 'Pocket Spring WIP',
            'spec' => 'WIP',
        ]);

        $createPage = $this->get('/spk/create');
        $createPage->assertOk();
        $createPage->assertSee('FG-12Q');
        $createPage->assertDontSee('RM-WIRE');
        $createPage->assertDontSee('WIP-POCKET');

        $response = $this->post('/spk', [
            'spk_date' => '2026-06-17',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'shift' => '1',
            'items' => [
                [
                    'part_id' => $rmPart->id,
                    'po_no' => 'PO-RM',
                    'target_qty' => 10,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.part_id');
        $this->assertDatabaseMissing('spks', ['part_id' => $rmPart->id]);

        $ok = $this->post('/spk', [
            'spk_date' => '2026-06-17',
            'dept' => 'Hotmelt, Binding, Packing',
            'month' => 'Juni',
            'shift' => '1',
            'items' => [
                [
                    'part_id' => $fgPart->id,
                    'po_no' => 'PO-FG',
                    'target_qty' => 10,
                ],
            ],
        ]);

        $ok->assertSessionHasNoErrors();
        $this->assertDatabaseHas('spks', ['part_id' => $fgPart->id]);
        $this->assertDatabaseMissing('spks', ['part_id' => $wipPart->id]);
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
        $createPage->assertDontSee('name="spk_no"', false);
        $createPage->assertSee('name="dept"', false);
        $createPage->assertSee('name="items[0][item]"', false);
        $createPage->assertSee('data-add-spk-item', false);
        $createPage->assertDontSee('_method" value="DELETE', false);

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
        $print->assertSee('Manager');
        $print->assertSee('Factory Manager');
        $print->assertDontSee('Supervisor Produksi');

        $kanban = $this->get("/spk/{$spk->id}/kanban-card");

        $kanban->assertOk();
        $kanban->assertSee('UNIT 001 / 003');
        $kanban->assertSee('UNIT 002 / 003');
        $kanban->assertSee('UNIT 003 / 003');
        $kanban->assertSee('LOT / SPK');
        $kanban->assertDontSee('JANGAN CAMPUR LOT', false);
    }

    private function xlsx(array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>
</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');

        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $sheet .= '<row r="'.$excelRow.'">';
            foreach ($row as $colIndex => $value) {
                $cell = $this->xlsxColumnName($colIndex + 1).$excelRow;
                $sheet .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        return $content;
    }

    private function xlsxSheetXml(string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'read_xlsx_');
        file_put_contents($tmp, $content);
        $zip = new \ZipArchive();
        $zip->open($tmp);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        return html_entity_decode($sheet, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
