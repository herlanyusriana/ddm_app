<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerPartSize;
use App\Models\Part;
use App\Models\Process;
use App\Models\ProductionEntry;
use App\Models\SizeVariant;
use App\Models\Spk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductionAdminController extends Controller
{
    public function inputProses(Request $request): View
    {
        return $this->renderInputPage($request, 'proses');
    }

    public function inputHasil(Request $request): View
    {
        return $this->renderInputPage($request, 'hasil');
    }

    private function renderInputPage(Request $request, string $type): View
    {
        $date = $request->query('production_date', now()->toDateString());
        $shift = $request->query('shift', '1');

        $inputProcesses = Process::where('is_input_process', true)->orderBy('sort_order')->get();
        if ($type === 'proses') {
            $inputProcesses = $inputProcesses->reject(fn ($p) => $this->processRequiresPart($p));
            $title = 'Input Proses (WIP)';
        } else {
            $inputProcesses = $inputProcesses->filter(fn ($p) => $this->processRequiresPart($p));
            $title = 'Input Hasil (FG/Packing)';
        }

        return view('production.index', [
            'pageType' => $type,
            'pageTitle' => $title,
            'date' => $date,
            'shift' => $shift,
            'shiftOptions' => $this->shiftOptions(),
            'buyers' => Buyer::orderBy('name')->get(),
            'parts' => Part::orderBy('code')->get(),
            'sizes' => SizeVariant::orderBy('code')->get(),
            'spks' => Spk::with('buyer')
                ->whereIn('status', ['Pending', 'Material Prepared', 'In Production'])
                ->latest()
                ->get(),
            'inputProcesses' => $inputProcesses,
            'entries' => ProductionEntry::with(['spk', 'buyer', 'part', 'sizeVariant', 'process'])
                ->whereDate('production_date', $date)
                ->where('shift', $shift)
                ->whereIn('process_id', $inputProcesses->pluck('id'))
                ->latest()
                ->get(),
        ]);
    }

    public function masters(?string $section = null): View
    {
        $section ??= 'buyers';

        return view('production.masters', [
            'section' => $section,
            'buyers' => Buyer::orderBy('name')->get(),
            'parts' => Part::with('buyer')->orderBy('code')->get(),
            'sizes' => SizeVariant::orderBy('code')->get(),
            'processes' => Process::orderBy('sort_order')->get(),
            'mappings' => BuyerPartSize::with(['buyer', 'part', 'sizeVariant'])->latest()->limit(20)->get(),
        ]);
    }

    public function dashboard(Request $request): View
    {
        $date = $request->query('production_date', now()->toDateString());
        $shift = $request->query('shift', '1');

        return view('production.dashboard', [
            'date' => $date,
            'shift' => $shift,
            'shiftOptions' => $this->shiftOptions(),
            'summaries' => $this->processSummaries($date, $shift),
        ]);
    }

    public function createBuyer(): View
    {
        return view('production.buyer-create');
    }

    public function createPart(): View
    {
        return view('production.part-create', ['buyers' => Buyer::orderBy('name')->get()]);
    }

    public function importPartsForm(): View
    {
        return view('production.part-import');
    }

    public function createSize(): View
    {
        return view('production.size-create');
    }

    public function importSizesForm(): View
    {
        return view('production.size-import');
    }

    public function storeBuyer(Request $request): RedirectResponse
    {
        Buyer::create($request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:buyers,code'],
            'name' => ['required', 'string', 'max:120'],
        ]));

        return redirect('/masters/buyers')->with('status', 'Buyer master tersimpan.');
    }

    public function destroyBuyer(Buyer $buyer): RedirectResponse
    {
        $buyer->delete();

        return redirect('/masters/buyers')->with('status', 'Buyer master terhapus.');
    }

    public function storePart(Request $request): RedirectResponse
    {
        Part::create($request->validate([
            'buyer_id' => ['nullable', 'exists:buyers,id'],
            'classification' => ['required', Rule::in(['FG', 'WIP', 'RM'])],
            'code' => ['required', 'string', 'max:60', 'unique:parts,code'],
            'name' => ['required', 'string', 'max:160'],
            'spec' => ['nullable', 'string', 'max:120'],
            'uom' => ['nullable', 'string', 'max:20'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'depth_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'cbm_per_unit' => ['nullable', 'numeric', 'min:0'],
            'net_weight_pc' => ['nullable', 'numeric', 'min:0'],
            'gross_weight_pc' => ['nullable', 'numeric', 'min:0'],
            'package_box' => ['nullable', 'integer', 'min:0'],
            'item_no' => ['nullable', 'string', 'max:80'],
            'goods_description' => ['nullable', 'string', 'max:200'],
        ]));

        return redirect('/masters/parts')->with('status', 'Part master tersimpan.');
    }

    public function destroyPart(Part $part): RedirectResponse
    {
        $part->delete();

        return redirect('/masters/parts')->with('status', 'Part master terhapus.');
    }

    public function exportParts(): Response
    {
        $headers = [
            'buyer_code',
            'classification',
            'code',
            'name',
            'spec',
            'uom',
            'width_cm',
            'depth_cm',
            'height_cm',
            'cbm_per_unit',
            'net_weight_pc',
            'gross_weight_pc',
            'package_box',
            'item_no',
            'goods_description',
        ];

        $rows = Part::with('buyer')->orderBy('code')->get()->map(fn (Part $part) => [
            $part->buyer?->code,
            $part->classification,
            $part->code,
            $part->name,
            $part->spec,
            $part->uom,
            $part->width_cm,
            $part->depth_cm,
            $part->height_cm,
            $part->cbm_per_unit,
            $part->net_weight_pc,
            $part->gross_weight_pc,
            $part->package_box,
            $part->item_no,
            $part->goods_description,
        ]);

        return $this->xlsxResponse('part_master_'.now()->format('Ymd_His').'.xlsx', 'Part Master', $headers, $rows);
    }

    public function importParts(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $rows = $this->readXlsxUpload($request->file('file')->getRealPath());
        $saved = 0;

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $buyer = null;
            $buyerCode = trim((string) ($row['buyer_code'] ?? ''));
            if ($buyerCode !== '') {
                $buyer = Buyer::where('code', $buyerCode)->orWhere('name', $buyerCode)->first();
            }

            Part::updateOrCreate(['code' => $code], [
                'buyer_id' => $buyer?->id,
                'classification' => strtoupper((string) ($row['classification'] ?? 'FG')) ?: 'FG',
                'name' => $name,
                'spec' => $row['spec'] ?? null,
                'uom' => $row['uom'] ?? null,
                'width_cm' => $this->nullableNumber($row['width_cm'] ?? null),
                'depth_cm' => $this->nullableNumber($row['depth_cm'] ?? null),
                'height_cm' => $this->nullableNumber($row['height_cm'] ?? null),
                'cbm_per_unit' => $this->nullableNumber($row['cbm_per_unit'] ?? null),
                'net_weight_pc' => $this->nullableNumber($row['net_weight_pc'] ?? null),
                'gross_weight_pc' => $this->nullableNumber($row['gross_weight_pc'] ?? null),
                'package_box' => $this->nullableInteger($row['package_box'] ?? null),
                'item_no' => $row['item_no'] ?? null,
                'goods_description' => $row['goods_description'] ?? null,
            ]);

            $saved++;
        }

        return redirect('/masters/parts')->with('status', $saved.' part berhasil diimport.');
    }

    public function storeSize(Request $request): RedirectResponse
    {
        SizeVariant::create($request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:size_variants,code'],
            'name' => ['nullable', 'string', 'max:120'],
        ]));

        return redirect('/masters/sizes')->with('status', 'Size master tersimpan.');
    }

    public function destroySize(SizeVariant $size): RedirectResponse
    {
        $size->delete();

        return redirect('/masters/sizes')->with('status', 'Size master terhapus.');
    }

    public function exportSizes(): Response
    {
        $rows = SizeVariant::orderBy('code')->get()->map(fn (SizeVariant $size) => [
            $size->code,
            $size->name,
        ]);

        return $this->xlsxResponse('size_master_'.now()->format('Ymd_His').'.xlsx', 'Size Master', ['code', 'name'], $rows);
    }

    public function importSizes(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $rows = $this->readXlsxUpload($request->file('file')->getRealPath());
        $saved = 0;

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            SizeVariant::updateOrCreate(['code' => $code], [
                'name' => $row['name'] ?? null,
            ]);

            $saved++;
        }

        return redirect('/masters/sizes')->with('status', $saved.' size berhasil diimport.');
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        BuyerPartSize::create($request->validate([
            'buyer_id' => ['required', 'exists:buyers,id'],
            'part_id' => ['required', 'exists:parts,id'],
            'size_variant_id' => [
                'required',
                'exists:size_variants,id',
                Rule::unique('buyer_part_sizes')->where(fn ($query) => $query
                    ->where('buyer_id', $request->buyer_id)
                    ->where('part_id', $request->part_id)),
            ],
        ]));

        return back()->with('status', 'Mapping buyer-part-size tersimpan.');
    }

    public function storeProductionEntry(Request $request): RedirectResponse
    {
        $process = Process::where('is_input_process', true)->find($request->input('process_id'));
        $requiresPart = $process ? $this->processRequiresPart($process) : false;
        $spk = Spk::find($request->input('spk_id'));

        $request->merge([
            'repairable_qty' => $request->input('repairable_qty', $request->input('ng_qty', 0)),
            'scrap_qty' => $request->input('scrap_qty', 0),
        ]);

        $validated = $request->validate([
            'spk_id' => ['required', 'exists:spks,id'],
            'production_date' => ['required', 'date'],
            'shift' => ['required', Rule::in(array_keys($this->shiftOptions()))],
            'buyer_id' => ['nullable', 'exists:buyers,id'],
            'part_id' => [$requiresPart && ! $spk?->part_id ? 'required' : 'nullable', 'exists:parts,id'],
            'size_variant_id' => [$requiresPart && ! $spk?->size_variant_id ? 'required' : 'nullable', 'exists:size_variants,id'],
            'process_id' => [
                'required',
                Rule::exists('processes', 'id')->where('is_input_process', true),
            ],
            'good_qty' => ['required', 'integer', 'min:0'],
            'repairable_qty' => ['required', 'integer', 'min:0'],
            'scrap_qty' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $validated['ng_qty'] = $validated['repairable_qty'] + $validated['scrap_qty'];

        if (($validated['good_qty'] + $validated['ng_qty']) <= 0) {
            return back()
                ->withErrors(['good_qty' => 'Total produksi (Good + NG) harus lebih dari 0.'])
                ->withInput();
        }

        if (! $requiresPart) {
            $validated['part_id'] = null;
        }

        if ($spk) {
            $validated['buyer_id'] = $spk->buyer_id;
            $validated['part_id'] = $requiresPart ? ($validated['part_id'] ?? $spk->part_id) : null;
            $validated['size_variant_id'] = $requiresPart ? ($validated['size_variant_id'] ?? $spk->size_variant_id) : null;
        }

        $entry = ProductionEntry::create($validated);
        $this->syncSpkStatus($entry->spk);

        $redirectPath = $requiresPart ? '/input-hasil' : '/input-proses';

        return redirect($redirectPath.'?production_date='.$validated['production_date'].'&shift='.$validated['shift'])
            ->with('status', 'Input produksi tersimpan.');
    }

    public function fgReportPage(Request $request)
    {
        if ($request->query('export') === 'xlsx') {
            return $this->exportFgXlsx($request);
        }

        $date  = $request->query('production_date', now()->toDateString());
        $shift = $request->query('shift', '1');
        $spkId = $request->query('spk_id');

        return view('production.report', [
            'date'         => $date,
            'shift'        => $shift,
            'spkId'        => $spkId,
            'spks'         => Spk::with('buyer')->latest()->get(),
            'shiftOptions' => $this->shiftOptions(),
            'fgReport'     => $this->fgReport($date, $shift, $spkId),
        ]);
    }

    public function fgReportPrint(Request $request)
    {
        $date  = $request->query('production_date', now()->toDateString());
        $shift = $request->query('shift', '1');
        $spkId = $request->query('spk_id');

        return view('production.report-print', [
            'date'         => $date,
            'shift'        => $shift,
            'spkId'        => $spkId,
            'selectedSpk'  => $spkId ? Spk::with('buyer')->find($spkId) : null,
            'shiftOptions' => $this->shiftOptions(),
            'fgReport'     => $this->fgReport($date, $shift, $spkId),
        ]);
    }

    private function exportFgXlsx(Request $request)
    {
        $date  = $request->query('production_date', now()->toDateString());
        $shift = $request->query('shift', '1');
        $report = $this->fgReport($date, $shift, $request->query('spk_id'));

        $filename = 'fg_report_' . $date . '_shift' . $shift . '.xlsx';

        $rows = [];

        foreach ($report['groups'] as $buyer => $items) {
            foreach ($items as $item) {
                $rows[] = [$buyer, $item->size_code, (int)$item->good_qty];
            }
            $rows[] = [$buyer . ' SUBTOTAL', '', $items->sum('good_qty')];
            $rows[] = [];
        }

        $rows[] = ['GRAND TOTAL', '', $report['total']];

        return $this->xlsxResponse($filename, 'Report FG', ['Buyer', 'Size Code', 'Good Qty (pcs)'], $rows);
    }

    public function apiMasters()
    {
        return response()->json([
            'buyers' => Buyer::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'parts' => Part::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'sizes' => SizeVariant::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'processes' => Process::where('is_input_process', true)->orderBy('sort_order')->get(['id', 'name']),
            'mappings' => BuyerPartSize::where('is_active', true)->get(['buyer_id', 'part_id', 'size_variant_id']),
        ]);
    }

    public function apiProductionEntries(Request $request)
    {
        $process = Process::where('is_input_process', true)->find($request->input('process_id'));
        $requiresPart = $process ? $this->processRequiresPart($process) : false;
        $spk = Spk::find($request->input('spk_id'));

        $request->merge([
            'repairable_qty' => $request->input('repairable_qty', $request->input('ng_qty', 0)),
            'scrap_qty' => $request->input('scrap_qty', 0),
        ]);

        $validated = $request->validate([
            'spk_id' => ['required', 'exists:spks,id'],
            'production_date' => ['required', 'date'],
            'shift' => ['required', Rule::in(array_keys($this->shiftOptions()))],
            'buyer_id' => ['nullable', 'exists:buyers,id'],
            'part_id' => [$requiresPart && ! $spk?->part_id ? 'required' : 'nullable', 'exists:parts,id'],
            'size_variant_id' => [$requiresPart && ! $spk?->size_variant_id ? 'required' : 'nullable', 'exists:size_variants,id'],
            'process_id' => [
                'required',
                Rule::exists('processes', 'id')->where('is_input_process', true),
            ],
            'good_qty' => ['required', 'integer', 'min:0'],
            'repairable_qty' => ['required', 'integer', 'min:0'],
            'scrap_qty' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $validated['ng_qty'] = $validated['repairable_qty'] + $validated['scrap_qty'];

        if (($validated['good_qty'] + $validated['ng_qty']) <= 0) {
            return response()->json(['message' => 'Total produksi (Good + NG) harus lebih dari 0.'], 422);
        }

        if (! $requiresPart) {
            $validated['part_id'] = null;
        }

        if ($spk) {
            $validated['buyer_id'] = $spk->buyer_id;
            $validated['part_id'] = $requiresPart ? ($validated['part_id'] ?? $spk->part_id) : null;
            $validated['size_variant_id'] = $requiresPart ? ($validated['size_variant_id'] ?? $spk->size_variant_id) : null;
        }

        $entry = ProductionEntry::create($validated);
        $this->syncSpkStatus($entry->spk);

        return response()->json($entry->load(['spk', 'buyer', 'part', 'sizeVariant', 'process']), 201);
    }

    public function apiFgReport(Request $request)
    {
        $date = $request->query('production_date', now()->toDateString());
        $shift = $request->query('shift', '1');

        return response()->json($this->fgReport($date, $shift, $request->query('spk_id')));
    }

    private function processSummaries(string $date, string $shift)
    {
        return Process::where('is_input_process', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Process $process) use ($date, $shift) {
                $totals = ProductionEntry::where('process_id', $process->id)
                    ->whereDate('production_date', $date)
                    ->where('shift', $shift)
                    ->selectRaw('COALESCE(SUM(good_qty), 0) as good_qty, COALESCE(SUM(ng_qty), 0) as ng_qty')
                    ->first();

                return [
                    'process' => $process,
                    'good_qty' => (int) $totals->good_qty,
                    'ng_qty' => (int) $totals->ng_qty,
                    'total_qty' => (int) $totals->good_qty + (int) $totals->ng_qty,
                ];
            });
    }

    private function fgReport(string $date, string $shift, ?string $spkId = null): array
    {
        $packing = Process::where('is_fg_process', true)
            ->orWhere('name', 'Packing')
            ->orderByDesc('is_fg_process')
            ->first();
        if (! $packing) {
            return ['groups' => collect(), 'total' => 0, 'chat' => 'total = 0pcs'];
        }

        $rows = ProductionEntry::query()
            ->join('buyers', 'buyers.id', '=', 'production_entries.buyer_id')
            ->join('size_variants', 'size_variants.id', '=', 'production_entries.size_variant_id')
            ->where('production_entries.process_id', $packing->id)
            ->whereDate('production_entries.production_date', $date)
            ->where('production_entries.shift', $shift)
            ->when($spkId, fn ($query) => $query->where('production_entries.spk_id', $spkId))
            ->groupBy('buyers.name', 'size_variants.code')
            ->orderBy(DB::raw('MIN(production_entries.id)'))
            ->get([
                'buyers.name as buyer_name',
                'size_variants.code as size_code',
                DB::raw('SUM(production_entries.good_qty) as good_qty'),
            ]);

        $groups = $rows->groupBy('buyer_name');
        $total = (int) $rows->sum('good_qty');

        return [
            'groups' => $groups,
            'total' => $total,
            'chat' => $this->formatChatReport($date, $shift, $groups, $total),
        ];
    }

    private function formatChatReport(string $date, string $shift, $groups, int $total): string
    {
        $shiftInfo = $this->shiftOptions()[$shift] ?? $this->shiftOptions()['1'];
        $lines = [
            $shiftInfo['greeting'],
            'hasil packing shift '.$shift,
            $shiftInfo['time'],
            date('d-m-Y', strtotime($date)),
            '',
        ];

        foreach ($groups as $buyer => $items) {
            $lines[] = $buyer;
            foreach ($items as $item) {
                $lines[] = $item->size_code.' = '.(int) $item->good_qty;
            }
            $lines[] = '';
        }

        $lines[] = 'total = '.$total.'pcs';
        $lines[] = 'terimakasi';

        return trim(implode("\n", $lines));
    }

    private function normalizeSheetHeader(?string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

        return strtolower(trim(str_replace([' ', '-', '/', '.'], '_', $value)));
    }

    private function xlsxResponse(string $filename, string $sheetName, array $headers, $rows): Response
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $data = [array_values($headers)];
        foreach ($rows as $row) {
            $data[] = array_values($row);
        }

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
  <sheets><sheet name="'.$this->xmlEscape($sheetName).'" sheetId="1" r:id="rId1"/></sheets>
</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');

        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($data as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $sheet .= '<row r="'.$excelRow.'">';
            foreach ($row as $colIndex => $value) {
                $cell = $this->xlsxColumnName($colIndex + 1).$excelRow;
                $sheet .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->xmlEscape((string) $value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function readXlsxUpload(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            foreach ($shared->si ?? [] as $si) {
                $sharedStrings[] = (string) ($si->t ?? $si->r->t ?? '');
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $rawRows = [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c ?? [] as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $col = $this->xlsxColumnIndex(preg_replace('/\d+/', '', $ref));
                $type = (string) ($cell['t'] ?? '');

                if ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } elseif ($type === 's') {
                    $value = $sharedStrings[(int) ($cell->v ?? 0)] ?? '';
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $values[$col - 1] = $value;
            }

            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) > 0) {
                ksort($values);
                $rawRows[] = $values;
            }
        }

        if ($rawRows === []) {
            return [];
        }

        $header = array_map(fn ($value) => $this->normalizeSheetHeader($value), array_values($rawRows[0]));
        $rows = [];

        foreach (array_slice($rawRows, 1) as $line) {
            $line = array_slice(array_pad(array_values($line), count($header), null), 0, count($header));
            $rows[] = array_combine($header, $line);
        }

        return $rows;
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

    private function xlsxColumnIndex(string $name): int
    {
        $index = 0;
        foreach (str_split($name) as $char) {
            $index = $index * 26 + ord(strtoupper($char)) - 64;
        }

        return max(1, $index);
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function nullableNumber($value): ?float
    {
        $value = str_replace(',', '.', trim((string) $value));

        return $value === '' ? null : (float) $value;
    }

    private function nullableInteger($value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function processRequiresPart(Process $process): bool
    {
        return $process->is_fg_process || strcasecmp($process->name, 'Packing') === 0;
    }

    private function syncSpkStatus(?Spk $spk): void
    {
        if (! $spk) {
            return;
        }

        $packing = Process::where('is_fg_process', true)
            ->orWhere('name', 'Packing')
            ->orderByDesc('is_fg_process')
            ->first();

        $packingGood = $packing
            ? ProductionEntry::where('spk_id', $spk->id)->where('process_id', $packing->id)->sum('good_qty')
            : 0;

        if ($packingGood >= $spk->target_qty) {
            $spk->update(['status' => 'Completed']);
            return;
        }

        if (in_array($spk->status, ['Pending', 'Material Prepared'], true)) {
            $spk->update(['status' => 'In Production']);
        }
    }

    private function shiftOptions(): array
    {
        return [
            '1' => ['label' => 'Shift 1', 'time' => '08:00-16:00', 'greeting' => 'Selamat sore'],
            '2' => ['label' => 'Shift 2', 'time' => '16:00-24:00', 'greeting' => 'Selamat malam'],
            '3' => ['label' => 'Shift 3', 'time' => '00:00-08:00', 'greeting' => 'Selamat pagi'],
        ];
    }
}
