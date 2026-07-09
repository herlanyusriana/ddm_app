<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerPartSize;
use App\Models\BindingRejectStock;
use App\Models\Operator;
use App\Models\Part;
use App\Models\Process;
use App\Models\ProductionEntry;
use App\Models\ProductionTrouble;
use App\Models\ReworkResult;
use App\Models\SizeVariant;
use App\Models\Spk;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

    public function productionHistory(Request $request): View
    {
        $type = $request->query('input_type') === 'hasil' ? 'hasil' : 'proses';
        $data = $this->productionPageData($request, $type);
        $data['pageTitle'] = 'History Produksi';
        $data['pageSubtitle'] = 'History input, trouble, koreksi, dan export Excel';
        $data['historyView'] = in_array($request->query('view'), ['input', 'trouble', 'correction'], true)
            ? $request->query('view')
            : 'input';

        return view('production.history', $data);
    }

    private function renderInputPage(Request $request, string $type): View
    {
        return view('production.index', $this->productionPageData($request, $type));
    }

    private function productionPageData(Request $request, string $type): array
    {
        $window = $this->productionWindow($request);
        $date = $window['date'];
        $shift = $window['shift'];

        $inputProcesses = Process::where('is_input_process', true)->orderBy('sort_order')->get();
        if ($type === 'proses') {
            $inputProcesses = $inputProcesses->reject(fn ($p) => $this->processRequiresPart($p));
            $title = 'Input Proses (WIP)';
        } else {
            $inputProcesses = $inputProcesses->filter(fn ($p) => $this->processRequiresPart($p));
            $title = 'Input Hasil (FG/Packing)';
        }

        $selectedProcess = $type === 'proses'
            ? $inputProcesses->firstWhere('id', (int) $request->query('process_id')) ?? $inputProcesses->first()
            : null;

        $spks = Spk::with(['buyer', 'part', 'sizeVariant'])
            ->whereIn('status', ['Pending', 'Material Prepared', 'In Production'])
            ->latest()
            ->get();
        $spkProcessTotals = ProductionEntry::whereIn('spk_id', $spks->pluck('id'))
            ->whereIn('process_id', $inputProcesses->pluck('id'))
            ->groupBy('spk_id', 'process_id')
            ->selectRaw('spk_id, process_id, COALESCE(SUM(good_qty + ng_qty), 0) as total_qty')
            ->get()
            ->groupBy('spk_id')
            ->mapWithKeys(fn ($rows, $spkId) => [
                $spkId => $rows->mapWithKeys(fn ($row) => [(string) $row->process_id => (int) $row->total_qty])->toArray(),
            ])
            ->toArray();
        $historyProcess = $selectedProcess ?? $inputProcesses->first();
        $historyPeriod = $request->query('history_period') === 'monthly' ? 'monthly' : 'daily';
        $productionMonth = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('production_month'))
            ? (string) $request->query('production_month')
            : substr($date, 0, 7);
        $hourlyReport = $historyProcess
            ? $this->productionHourlyReport($date, $shift, $historyProcess, $historyPeriod, $productionMonth)
            : ['process' => null, 'headers' => [], 'rows' => collect(), 'totals_row' => null, 'record_count' => 0];
        $troubles = ProductionTrouble::with(['spk', 'operator', 'process'])
            ->when($historyProcess, fn ($query) => $query->where('process_id', $historyProcess->id))
            ->where('shift', $shift)
            ->when(
                $historyPeriod === 'monthly',
                function ($query) use ($productionMonth) {
                    $monthStart = CarbonImmutable::createFromFormat('Y-m', $productionMonth)->startOfMonth();
                    $query->whereBetween('production_date', [$monthStart->toDateString(), $monthStart->endOfMonth()->toDateString()]);
                },
                fn ($query) => $query->whereDate('production_date', $date),
            )
            ->orderByDesc('production_date')
            ->orderByDesc('start_time')
            ->get();
        $correctionEntries = ProductionEntry::with(['spk', 'operator', 'buyer', 'sizeVariant', 'process'])
            ->when($historyProcess, fn ($query) => $query->where('process_id', $historyProcess->id))
            ->where('shift', $shift)
            ->when(
                $historyPeriod === 'monthly',
                function ($query) use ($productionMonth) {
                    $monthStart = CarbonImmutable::createFromFormat('Y-m', $productionMonth)->startOfMonth();
                    $query->whereBetween('production_date', [$monthStart->toDateString(), $monthStart->endOfMonth()->toDateString()]);
                },
                fn ($query) => $query->whereDate('production_date', $date),
            )
            ->latest()
            ->get();

        return [
            'pageType' => $type,
            'pageTitle' => $title,
            'pageSubtitle' => 'Input Good dan Reject per proses',
            'date' => $date,
            'shift' => $shift,
            'shiftOptions' => $this->shiftOptions(),
            'isManualWindow' => $window['manual'],
            'buyers' => Buyer::where('is_active', true)->orderBy('name')->get(),
            'operators' => Operator::orderBy('operator_code')->get(),
            'parts' => Part::with('buyer')
                ->when($type === 'hasil', fn ($query) => $query->where('classification', 'FG'))
                ->orderBy('code')
                ->get(),
            'sizes' => SizeVariant::where('is_active', true)->orderBy('production_code')->orderBy('code')->get(),
            'spks' => $spks,
            'inputProcesses' => $inputProcesses,
            'selectedProcess' => $selectedProcess,
            'spkProcessTotals' => $spkProcessTotals,
            'hourlyReport' => $hourlyReport,
            'historyPeriod' => $historyPeriod,
            'productionMonth' => $productionMonth,
            'troubles' => $troubles,
            'correctionEntries' => $correctionEntries,
            'rejectReasons' => $this->rejectReasonOptions(),
        ];
    }

    public function masters(?string $section = null): View
    {
        $section ??= 'buyers';

        return view('production.masters', [
            'section' => $section,
            'buyers' => Buyer::orderBy('name')->get(),
            'operators' => Operator::orderBy('operator_code')->get(),
            'parts' => Part::with('buyer')->orderBy('code')->get(),
            'sizes' => SizeVariant::orderBy('code')->get(),
            'processes' => Process::orderBy('sort_order')->get(),
            'mappings' => BuyerPartSize::with(['buyer', 'part', 'sizeVariant'])->latest()->limit(20)->get(),
        ]);
    }

    public function dashboard(Request $request): View
    {
        $window = $this->productionWindow($request);
        $date = $window['date'];
        $shift = $window['shift'];

        return view('production.dashboard', [
            'date' => $date,
            'shift' => $shift,
            'isManualWindow' => $window['manual'],
            'shiftOptions' => $this->shiftOptions(),
            'summaries' => $this->processSummaries($date, $shift),
        ]);
    }

    public function dashboardSummary(Request $request): JsonResponse
    {
        $window = $this->productionWindow($request);
        $summaries = $this->processSummaries($window['date'], $window['shift']);
        $goodQty = (int) $summaries->sum('good_qty');
        $rejectQty = (int) $summaries->sum('ng_qty');

        return response()->json([
            'date' => $window['date'],
            'shift' => $window['shift'],
            'totals' => [
                'total_qty' => $goodQty + $rejectQty,
                'good_qty' => $goodQty,
                'reject_qty' => $rejectQty,
                'active_processes' => $summaries->where('total_qty', '>', 0)->count(),
                'process_count' => $summaries->count(),
            ],
            'processes' => $summaries->map(function (array $summary): array {
                $totalQty = max(0, (int) $summary['total_qty']);
                $goodQty = max(0, (int) $summary['good_qty']);

                return [
                    'id' => $summary['process']->id,
                    'name' => $summary['process']->name,
                    'good_qty' => $goodQty,
                    'reject_qty' => max(0, (int) $summary['ng_qty']),
                    'total_qty' => $totalQty,
                    'good_rate' => $totalQty > 0 ? (int) round($goodQty / $totalQty * 100) : 0,
                ];
            })->values(),
            'updated_at' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    public function reworkPage(Request $request): View
    {
        $rows = ProductionEntry::query()
            ->with(['spk.buyer', 'process'])
            ->where('repairable_qty', '>', 0)
            ->latest()
            ->get()
            ->groupBy(fn (ProductionEntry $entry) => $entry->spk_id.'-'.$entry->process_id)
            ->map(function ($entries) {
                $first = $entries->first();
                $completed = (int) ReworkResult::whereIn('production_entry_id', $entries->pluck('id'))->sum('qty');

                return [
                    'spk' => $first->spk,
                    'process' => $first->process,
                    'reject_qty' => max(0, (int) $entries->sum('repairable_qty') - $completed),
                    'last_date' => $entries->max('production_date'),
                    'records' => $entries->count(),
                ];
            })
            ->filter(fn ($row) => $row['reject_qty'] > 0)
            ->sortByDesc('reject_qty')
            ->values();

        return view('production.rework', [
            'rows' => $rows,
            'totalReject' => (int) $rows->sum('reject_qty'),
            'totalSpk' => $rows->pluck('spk.id')->filter()->unique()->count(),
        ]);
    }

    public function reworkResultsPage(Request $request): View
    {
        return $this->reworkResultsView($request);
    }

    public function editReworkResult(Request $request, ReworkResult $result): View
    {
        return $this->reworkResultsView($request, $result);
    }

    private function reworkResultsView(Request $request, ?ReworkResult $editResult = null): View
    {
        $date = (string) $request->query('date', now('Asia/Jakarta')->toDateString());
        $productionSources = ProductionEntry::with(['buyer', 'sizeVariant', 'process', 'spk'])
            ->where('repairable_qty', '>', 0)->latest()->get()
            ->map(function (ProductionEntry $entry) use ($editResult) {
                $used = (int) ReworkResult::where('production_entry_id', $entry->id)
                    ->when($editResult, fn ($query) => $query->where('id', '!=', $editResult->id))
                    ->sum('qty');
                $entry->setAttribute('remaining_rework', max(0, (int) $entry->repairable_qty - $used));
                return $entry;
            })->filter(fn ($entry) => $entry->remaining_rework > 0 || $editResult?->production_entry_id === $entry->id);
        $bindingSources = BindingRejectStock::with(['buyer', 'sizeVariant'])
            ->latest('transaction_date')->latest('id')->get()
            ->map(function (BindingRejectStock $stock) use ($editResult) {
                $used = (int) ReworkResult::where('binding_reject_stock_id', $stock->id)
                    ->when($editResult, fn ($query) => $query->where('id', '!=', $editResult->id))
                    ->sum('qty');
                $stock->setAttribute('remaining_rework', max(0, (int) $stock->qty_in - (int) $stock->qty_out - $used));
                return $stock;
            })->filter(fn ($stock) => $stock->remaining_rework > 0 || $editResult?->binding_reject_stock_id === $stock->id);

        return view('production.rework-results', [
            'date' => $date,
            'productionSources' => $productionSources,
            'bindingSources' => $bindingSources,
            'operators' => Operator::orderBy('operator_code')->get(),
            'results' => ReworkResult::with(['productionEntry.buyer', 'productionEntry.sizeVariant', 'bindingRejectStock.buyer', 'bindingRejectStock.sizeVariant', 'operator'])
                ->whereDate('result_date', $date)->latest()->get(),
            'editResult' => $editResult,
        ]);
    }

    public function storeReworkResult(Request $request): RedirectResponse
    {
        $result = ReworkResult::create($this->validatedReworkResult($request));
        return redirect('/rework-results/'.$result->id.'/additional-print?date='.$request->input('result_date'))->with('status', 'Hasil rework tersimpan.');
    }

    public function updateReworkResult(Request $request, ReworkResult $result): RedirectResponse
    {
        $result->update($this->validatedReworkResult($request, $result));
        return redirect('/rework-results?date='.$request->input('result_date'))->with('status', 'Hasil rework diperbarui.');
    }

    private function validatedReworkResult(Request $request, ?ReworkResult $current = null): array
    {
        $validated = $request->validate([
            'production_entry_id' => ['nullable', 'exists:production_entries,id'],
            'binding_reject_stock_id' => ['nullable', 'exists:binding_reject_stocks,id'],
            'result_date' => ['required', 'date'],
            'component' => ['required', Rule::in(['Topper', 'Border', 'Bottom'])],
            'qty' => ['required', 'integer', 'min:1'],
            'operator_id' => ['required', 'exists:operators,id'],
            'reject_notes' => ['required', 'string', 'max:500'],
        ]);
        $productionEntryId = $validated['production_entry_id'] ?? null;
        $bindingRejectStockId = $validated['binding_reject_stock_id'] ?? null;

        if (($productionEntryId && $bindingRejectStockId) || (! $productionEntryId && ! $bindingRejectStockId)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['production_entry_id' => 'Pilih salah satu sumber reject.']);
        }

        if ($productionEntryId) {
            $source = ProductionEntry::findOrFail($productionEntryId);
            $used = (int) ReworkResult::where('production_entry_id', $source->id)
                ->when($current, fn ($query) => $query->where('id', '!=', $current->id))->sum('qty');
            $remaining = max(0, (int) $source->repairable_qty - $used);
            $validated['binding_reject_stock_id'] = null;
        } else {
            $source = BindingRejectStock::findOrFail($bindingRejectStockId);
            $used = (int) ReworkResult::where('binding_reject_stock_id', $source->id)
                ->when($current, fn ($query) => $query->where('id', '!=', $current->id))->sum('qty');
            $remaining = max(0, (int) $source->qty_in - (int) $source->qty_out - $used);
            $validated['production_entry_id'] = null;
        }

        if ((int) $validated['qty'] > $remaining) {
            throw \Illuminate\Validation\ValidationException::withMessages(['qty' => "Qty melebihi sisa hutang rework ({$remaining})."]);
        }
        return $validated;
    }

    public function destroyReworkResult(ReworkResult $result): RedirectResponse
    {
        $result->delete();
        return back()->with('status', 'Hasil rework dihapus dan hutang dikembalikan.');
    }

    public function exportReworkResults(Request $request): Response
    {
        $date = (string) $request->query('date', now('Asia/Jakarta')->toDateString());
        $rows = ReworkResult::with(['productionEntry.buyer', 'productionEntry.sizeVariant', 'bindingRejectStock.buyer', 'bindingRejectStock.sizeVariant', 'operator'])
            ->whereDate('result_date', $date)->get()->map(fn ($result) => [
                $result->productionEntry?->buyer?->code ?? $result->bindingRejectStock?->buyer?->code,
                $result->productionEntry?->sizeVariant?->code ?? $result->bindingRejectStock?->sizeVariant?->code,
                $result->productionEntry ? 'Reject Produksi' : 'Reject Binding',
                $result->component,
                $result->qty,
                $result->operator?->operator_code,
                $result->operator?->name,
                $result->reject_notes,
            ]);
        return $this->xlsxResponse('hasil_rework_'.$date.'.xlsx', 'Hasil Rework', ['Buyer', 'Style', 'Sumber', 'Bagian', 'Qty', 'No Operator', 'Nama Operator', 'Keterangan Reject'], $rows);
    }

    public function printReworkAdditional(Request $request, ReworkResult $result): View
    {
        $result->load(['productionEntry.buyer', 'productionEntry.sizeVariant', 'productionEntry.process', 'bindingRejectStock.buyer', 'bindingRejectStock.sizeVariant', 'operator']);

        return view('production.rework-additional-print', [
            'result' => $result,
            'date' => (string) $request->query('date', $result->result_date?->toDateString() ?? now('Asia/Jakarta')->toDateString()),
        ]);
    }

    public function bindingRejectStockPage(Request $request): View
    {
        return $this->bindingRejectStockView($request);
    }

    public function editBindingRejectStock(Request $request, BindingRejectStock $stock): View
    {
        return $this->bindingRejectStockView($request, $stock);
    }

    private function bindingRejectStockView(Request $request, ?BindingRejectStock $editRecord = null): View
    {
        $date = (string) $request->query('date', now('Asia/Jakarta')->toDateString());
        $ledger = BindingRejectStock::with(['buyer', 'sizeVariant'])
            ->whereDate('transaction_date', '<=', $date)
            ->orderBy('transaction_date')
            ->orderBy('transaction_time')
            ->orderBy('id')
            ->get();
        $balances = [];
        foreach ($ledger as $row) {
            $key = $row->buyer_id.'-'.$row->size_variant_id;
            $balances[$key] = ($balances[$key] ?? 0) + (int) $row->qty_in - (int) $row->qty_out;
            $row->setAttribute('balance', $balances[$key]);
        }

        $summary = $ledger->groupBy('buyer_id')->map(function ($buyerRows) {
            return [
                'buyer' => $buyerRows->first()->buyer,
                'styles' => $buyerRows->groupBy('size_variant_id')->map(function ($styleRows) {
                    return [
                        'size' => $styleRows->first()->sizeVariant,
                        'qty' => (int) $styleRows->sum('qty_in') - (int) $styleRows->sum('qty_out'),
                    ];
                })->values(),
            ];
        })->values();

        return view('production.binding-reject-stock', [
            'date' => $date,
            'summary' => $summary,
            'grandTotal' => (int) $summary->sum(fn ($group) => $group['styles']->sum('qty')),
            'transactions' => $ledger,
            'buyers' => Buyer::where('is_active', true)->orderBy('name')->get(),
            'sizes' => SizeVariant::where('is_active', true)->orderBy('production_code')->orderBy('code')->get(),
            'editRecord' => $editRecord,
        ]);
    }

    public function storeBindingRejectStock(Request $request): RedirectResponse
    {
        BindingRejectStock::create($this->validatedBindingRejectStock($request));

        return redirect('/binding-reject-stock?date='.$request->input('transaction_date'))
            ->with('status', 'Stock reject Binding tersimpan.');
    }

    public function updateBindingRejectStock(Request $request, BindingRejectStock $stock): RedirectResponse
    {
        $stock->update($this->validatedBindingRejectStock($request));

        return redirect('/binding-reject-stock?date='.$request->input('transaction_date'))
            ->with('status', 'Stock reject Binding diperbarui.');
    }

    public function destroyBindingRejectStock(BindingRejectStock $stock): RedirectResponse
    {
        $stock->delete();

        return back()->with('status', 'Transaksi stock reject Binding dihapus.');
    }

    private function validatedBindingRejectStock(Request $request): array
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'pallet' => ['nullable', 'string', 'max:80'],
            'po_no' => ['nullable', 'string', 'max:80'],
            'buyer_id' => ['required', 'exists:buyers,id'],
            'size_variant_id' => ['required', 'exists:size_variants,id'],
            'qty_in' => ['required', 'integer', 'min:0'],
            'qty_out' => ['required', 'integer', 'min:0'],
            'paraf' => ['nullable', 'string', 'max:120'],
        ]);

        if (((int) $validated['qty_in'] + (int) $validated['qty_out']) <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qty_in' => 'IN atau OUT harus lebih dari 0.',
            ]);
        }

        return $validated;
    }

    public function exportBindingRejectStock(Request $request): Response
    {
        $viewData = $this->bindingRejectStockView($request)->getData();
        $rows = [];
        foreach ($viewData['summary'] as $group) {
            foreach ($group['styles'] as $style) {
                $rows[] = [
                    $group['buyer']?->code ?? $group['buyer']?->name,
                    $style['size']?->code,
                    $style['qty'],
                ];
            }
            $rows[] = [($group['buyer']?->code ?? 'BUYER').' TOTAL', '', $group['styles']->sum('qty')];
        }
        $rows[] = ['GRAND TOTAL', '', $viewData['grandTotal']];

        return $this->xlsxResponse(
            'data_reject_binding_'.$viewData['date'].'.xlsx',
            'Reject Binding',
            ['Buyer', 'Style', 'QTY'],
            $rows,
        );
    }

    public function createBuyer(): View
    {
        return view('production.buyer-create');
    }

    public function editMaster(string $type, int $id): View
    {
        $record = match ($type) {
            'buyers' => Buyer::findOrFail($id),
            'operators' => Operator::findOrFail($id),
            'parts' => Part::findOrFail($id),
            'sizes' => SizeVariant::findOrFail($id),
            'processes' => Process::findOrFail($id),
        };

        return view('production.master-edit', [
            'type' => $type,
            'record' => $record,
            'buyers' => $type === 'parts'
                ? Buyer::where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function updateMaster(Request $request, string $type, int $id): RedirectResponse
    {
        if ($type === 'buyers') {
            $record = Buyer::findOrFail($id);
            $record->update($request->validate([
                'code' => ['required', 'string', 'max:40', Rule::unique('buyers', 'code')->ignore($record->id)],
                'name' => ['required', 'string', 'max:120'],
                'is_active' => ['required', 'boolean'],
            ]));
        } elseif ($type === 'operators') {
            $record = Operator::findOrFail($id);
            $record->update($request->validate([
                'operator_code' => ['required', 'string', 'max:40', 'regex:/^\d+$/', Rule::unique('operators', 'operator_code')->ignore($record->id)],
                'name' => ['required', 'string', 'max:120'],
                'qc_label' => ['nullable', 'string', 'max:40', 'regex:/^\d+$/'],
                'leader_name' => ['nullable', 'string', 'max:120'],
                'target_prod' => ['nullable', 'integer', 'min:0'],
            ]));
        } elseif ($type === 'parts') {
            $record = Part::findOrFail($id);
            $record->update($request->validate([
                'buyer_id' => ['nullable', 'exists:buyers,id'],
                'classification' => ['required', Rule::in(['FG', 'WIP', 'RM'])],
                'code' => ['required', 'string', 'max:60', Rule::unique('parts', 'code')->ignore($record->id)],
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
        } elseif ($type === 'sizes') {
            $record = SizeVariant::findOrFail($id);
            $productionCode = strtoupper((string) $request->input('production_code'));
            $request->merge(['production_code' => $productionCode]);
            $record->update($request->validate([
                'production_code' => ['required', Rule::in(['A', 'B'])],
                'code' => [
                    'required', 'string', 'max:40',
                    Rule::unique('size_variants', 'code')
                        ->where('production_code', $productionCode)
                        ->ignore($record->id),
                ],
                'point' => ['required', 'numeric', 'min:0'],
                'is_active' => ['required', 'boolean'],
            ]));
        } else {
            $record = Process::findOrFail($id);
            $record->update($request->validate([
                'name' => ['required', 'string', 'max:120', Rule::unique('processes', 'name')->ignore($record->id)],
                'sort_order' => ['required', 'integer', 'min:0'],
                'is_input_process' => ['required', 'boolean'],
                'is_fg_process' => ['required', 'boolean'],
            ]));
        }

        return redirect('/masters/'.$type)->with('status', 'Master data berhasil diperbarui.');
    }

    public function createOperator(): View
    {
        return view('production.operator-create');
    }

    public function importOperatorsForm(): View
    {
        return view('production.operator-import');
    }

    public function createPart(): View
    {
        return view('production.part-create', ['buyers' => Buyer::where('is_active', true)->orderBy('name')->get()]);
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
        $isReferenced = Part::where('buyer_id', $buyer->id)->exists()
            || Spk::where('buyer_id', $buyer->id)->exists()
            || ProductionEntry::where('buyer_id', $buyer->id)->exists();

        if ($isReferenced) {
            $buyer->update(['is_active' => false]);

            return redirect('/masters/buyers')->with('status', 'Buyer sudah dipakai dan berhasil diarsipkan.');
        }

        $buyer->delete();

        return redirect('/masters/buyers')->with('status', 'Buyer master terhapus.');
    }

    public function storeOperator(Request $request): RedirectResponse
    {
        Operator::create($request->validate([
            'operator_code' => ['required', 'string', 'max:40', 'regex:/^\d+$/', 'unique:operators,operator_code'],
            'name' => ['required', 'string', 'max:120'],
            'qc_label' => ['nullable', 'string', 'max:40', 'regex:/^\d+$/'],
            'leader_name' => ['nullable', 'string', 'max:120'],
            'target_prod' => ['nullable', 'integer', 'min:0'],
        ]));

        return redirect('/masters/operators')->with('status', 'Operator master tersimpan.');
    }

    public function destroyOperator(Operator $operator): RedirectResponse
    {
        $operator->delete();

        return redirect('/masters/operators')->with('status', 'Operator master terhapus.');
    }

    public function exportOperators(): Response
    {
        $rows = Operator::orderBy('operator_code')->get()->map(fn (Operator $operator) => [
            $operator->operator_code,
            $operator->name,
            $operator->qc_label,
            $operator->leader_name,
            $operator->target_prod,
        ]);

        return $this->xlsxResponse(
            'operator_master_'.now()->format('Ymd_His').'.xlsx',
            'Operator Master',
            ['No', 'Nama', 'QC LABEL', 'Group', 'Target Prod'],
            $rows
        );
    }

    public function importOperators(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $rows = $this->readXlsxUpload($request->file('file')->getRealPath());
        $saved = 0;

        foreach ($rows as $row) {
            $operatorCode = trim((string) ($row['no'] ?? ''));
            $name = trim((string) ($row['nama'] ?? ''));
            $qcLabel = trim((string) ($row['qc_label'] ?? ''));
            $leaderName = trim((string) ($row['group'] ?? ''));
            $targetProd = trim((string) ($row['target_prod'] ?? ''));

            if (
                $operatorCode === ''
                || $name === ''
                || ! ctype_digit($operatorCode)
                || ($qcLabel !== '' && ! ctype_digit($qcLabel))
                || ($targetProd !== '' && ! ctype_digit($targetProd))
            ) {
                continue;
            }

            Operator::updateOrCreate(['operator_code' => $operatorCode], [
                'name' => $name,
                'qc_label' => $qcLabel !== '' ? $qcLabel : null,
                'leader_name' => $leaderName !== '' ? $leaderName : null,
                'target_prod' => $targetProd !== '' ? (int) $targetProd : null,
            ]);

            $saved++;
        }

        return redirect('/masters/operators')->with('status', $saved.' operator berhasil diimport.');
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
        $productionCode = strtoupper((string) $request->input('production_code'));
        $request->merge(['production_code' => $productionCode]);
        SizeVariant::create($request->validate([
            'production_code' => ['required', Rule::in(['A', 'B'])],
            'code' => [
                'required', 'string', 'max:40',
                Rule::unique('size_variants', 'code')->where('production_code', $productionCode),
            ],
            'point' => ['required', 'numeric', 'min:0'],
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
        $rows = SizeVariant::orderBy('production_code')->orderBy('code')->get()->map(fn (SizeVariant $size) => [
            $size->production_code,
            $size->code,
            $size->point,
        ]);

        return $this->xlsxResponse('size_master_'.now()->format('Ymd_His').'.xlsx', 'Size Master', ['Code', 'Type', 'Point'], $rows);
    }

    public function importSizes(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $rows = $this->readXlsxUpload($request->file('file')->getRealPath());
        $saved = 0;

        foreach ($rows as $row) {
            $productionCode = strtoupper(trim((string) ($row['code'] ?? '')));
            $type = strtoupper(trim((string) ($row['type'] ?? '')));
            $point = $this->nullableNumber($row['point'] ?? null);

            if (! in_array($productionCode, ['A', 'B'], true) || $type === '' || $point === null || $point < 0) {
                continue;
            }

            SizeVariant::updateOrCreate([
                'production_code' => $productionCode,
                'code' => $type,
            ], [
                'point' => $point,
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
        $automaticWindow = $request->boolean('automatic_window');
        if ($automaticWindow) {
            $window = $this->productionWindow(new Request);
            $request->merge([
                'production_date' => $window['date'],
                'shift' => $window['shift'],
            ]);
        }

        if ($request->input('record_mode', 'production') === 'trouble') {
            return $this->storeProductionTrouble($request, $automaticWindow);
        }

        $process = Process::where('is_input_process', true)->find($request->input('process_id'));
        $requiresPart = $process ? $this->processRequiresPart($process) : false;
        $requiresOperator = $process && strcasecmp($process->name, 'Binding') === 0;
        $spk = Spk::find($request->input('spk_id'));
        $isCustomEntry = ! $spk;

        $request->merge([
            'repairable_qty' => $request->input('reject_qty', $request->input('repairable_qty', $request->input('ng_qty', 0))),
            'scrap_qty' => 0,
        ]);

        $validated = $request->validate([
            'spk_id' => ['nullable', 'exists:spks,id'],
            'production_date' => ['required', 'date'],
            'shift' => ['required', Rule::in(array_keys($this->shiftOptions()))],
            'buyer_id' => [$isCustomEntry ? 'required' : 'nullable', 'exists:buyers,id'],
            'part_id' => [
                ! $isCustomEntry && $requiresPart && ! $spk?->part_id ? 'required' : 'nullable',
                'exists:parts,id',
            ],
            'size_variant_id' => [
                $isCustomEntry || ($requiresPart && ! $spk?->size_variant_id) ? 'required' : 'nullable',
                'exists:size_variants,id',
            ],
            'operator_id' => [$requiresOperator ? 'required' : 'nullable', 'exists:operators,id'],
            'process_id' => [
                'required',
                Rule::exists('processes', 'id')->where('is_input_process', true),
            ],
            'good_qty' => ['required', 'integer', 'min:0'],
            'repairable_qty' => ['required', 'integer', 'min:0'],
            'scrap_qty' => ['required', 'integer', 'min:0'],
            'reject_reason' => ['nullable', Rule::in($this->rejectReasonOptions())],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $validated['ng_qty'] = $validated['repairable_qty'] + $validated['scrap_qty'];
        $validated['reject_reason'] = $validated['ng_qty'] > 0 ? ($validated['reject_reason'] ?? null) : null;

        if (($validated['good_qty'] + $validated['ng_qty']) <= 0) {
            return back()
                ->withErrors(['good_qty' => 'Total produksi (Good + Reject) harus lebih dari 0.'])
                ->withInput();
        }

        if ($validated['ng_qty'] > 0 && ! $validated['reject_reason']) {
            return back()
                ->withErrors(['reject_reason' => 'Alasan reject wajib dipilih jika Reject lebih dari 0.'])
                ->withInput();
        }

        if ($spk && $process) {
            $overflowMessage = $this->spkProcessCapacityError($spk, $process, $validated['good_qty'] + $validated['ng_qty']);
            if ($overflowMessage) {
                return back()
                    ->withErrors(['good_qty' => $overflowMessage])
                    ->withInput();
            }
        }

        if (! $requiresPart && ! $isCustomEntry) {
            $validated['part_id'] = null;
        }

        if ($isCustomEntry) {
            $validated['part_id'] = null;
        }

        if (! $requiresOperator) {
            $validated['operator_id'] = null;
        }

        if ($spk) {
            if ($requiresPart && $spk->part_id && isset($validated['part_id']) && (int) $validated['part_id'] !== (int) $spk->part_id) {
                return back()
                    ->withErrors(['part_id' => 'Part harus sesuai dengan part di SPK.'])
                    ->withInput();
            }

            if ($requiresPart && isset($validated['part_id']) && ! $this->partMatchesBuyer((int) $validated['part_id'], (int) $spk->buyer_id)) {
                return back()
                    ->withErrors(['part_id' => 'Part tidak sesuai dengan buyer SPK.'])
                    ->withInput();
            }

            $validated['buyer_id'] = $spk->buyer_id;
            $validated['part_id'] = $requiresPart ? ($validated['part_id'] ?? $spk->part_id) : null;
            $validated['size_variant_id'] = $requiresPart ? ($validated['size_variant_id'] ?? $spk->size_variant_id) : null;
        }

        $entry = ProductionEntry::create($validated);
        $this->syncSpkStatus($entry->spk);

        $redirectPath = $requiresPart ? '/input-hasil' : '/input-proses';
        $redirectQuery = ['process_id' => $validated['process_id']];

        if (! $automaticWindow) {
            $redirectQuery['production_date'] = $validated['production_date'];
            $redirectQuery['shift'] = $validated['shift'];
        }

        return redirect($redirectPath.'?'.http_build_query($redirectQuery))
            ->with('status', 'Input produksi tersimpan.');
    }

    private function storeProductionTrouble(Request $request, bool $automaticWindow): RedirectResponse
    {
        $process = Process::where('is_input_process', true)->find($request->input('process_id'));
        $requiresOperator = $process && strcasecmp($process->name, 'Binding') === 0;

        $validated = $request->validate([
            'spk_id' => ['nullable', 'exists:spks,id'],
            'operator_id' => [$requiresOperator ? 'required' : 'nullable', 'exists:operators,id'],
            'production_date' => ['required', 'date'],
            'shift' => ['required', Rule::in(array_keys($this->shiftOptions()))],
            'process_id' => [
                'required',
                Rule::exists('processes', 'id')->where('is_input_process', true),
            ],
            'trouble_type' => ['required', Rule::in(['Mesin', 'Material', 'Quality', 'Lainnya'])],
            'trouble_start_time' => ['required', 'date_format:H:i'],
            'trouble_end_time' => ['required', 'date_format:H:i', 'different:trouble_start_time'],
            'trouble_notes' => ['required', 'string', 'max:500'],
        ]);

        ProductionTrouble::create([
            'spk_id' => $validated['spk_id'] ?? null,
            'operator_id' => $requiresOperator ? ($validated['operator_id'] ?? null) : null,
            'production_date' => $validated['production_date'],
            'shift' => $validated['shift'],
            'process_id' => $validated['process_id'],
            'trouble_type' => $validated['trouble_type'],
            'start_time' => $validated['trouble_start_time'],
            'end_time' => $validated['trouble_end_time'],
            'notes' => $validated['trouble_notes'],
        ]);

        $redirectPath = $this->processRequiresPart($process) ? '/input-hasil' : '/input-proses';
        $redirectQuery = ['process_id' => $validated['process_id']];
        if (! $automaticWindow) {
            $redirectQuery['production_date'] = $validated['production_date'];
            $redirectQuery['shift'] = $validated['shift'];
        }

        return redirect($redirectPath.'?'.http_build_query($redirectQuery))
            ->with('status', 'Trouble produksi tersimpan.');
    }

    public function editProductionEntry(ProductionEntry $entry): View
    {
        return view('production.entry-edit', [
            'entry' => $entry->load(['spk', 'operator', 'buyer', 'sizeVariant', 'process']),
            'buyers' => Buyer::where('is_active', true)->orderBy('name')->get(),
            'sizes' => SizeVariant::where('is_active', true)->orderBy('production_code')->orderBy('code')->get(),
            'operators' => Operator::orderBy('operator_code')->get(),
            'rejectReasons' => $this->rejectReasonOptions(),
        ]);
    }

    public function updateProductionEntry(Request $request, ProductionEntry $entry): RedirectResponse
    {
        $requiresOperator = strcasecmp($entry->process?->name ?? '', 'Binding') === 0;
        $validated = $request->validate([
            'buyer_id' => ['required', 'exists:buyers,id'],
            'size_variant_id' => ['required', 'exists:size_variants,id'],
            'operator_id' => [$requiresOperator ? 'required' : 'nullable', 'exists:operators,id'],
            'good_qty' => ['required', 'integer', 'min:0'],
            'reject_qty' => ['required', 'integer', 'min:0'],
            'reject_reason' => ['nullable', Rule::in($this->rejectReasonOptions())],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        if (($validated['good_qty'] + $validated['reject_qty']) <= 0) {
            return back()->withErrors(['good_qty' => 'Total produksi harus lebih dari 0.'])->withInput();
        }

        if ((int) $validated['reject_qty'] > 0 && empty($validated['reject_reason'])) {
            return back()->withErrors(['reject_reason' => 'Alasan reject wajib dipilih jika Reject lebih dari 0.'])->withInput();
        }

        $entry->update([
            'buyer_id' => $validated['buyer_id'],
            'size_variant_id' => $validated['size_variant_id'],
            'operator_id' => $requiresOperator ? ($validated['operator_id'] ?? null) : null,
            'good_qty' => $validated['good_qty'],
            'repairable_qty' => $validated['reject_qty'],
            'scrap_qty' => 0,
            'ng_qty' => $validated['reject_qty'],
            'reject_reason' => (int) $validated['reject_qty'] > 0 ? ($validated['reject_reason'] ?? null) : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect('/input-proses?'.http_build_query([
            'process_id' => $entry->process_id,
            'production_date' => $entry->production_date->toDateString(),
            'shift' => $entry->shift,
        ]))->with('status', 'Input produksi berhasil diperbarui.');
    }

    public function destroyProductionEntry(ProductionEntry $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('status', 'Input produksi berhasil dihapus.');
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

    public function productionHourlyExport(Request $request): Response
    {
        $validated = $request->validate([
            'production_date' => ['required', 'date'],
            'shift' => ['required', Rule::in(array_keys($this->shiftOptions()))],
            'process_id' => [
                'required',
                Rule::exists('processes', 'id')->where('is_input_process', true),
            ],
            'history_period' => ['nullable', Rule::in(['daily', 'monthly'])],
            'production_month' => ['nullable', 'date_format:Y-m'],
        ]);

        $process = Process::findOrFail($validated['process_id']);
        $historyPeriod = $validated['history_period'] ?? 'daily';
        $productionMonth = $validated['production_month'] ?? substr($validated['production_date'], 0, 7);
        $report = $this->productionHourlyReport(
            $validated['production_date'],
            $validated['shift'],
            $process,
            $historyPeriod,
            $productionMonth,
        );

        $periodLabel = $historyPeriod === 'monthly' ? $productionMonth : $validated['production_date'];
        $filename = 'history_'.strtolower(preg_replace('/[^a-z0-9]+/i', '_', $process->name))
            .'_'.$periodLabel.'_shift_'.$validated['shift'].'.xlsx';

        $exportRows = collect($report['rows']);
        if ($report['totals_row']) {
            $exportRows->push($report['totals_row']);
        }

        return $this->xlsxResponse($filename, substr($process->name, 0, 31), $report['headers'], $exportRows);
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
            'sizes' => SizeVariant::where('is_active', true)
                ->orderBy('production_code')
                ->orderBy('code')
                ->get(['id', 'production_code', 'code', 'name', 'point']),
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
            'repairable_qty' => $request->input('reject_qty', $request->input('repairable_qty', $request->input('ng_qty', 0))),
            'scrap_qty' => 0,
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
            'reject_reason' => ['nullable', Rule::in($this->rejectReasonOptions())],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $validated['ng_qty'] = $validated['repairable_qty'] + $validated['scrap_qty'];
        $validated['reject_reason'] = $validated['ng_qty'] > 0 ? ($validated['reject_reason'] ?? null) : null;

        if (($validated['good_qty'] + $validated['ng_qty']) <= 0) {
            return response()->json(['message' => 'Total produksi (Good + Reject) harus lebih dari 0.'], 422);
        }

        if ($validated['ng_qty'] > 0 && ! $validated['reject_reason']) {
            return response()->json(['message' => 'Alasan reject wajib dipilih jika Reject lebih dari 0.', 'errors' => ['reject_reason' => ['Alasan reject wajib dipilih jika Reject lebih dari 0.']]], 422);
        }

        if ($spk && $process) {
            $overflowMessage = $this->spkProcessCapacityError($spk, $process, $validated['good_qty'] + $validated['ng_qty']);
            if ($overflowMessage) {
                return response()->json(['message' => $overflowMessage, 'errors' => ['good_qty' => [$overflowMessage]]], 422);
            }
        }

        if (! $requiresPart) {
            $validated['part_id'] = null;
        }

        if ($spk) {
            if ($requiresPart && $spk->part_id && isset($validated['part_id']) && (int) $validated['part_id'] !== (int) $spk->part_id) {
                return response()->json(['message' => 'Part harus sesuai dengan part di SPK.', 'errors' => ['part_id' => ['Part harus sesuai dengan part di SPK.']]], 422);
            }

            if ($requiresPart && isset($validated['part_id']) && ! $this->partMatchesBuyer((int) $validated['part_id'], (int) $spk->buyer_id)) {
                return response()->json(['message' => 'Part tidak sesuai dengan buyer SPK.', 'errors' => ['part_id' => ['Part tidak sesuai dengan buyer SPK.']]], 422);
            }

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

    private function bindingHourlyRows($entries, string $date, string $shift)
    {
        return $entries
            ->whereNotNull('operator_id')
            ->groupBy('operator_id')
            ->map(function ($operatorEntries) use ($date, $shift) {
                $operator = $operatorEntries->first()->operator;
                $hours = $this->hourlyEntryBuckets($operatorEntries, $date, $shift);

                return [
                    $operator?->operator_code,
                    $operator?->name,
                    $operator?->target_prod,
                    ...array_map(fn ($bucket) => $this->formatBuyerSizeBucket($bucket), $hours),
                    (int) $operatorEntries->sum('good_qty'),
                    (int) $operatorEntries->sum('ng_qty'),
                    round($operatorEntries->sum(fn (ProductionEntry $entry) => $entry->good_qty * (float) ($entry->sizeVariant?->point ?? 0)), 2),
                ];
            })
            ->values();
    }

    private function productionHourlyReport(
        string $date,
        string $shift,
        Process $process,
        string $period = 'daily',
        ?string $month = null,
    ): array
    {
        $query = ProductionEntry::with(['operator', 'buyer', 'sizeVariant'])
            ->where('shift', $shift)
            ->where('process_id', $process->id);

        if ($period === 'monthly') {
            $monthStart = CarbonImmutable::createFromFormat('Y-m', $month ?? substr($date, 0, 7))->startOfMonth();
            $query->whereBetween('production_date', [$monthStart->toDateString(), $monthStart->endOfMonth()->toDateString()]);
        } else {
            $query->whereDate('production_date', $date);
        }

        $entries = $query
            ->orderBy('production_date')
            ->orderBy('created_at')
            ->get();

        $isBinding = strcasecmp($process->name, 'Binding') === 0;
        if ($period === 'monthly') {
            return [
                'process' => $process,
                'headers' => $isBinding
                    ? ['Tanggal', 'No', 'Nama Operator', 'Target Operator', 'Total Good', 'Total Reject', 'Total Point', 'Pencapaian Target']
                    : ['Tanggal', 'Buyer', 'Size', 'Total Good', 'Total Reject'],
                'rows' => $isBinding
                    ? $this->bindingMonthlyRows($entries)
                    : $this->processMonthlyRows($entries),
                'totals_row' => null,
                'record_count' => $entries->count(),
            ];
        }

        $headers = $isBinding
            ? ['No', 'Nama Operator', 'Target Operator', 'Jam 1', 'Jam 2', 'Jam 3', 'Jam 4', 'Jam 5', 'Jam 6', 'Jam 7', 'Total Good', 'Total Reject', 'Total Point']
            : ['Buyer', 'Size', 'Jam 1', 'Jam 2', 'Jam 3', 'Jam 4', 'Jam 5', 'Jam 6', 'Jam 7', 'Total Good', 'Total Reject'];

        return [
            'process' => $process,
            'headers' => $headers,
            'rows' => $isBinding
                ? $this->bindingHourlyRows($entries, $date, $shift)
                : $this->processHourlyRows($entries, $date, $shift),
            'totals_row' => $this->hourlyTotalsRow($entries, $date, $shift, $isBinding),
            'record_count' => $entries->count(),
        ];
    }

    private function hourlyTotalsRow($entries, string $date, string $shift, bool $isBinding): array
    {
        $hourTotals = array_map(function ($bucket): string {
            if (! $bucket) {
                return "TOTAL G: 0 · R: 0";
            }

            $operators = $bucket
                ->filter(fn (ProductionEntry $entry) => $entry->operator_id)
                ->unique('operator_id');
            $operatorCount = $operators->count();
            $targetTotal = (int) $operators->sum(fn (ProductionEntry $entry) => (int) ($entry->operator?->target_prod ?? 0));

            $styles = $bucket
                ->groupBy(fn (ProductionEntry $entry) => ($entry->buyer_id ?? 'none').'-'.($entry->size_variant_id ?? 'none'))
                ->map(function ($group): string {
                    $first = $group->first();
                    $size = $first->sizeVariant;
                    $sizeCode = $size
                        ? ($size->production_code ? $size->production_code.'-' : '').$size->code
                        : '—';

                    return ($first->buyer?->code ?? '—').' / '.$sizeCode
                        .' = '.(int) $group->sum('good_qty');
                })
                ->implode("\n");

            return $styles
                ."\nTarget: ".$targetTotal
                ."\nOperator: ".$operatorCount
                ."\nTOTAL G: ".(int) $bucket->sum('good_qty').' · R: '.(int) $bucket->sum('ng_qty');
        }, $this->hourlyEntryBuckets($entries, $date, $shift));

        return [
            'TOTAL PER JAM',
            ...array_fill(0, $isBinding ? 2 : 1, ''),
            ...$hourTotals,
            (int) $entries->sum('good_qty'),
            (int) $entries->sum('ng_qty'),
            ...($isBinding ? [
                round($entries->sum(fn (ProductionEntry $entry) => $entry->good_qty * (float) ($entry->sizeVariant?->point ?? 0)), 2),
            ] : []),
        ];
    }

    private function bindingMonthlyRows($entries)
    {
        return $entries
            ->whereNotNull('operator_id')
            ->groupBy(fn (ProductionEntry $entry) => $entry->production_date->format('Y-m-d').'-'.$entry->operator_id)
            ->map(function ($operatorEntries) {
                $first = $operatorEntries->first();
                $operator = $first->operator;
                $good = (int) $operatorEntries->sum('good_qty');
                $target = (int) ($operator?->target_prod ?? 0);

                return [
                    $first->production_date->format('d M Y'),
                    $operator?->operator_code,
                    $operator?->name,
                    $target,
                    $good,
                    (int) $operatorEntries->sum('ng_qty'),
                    round($operatorEntries->sum(fn (ProductionEntry $entry) => $entry->good_qty * (float) ($entry->sizeVariant?->point ?? 0)), 2),
                    ($target > 0 ? (int) round($good / $target * 100) : 0).'%',
                ];
            })
            ->values();
    }

    private function processMonthlyRows($entries)
    {
        return $entries
            ->groupBy(fn (ProductionEntry $entry) => $entry->production_date->format('Y-m-d').'-'.($entry->buyer_id ?? 'none').'-'.($entry->size_variant_id ?? 'none'))
            ->map(function ($groupEntries) {
                $first = $groupEntries->first();

                return [
                    $first->production_date->format('d M Y'),
                    $first->buyer?->code ?? '—',
                    $first->sizeVariant?->code ?? '—',
                    (int) $groupEntries->sum('good_qty'),
                    (int) $groupEntries->sum('ng_qty'),
                ];
            })
            ->values();
    }

    private function processHourlyRows($entries, string $date, string $shift)
    {
        return $entries
            ->groupBy(fn (ProductionEntry $entry) => ($entry->buyer_id ?? 'none').'-'.($entry->size_variant_id ?? 'none'))
            ->map(function ($groupEntries) use ($date, $shift) {
                $first = $groupEntries->first();
                $hours = $this->hourlyEntryBuckets($groupEntries, $date, $shift);

                return [
                    $first->buyer?->code ?? '—',
                    $first->sizeVariant?->code ?? '—',
                    ...array_map(fn ($bucket) => $this->formatQuantityBucket($bucket), $hours),
                    (int) $groupEntries->sum('good_qty'),
                    (int) $groupEntries->sum('ng_qty'),
                ];
            })
            ->values();
    }

    private function hourlyEntryBuckets($entries, string $date, string $shift): array
    {
        $buckets = array_fill(0, 7, null);
        $start = CarbonImmutable::parse($date, 'Asia/Jakarta');
        $start = match ($shift) {
            '1' => $start->setTime(8, 0),
            '2' => $start->setTime(16, 0),
            '3' => $start->addDay()->startOfDay(),
        };

        foreach ($entries as $entry) {
            $timestamp = CarbonImmutable::parse($entry->created_at, config('app.timezone'))
                ->setTimezone('Asia/Jakarta');
            $hourIndex = intdiv($timestamp->getTimestamp() - $start->getTimestamp(), 3600);

            if ($hourIndex >= 0 && $hourIndex < 7) {
                $buckets[$hourIndex] ??= collect();
                $buckets[$hourIndex]->push($entry);
            }
        }

        return $buckets;
    }

    private function formatBuyerSizeBucket($entries): string
    {
        if (! $entries) {
            return '';
        }

        return $entries
            ->groupBy(fn (ProductionEntry $entry) => $this->entryInputTime($entry).'-'.($entry->buyer_id ?? 'none').'-'.($entry->size_variant_id ?? 'none'))
            ->map(function ($group) {
                $first = $group->first();

                $size = $first->sizeVariant;
                $sizeCode = $size
                    ? ($size->production_code ? $size->production_code.'-' : '').$size->code
                    : '—';

                $rejectReasons = $group
                    ->pluck('reject_reason')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                return $this->entryInputTime($first).' · '.($first->buyer?->code ?? '—').' / '.$sizeCode
                    .' = '.(int) $group->sum('good_qty').', Reject = '.(int) $group->sum('ng_qty')
                    .($rejectReasons ? ' · Alasan: '.$rejectReasons : '');
            })
            ->implode("\n");
    }

    private function formatQuantityBucket($entries): string
    {
        if (! $entries) {
            return '';
        }

        return $entries
            ->groupBy(fn (ProductionEntry $entry) => $this->entryInputTime($entry))
            ->map(function ($group) {
                $rejectReasons = $group->pluck('reject_reason')->filter()->unique()->implode(', ');

                return $this->entryInputTime($group->first()).' · Good = '.(int) $group->sum('good_qty')
                    .', Reject = '.(int) $group->sum('ng_qty')
                    .($rejectReasons ? ' · Alasan: '.$rejectReasons : '');
            })
            ->implode("\n");
    }

    private function entryInputTime(ProductionEntry $entry): string
    {
        return CarbonImmutable::parse($entry->created_at, config('app.timezone'))
            ->setTimezone('Asia/Jakarta')
            ->format('H:i');
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

    private function spkProcessCapacityError(Spk $spk, Process $process, int $entryQty): ?string
    {
        $existingQty = (int) ProductionEntry::where('spk_id', $spk->id)
            ->where('process_id', $process->id)
            ->sum(DB::raw('good_qty + ng_qty'));

        $remaining = max(0, $spk->target_qty - $existingQty);
        if ($entryQty > $remaining) {
            return "Total produksi untuk SPK {$spk->spk_no} proses {$process->name} tidak boleh melebihi target lot ({$spk->target_qty} pcs). Sisa kapasitas: {$remaining} pcs.";
        }

        return null;
    }

    private function partMatchesBuyer(int $partId, int $buyerId): bool
    {
        $part = Part::find($partId);

        return $part && (! $part->buyer_id || (int) $part->buyer_id === $buyerId);
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

    private function rejectReasonOptions(): array
    {
        return ['Pembersihan', 'Bongkar', 'Jahitan Jebol', 'Label Salah', 'Lain-lain'];
    }

    private function productionWindow(Request $request): array
    {
        if ($request->filled('production_date') && array_key_exists((string) $request->query('shift'), $this->shiftOptions())) {
            return [
                'date' => (string) $request->query('production_date'),
                'shift' => (string) $request->query('shift'),
                'manual' => true,
            ];
        }

        $now = now('Asia/Jakarta');
        $hour = (int) $now->format('G');

        if ($hour < 8) {
            return [
                'date' => $now->copy()->subDay()->toDateString(),
                'shift' => '3',
                'manual' => false,
            ];
        }

        return [
            'date' => $now->toDateString(),
            'shift' => $hour < 16 ? '1' : '2',
            'manual' => false,
        ];
    }
}
