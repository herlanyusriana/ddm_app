@extends('production.layout', ['title' => $pageTitle, 'subtitle' => 'Input Good dan Reject per proses'])

@section('topbar-actions')
    @php($exportProcess = $hourlyReport['process'])
    @if($exportProcess)
        <a
            class="link-btn link-btn-success"
            href="{{ route('reports.production-hourly', ['production_date' => $date, 'shift' => $shift, 'process_id' => $exportProcess->id, 'history_period' => $historyPeriod, 'production_month' => $productionMonth], false) }}"
        >Export History Excel</a>
    @endif
    <form class="filter-bar" method="get" action="" style="margin:0;border:0;background:transparent;padding:0">
        @if($pageType === 'proses' && $selectedProcess)
            <input type="hidden" name="process_id" value="{{ $selectedProcess->id }}">
        @endif
        <select name="history_period" data-history-period style="min-height:36px;font-size:13px">
            <option value="daily" @selected($historyPeriod === 'daily')>Harian</option>
            <option value="monthly" @selected($historyPeriod === 'monthly')>Bulanan</option>
        </select>
        <input type="date" name="production_date" value="{{ $date }}" data-daily-filter style="min-height:36px;font-size:13px;{{ $historyPeriod === 'monthly' ? 'display:none' : '' }}">
        <input type="month" name="production_month" value="{{ $productionMonth }}" data-monthly-filter style="min-height:36px;font-size:13px;{{ $historyPeriod === 'daily' ? 'display:none' : '' }}">
        <select name="shift" style="min-height:36px;font-size:13px">
            @foreach($shiftOptions as $key => $opt)
                <option value="{{ $key }}" @selected((string) $shift === (string) $key)>{{ $opt['label'] }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
    </form>
@endsection

@section('content')
<style>
    .production-input-grid {
        align-items: start;
        display: grid;
        gap: 20px;
        grid-template-columns: minmax(340px, 380px) 1fr;
    }

    @media (max-width: 760px) {
        .production-input-grid {
            gap: 12px;
            grid-template-columns: 1fr;
        }
    }

    .readonly-pill {
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid var(--line);
        border-radius: var(--radius-sm);
        color: var(--ink);
        display: flex;
        font-size: 14px;
        font-weight: 700;
        min-height: 42px;
        padding: 10px 12px;
    }

    .field-hint {
        color: var(--muted);
        font-size: 11px;
        font-weight: 600;
        line-height: 1.35;
    }

    .qty-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .hourly-history-panel {
        min-width: 0;
    }

    .hourly-history-table {
        min-width: 1420px;
    }

    .hourly-history-table th,
    .hourly-history-table td {
        white-space: nowrap;
    }

    .hourly-history-table .hour-column {
        min-width: 170px;
    }

    .hourly-history-table .identity-column {
        min-width: 100px;
    }

    .hourly-history-table tfoot td {
        background: var(--primary-soft);
        border-top: 2px solid #bfdbfe;
        color: var(--ink2);
        font-size: 12px;
        font-weight: 800;
    }

    .hour-good {
        color: var(--success);
    }

    .hour-reject {
        color: var(--danger);
    }

    .hour-meta {
        color: var(--ink2);
        font-weight: 800;
        margin-top: 3px;
    }

    .correction-actions {
        display: flex;
        gap: 6px;
    }

    .trouble-history-panel {
        grid-column: 2;
        min-width: 0;
    }

    @media (max-width: 760px) {
        .qty-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .hourly-history-table {
            min-width: 1320px;
        }

        .trouble-history-panel {
            grid-column: auto;
        }
    }
</style>

<div class="production-input-grid">

    {{-- Input Form --}}
    <div class="panel">
        <div class="panel-header">
            <h2>Form Input</h2>
            <span class="badge badge-neutral">{{ date('d M Y', strtotime($date)) }} · Shift {{ $shift }}</span>
        </div>
        <div class="panel-body">
            @if($pageType === 'proses' && ! $selectedProcess)
                <div class="empty-state">
                    <div class="empty-icon">⚙️</div>
                    <p>Belum ada proses WIP yang dapat dipilih.</p>
                </div>
            @else
            <form class="form-grid" method="post" action="/production-entries">
                @csrf
                @if(! $isManualWindow)
                    <input type="hidden" name="automatic_window" value="1">
                @endif
                <input type="hidden" name="production_date" value="{{ $date }}">
                <input type="hidden" name="shift" value="{{ $shift }}">

                <div class="field">
                    <label>SPK / Lot Produksi</label>
                    <select name="spk_id">
                        <option value="">Custom / Tanpa SPK</option>
                        @foreach($spks as $spk)
                            <option
                                value="{{ $spk->id }}"
                                data-buyer-id="{{ $spk->buyer_id }}"
                                data-buyer-name="{{ $spk->buyer?->name }}"
                                data-part-id="{{ $spk->part_id }}"
                                data-size-id="{{ $spk->size_variant_id }}"
                                data-target-qty="{{ $spk->target_qty }}"
                            >
                                {{ $spk->spk_no }} · {{ $spk->buyer?->name }} · {{ $spk->item }} · {{ $spk->style }} · {{ number_format($spk->target_qty) }} pcs
                            </option>
                        @endforeach
                    </select>
                    <div class="field-hint">Kosongkan SPK untuk input menggunakan Buyer dan Size dari Master Data.</div>
                </div>
                <div class="field">
                    <label>Target Lot & Sisa Kapasitas</label>
                    <div class="readonly-pill" data-spk-target-info>— Pilih SPK dan proses —</div>
                    <div class="field-hint" data-spk-warning style="display:none;"></div>
                </div>

                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:10px">Mode Pencatatan</div>
                    <div class="processes" style="grid-template-columns:repeat(2,1fr)">
                        <label class="process-label">
                            <input type="radio" name="record_mode" value="production" checked>
                            Input Produksi
                        </label>
                        <label class="process-label">
                            <input type="radio" name="record_mode" value="trouble">
                            Trouble
                        </label>
                    </div>
                </div>

                <div data-custom-production-fields data-production-mode-fields data-page-type="{{ $pageType }}" class="form-grid">
                    <div class="field">
                        <label>Kode Buyer</label>
                        <select name="buyer_id" required data-custom-buyer-select>
                            <option value="">— Pilih Buyer —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}">{{ $buyer->code }} · {{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" data-spk-item-field>
                        <label>Item {{ $pageType === 'hasil' ? 'FG' : '' }}</label>
                        <select name="part_id" required data-custom-part-select>
                            <option value="">— Pilih Item —</option>
                        @foreach($parts as $p)
                            <option
                                value="{{ $p->id }}"
                                data-part-id="{{ $p->id }}"
                                data-part-buyer-id="{{ $p->buyer_id }}"
                            >
                                {{ $p->code }} · {{ $p->name }}
                            </option>
                        @endforeach
                        </select>
                        <div class="field-hint" data-custom-part-hint>Item tampil sesuai buyer yang dipilih.</div>
                    </div>
                    <div class="field">
                        <label>Code Produksi</label>
                        <select name="production_code" required data-production-code-select>
                            <option value="">— Pilih Code —</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                        </select>
                        <div class="field-hint">Code menentukan pilihan Size dan Point produksi.</div>
                    </div>
                    <div class="field">
                        <label>Kode Size</label>
                        <select name="size_variant_id" required data-custom-size-select disabled>
                            <option value="">— Pilih Code dulu —</option>
                            @foreach($sizes as $s)
                                <option
                                    value="{{ $s->id }}"
                                    data-production-code="{{ $s->production_code }}"
                                    data-size-code="{{ $s->code }}"
                                >{{ $s->display_label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="divider"></div>

                @if($pageType === 'proses')
                    <input type="hidden" name="process_id" value="{{ $selectedProcess->id }}">
                    <div class="field">
                        <label>Proses aktif</label>
                        <div class="readonly-pill">{{ $selectedProcess->name }}</div>
                    </div>
                    @if(strcasecmp($selectedProcess->name, 'Binding') === 0)
                        <div class="field">
                            <label>Operator Binding</label>
                            <input
                                name="operator_search"
                                list="operator-suggestions"
                                value="{{ old('operator_search') }}"
                                placeholder="Ketik nomor atau nama operator..."
                                autocomplete="off"
                                data-operator-search
                                required
                            >
                            <input type="hidden" name="operator_id" value="{{ old('operator_id') }}" data-operator-id>
                            <datalist id="operator-suggestions">
                                @foreach($operators as $operator)
                                    <option
                                        value="{{ $operator->operator_code }} · {{ $operator->name }}"
                                        data-operator-id="{{ $operator->id }}"
                                    ></option>
                                @endforeach
                            </datalist>
                            <div class="field-hint">Ketik nomor atau nama, lalu pilih dari suggestion.</div>
                        </div>
                    @endif
                @else
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:10px">Pilih Proses</div>
                        <div class="processes" style="grid-template-columns:repeat(2,1fr)">
                            @foreach($inputProcesses as $process)
                                <label class="process-label">
                                    <input type="radio" name="process_id" value="{{ $process->id }}" required data-process-name="{{ $process->name }}">
                                    {{ $process->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div data-production-mode-fields>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:10px">Jumlah Produksi</div>
                    <div class="qty-grid qty-grid-2">
                        <div class="qty-box good">
                            <label>✅ Good</label>
                            <input type="number" min="0" name="good_qty" value="0">
                        </div>
                        <div class="qty-box rework">
                            <label>🔧 Reject</label>
                            <input type="number" min="0" name="reject_qty" value="0">
                        </div>
                    </div>
                </div>

                <div class="form-grid" data-trouble-mode-fields style="display:none">
                    <div class="field">
                        <label>Jenis Trouble</label>
                        <select name="trouble_type" disabled required>
                            <option value="">— Pilih Jenis Trouble —</option>
                            <option value="Mesin">Mesin</option>
                            <option value="Material">Material</option>
                            <option value="Quality">Quality</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-row-2">
                        <div class="field">
                            <label>Jam Mulai</label>
                            <input type="time" name="trouble_start_time" disabled required>
                        </div>
                        <div class="field">
                            <label>Jam Selesai</label>
                            <input type="time" name="trouble_end_time" disabled required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Keterangan Trouble</label>
                        <input name="trouble_notes" disabled maxlength="500" placeholder="Contoh: ganti bearing mesin" required>
                    </div>
                </div>

                <button class="btn btn-primary btn-full btn-lg" type="submit" data-submit-production>Simpan Input Produksi</button>
            </form>
            @endif
        </div>
    </div>

    {{-- History --}}
    <div class="panel hourly-history-panel">
        <div class="panel-header">
            <h2>History Input</h2>
            <span class="badge badge-neutral">{{ $hourlyReport['record_count'] }} records</span>
        </div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table class="hourly-history-table">
                    <thead>
                        <tr>
                            @foreach($hourlyReport['headers'] as $header)
                                <th class="{{ str_starts_with($header, 'Total ') ? 'td-num' : '' }} {{ str_starts_with($header, 'Jam ') ? 'hour-column' : 'identity-column' }}">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($hourlyReport['rows'] as $row)
                        <tr>
                            @foreach($row as $index => $cell)
                                <td class="{{ str_starts_with($hourlyReport['headers'][$index] ?? '', 'Total ') ? 'td-num' : '' }}">
                                    {!! nl2br(e((string) $cell)) !!}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(1, count($hourlyReport['headers'])) }}">
                            <div class="empty-state"><div class="empty-icon">📭</div><p>Belum ada input untuk filter ini.</p></div>
                        </td></tr>
                    @endforelse
                    </tbody>
                    @if($hourlyReport['totals_row'])
                        <tfoot>
                            <tr>
                                @foreach($hourlyReport['totals_row'] as $index => $cell)
                                    <td class="{{ str_starts_with($hourlyReport['headers'][$index] ?? '', 'Total ') ? 'td-num' : '' }}">
                                        @if(str_starts_with($hourlyReport['headers'][$index] ?? '', 'Jam '))
                                            @foreach(explode("\n", (string) $cell) as $line)
                                                @if(preg_match('/^TOTAL G: (\d+) · R: (\d+)$/', $line, $totals))
                                                    <div style="margin-top:4px">
                                                        <strong>TOTAL </strong>
                                                        <span class="hour-good">G: {{ $totals[1] }}</span>
                                                        <span> · </span>
                                                        <span class="hour-reject">R: {{ $totals[2] }}</span>
                                                    </div>
                                                @elseif(preg_match('/^(Target|Operator): (.+)$/', $line, $meta))
                                                    <div class="hour-meta">{{ $meta[1] }}: {{ $meta[2] }}</div>
                                                @else
                                                    <div>{{ $line }}</div>
                                                @endif
                                            @endforeach
                                        @else
                                            {{ $cell }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="panel trouble-history-panel">
        <div class="panel-header">
            <h2>History Trouble</h2>
            <span class="badge badge-neutral">{{ $troubles->count() }} records</span>
        </div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            @if($historyPeriod === 'monthly')<th>Tanggal</th>@endif
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Jenis</th>
                            <th>Operator</th>
                            <th>SPK</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($troubles as $trouble)
                        <tr>
                            @if($historyPeriod === 'monthly')<td>{{ $trouble->production_date->format('d M Y') }}</td>@endif
                            <td>{{ substr($trouble->start_time, 0, 5) }} - {{ substr($trouble->end_time, 0, 5) }}</td>
                            <td>{{ $trouble->duration_minutes }} menit</td>
                            <td><span class="badge badge-warning">{{ $trouble->trouble_type }}</span></td>
                            <td>{{ $trouble->operator?->name ?? '—' }}</td>
                            <td>{{ $trouble->spk?->spk_no ?? 'Custom' }}</td>
                            <td>{{ $trouble->notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $historyPeriod === 'monthly' ? 7 : 6 }}">
                            <div class="empty-state"><div class="empty-icon">🛠️</div><p>Belum ada trouble untuk filter ini.</p></div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel trouble-history-panel">
        <div class="panel-header">
            <h2>Koreksi Input Produksi</h2>
            <span class="badge badge-neutral">{{ $correctionEntries->count() }} records</span>
        </div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Waktu</th><th>Operator</th><th>Style</th><th>Good</th><th>Reject</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($correctionEntries as $entry)
                        <tr>
                            <td>{{ $entry->created_at->timezone('Asia/Jakarta')->format('H:i') }}</td>
                            <td>{{ $entry->operator?->name ?? '—' }}</td>
                            <td>{{ $entry->buyer?->code ?? '—' }} / {{ $entry->sizeVariant?->production_code }}-{{ $entry->sizeVariant?->code }}</td>
                            <td class="td-num">{{ $entry->good_qty }}</td>
                            <td class="td-num">{{ $entry->ng_qty }}</td>
                            <td>
                                <div class="correction-actions">
                                    <a class="btn btn-secondary btn-sm" href="/production-entries/{{ $entry->id }}/edit">Edit</a>
                                    <form method="post" action="/production-entries/{{ $entry->id }}" onsubmit="return confirm('Hapus input ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><p>Belum ada input untuk dikoreksi.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    function syncProductionMasterFields() {
        const spkSelect = document.querySelector('select[name="spk_id"]');
        const buyerSelect = document.querySelector('[data-custom-buyer-select]');
        const partSelect = document.querySelector('[data-custom-part-select]');
        const partHint = document.querySelector('[data-custom-part-hint]');
        const sizeSelect = document.querySelector('[data-custom-size-select]');
        const productionCodeSelect = document.querySelector('[data-production-code-select]');
        const fields = document.querySelector('[data-custom-production-fields]');
        const itemField = document.querySelector('[data-spk-item-field]');

        if (!spkSelect || !buyerSelect || !partSelect || !sizeSelect) {
            return;
        }

        const selected = spkSelect.selectedOptions[0];
        const hasSpk = Boolean(selected?.value);
        const buyerId = hasSpk ? selected.dataset.buyerId || '' : buyerSelect.value;
        const lockedPartId = selected?.dataset.partId || '';
        const lockedSizeId = selected?.dataset.sizeId || '';
        const requiresFgData = fields?.dataset.pageType === 'hasil';

        buyerSelect.required = !hasSpk;
        partSelect.required = hasSpk && requiresFgData && !lockedPartId;
        sizeSelect.required = !hasSpk || (requiresFgData && !lockedSizeId);
        itemField.style.display = hasSpk && requiresFgData ? '' : 'none';

        if (!hasSpk) {
            partSelect.value = '';
        }

        if (hasSpk) {
            buyerSelect.value = buyerId;
        }

        let visibleCount = 0;
        const previousPartId = partSelect.value;

        Array.from(partSelect.options).forEach((option) => {
            if (!option.value) {
                option.textContent = buyerId ? '— Pilih Item —' : '— Pilih Buyer dulu —';
                option.hidden = false;
                return;
            }

            const optionBuyerId = option.dataset.partBuyerId || '';
            const allowedByBuyer = optionBuyerId === '' || optionBuyerId === buyerId;
            const allowedBySpkPart = !lockedPartId || option.value === lockedPartId;
            const isVisible = Boolean(buyerId) && allowedByBuyer && allowedBySpkPart;

            option.hidden = !isVisible;
            option.disabled = !isVisible;

            if (isVisible) {
                visibleCount++;
            }
        });

        if (lockedPartId) {
            partSelect.value = lockedPartId;
            partHint.textContent = 'Item mengikuti SPK yang dipilih.';
        } else if (buyerId) {
            const previousOption = partSelect.querySelector(`option[value="${previousPartId}"]`);
            partSelect.value = previousOption && !previousOption.disabled ? previousPartId : '';
            partHint.textContent = visibleCount + ' item tersedia untuk buyer ini.';
        } else {
            partSelect.value = '';
            partHint.textContent = 'Pilih buyer untuk menampilkan item.';
        }

        if (lockedSizeId) {
            sizeSelect.value = lockedSizeId;
            productionCodeSelect.value = sizeSelect.selectedOptions[0]?.dataset.productionCode || '';
            syncProductionCode();
        }
    }

    function syncProductionCode() {
        const sizeSelect = document.querySelector('[data-custom-size-select]');
        const productionCodeSelect = document.querySelector('[data-production-code-select]');

        if (!sizeSelect || !productionCodeSelect) {
            return;
        }

        const currentOption = sizeSelect.selectedOptions[0];
        const currentSizeCode = currentOption?.dataset.sizeCode || '';
        const productionCode = productionCodeSelect.value;

        sizeSelect.disabled = !productionCode;
        sizeSelect.options[0].textContent = productionCode ? '— Pilih Size —' : '— Pilih Code dulu —';

        Array.from(sizeSelect.options).forEach((option) => {
            if (!option.value) {
                return;
            }

            const matchesCode = Boolean(productionCode) && option.dataset.productionCode === productionCode;
            option.hidden = !matchesCode;
            option.disabled = !matchesCode;
        });

        const matchingOption = currentSizeCode
            ? Array.from(sizeSelect.options).find((option) =>
                option.dataset.productionCode === productionCode
                && option.dataset.sizeCode === currentSizeCode
            )
            : null;

        if (matchingOption) {
            sizeSelect.value = matchingOption.value;
        } else {
            sizeSelect.value = '';
        }
    }

    document.querySelector('select[name="spk_id"]')?.addEventListener('change', syncProductionMasterFields);
    document.querySelector('[data-custom-buyer-select]')?.addEventListener('change', syncProductionMasterFields);
    document.querySelector('[data-production-code-select]')?.addEventListener('change', syncProductionCode);
    document.querySelector('[data-custom-size-select]')?.addEventListener('change', (event) => {
        const productionCodeSelect = document.querySelector('[data-production-code-select]');
        productionCodeSelect.value = event.target.selectedOptions[0]?.dataset.productionCode || '';
    });
    syncProductionMasterFields();
    syncProductionCode();

    function syncRecordMode() {
        const troubleMode = document.querySelector('input[name="record_mode"]:checked')?.value === 'trouble';
        const troubleFields = document.querySelector('[data-trouble-mode-fields]');
        const submitButton = document.querySelector('[data-submit-production]');

        document.querySelectorAll('[data-production-mode-fields]').forEach((section) => {
            section.style.display = troubleMode ? 'none' : '';
            section.querySelectorAll('input, select, textarea').forEach((field) => field.disabled = troubleMode);
        });
        troubleFields.style.display = troubleMode ? '' : 'none';
        troubleFields.querySelectorAll('input, select, textarea').forEach((field) => field.disabled = !troubleMode);
        submitButton.textContent = troubleMode ? 'Simpan Trouble' : 'Simpan Input Produksi';

        if (!troubleMode) {
            syncProductionCode();
        }
    }

    document.querySelectorAll('input[name="record_mode"]').forEach((radio) => radio.addEventListener('change', syncRecordMode));
    syncRecordMode();

    const operatorSearch = document.querySelector('[data-operator-search]');
    const operatorId = document.querySelector('[data-operator-id]');
    const operatorOptions = Array.from(document.querySelectorAll('#operator-suggestions option'));

    operatorSearch?.addEventListener('input', () => {
        const selected = operatorOptions.find((option) => option.value === operatorSearch.value);
        operatorId.value = selected?.dataset.operatorId || '';
        operatorSearch.setCustomValidity(selected ? '' : 'Pilih operator dari suggestion.');
    });
</script>
<script>
    document.querySelector('[data-history-period]')?.addEventListener('change', (event) => {
        const monthly = event.target.value === 'monthly';
        document.querySelector('[data-daily-filter]').style.display = monthly ? 'none' : '';
        document.querySelector('[data-monthly-filter]').style.display = monthly ? '' : 'none';
    });
</script>
<script>
    const spkProcessTotals = @json($spkProcessTotals);

    function updateSpkTargetInfo() {
        const spkSelect = document.querySelector('select[name="spk_id"]');
        const processInput = document.querySelector('input[name="process_id"]:checked, input[type="hidden"][name="process_id"]');
        const targetInfo = document.querySelector('[data-spk-target-info]');
        const warning = document.querySelector('[data-spk-warning]');
        const goodInput = Number(document.querySelector('input[name="good_qty"]').value || 0);
        const rejectInput = Number(document.querySelector('input[name="reject_qty"]').value || 0);

        if (!spkSelect || !targetInfo || !warning) {
            return;
        }

        const selected = spkSelect.selectedOptions[0];
        const targetQty = selected ? Number(selected.dataset.targetQty || 0) : 0;
        const spkId = selected ? selected.value : null;
        const processId = processInput ? processInput.value : null;
        const entryQty = goodInput + rejectInput;

        let currentQty = 0;
        if (spkId && processId && spkProcessTotals[spkId] && spkProcessTotals[spkId][processId]) {
            currentQty = Number(spkProcessTotals[spkId][processId]);
        }

        const remainingQty = Math.max(0, targetQty - currentQty);

        if (targetQty > 0) {
            targetInfo.textContent = `Target lot: ${targetQty} pcs · Terpakai untuk proses saat ini: ${currentQty} pcs · Sisa: ${remainingQty} pcs`;
        } else if (!spkId) {
            targetInfo.textContent = 'Mode Custom · Tanpa batas target SPK';
        } else {
            targetInfo.textContent = 'Pilih SPK terlebih dulu';
        }

        if (entryQty > remainingQty) {
            warning.style.display = 'block';
            warning.textContent = `Warning: total input saat ini (${entryQty} pcs) melebihi sisa kapasitas proses (${remainingQty} pcs).`;
            warning.style.color = 'var(--danger)';
        } else if (targetQty > 0) {
            warning.style.display = 'block';
            warning.textContent = `Total input saat ini: ${entryQty} pcs. Sisa kapasitas: ${remainingQty} pcs.`;
            warning.style.color = 'var(--muted)';
        } else {
            warning.style.display = 'none';
        }
    }

    document.querySelector('select[name="spk_id"]')?.addEventListener('change', updateSpkTargetInfo);
    document.querySelectorAll('input[name="process_id"]').forEach((radio) => radio.addEventListener('change', updateSpkTargetInfo));
    document.querySelector('input[name="good_qty"]')?.addEventListener('input', updateSpkTargetInfo);
    document.querySelector('input[name="reject_qty"]')?.addEventListener('input', updateSpkTargetInfo);
    updateSpkTargetInfo();
</script>
@endsection
