@extends('production.layout', ['title' => $pageTitle, 'subtitle' => $pageSubtitle])

@section('topbar-actions')
    @php($historyQuery = array_filter([
        'input_type' => $pageType === 'hasil' ? 'hasil' : null,
        'process_id' => $pageType === 'proses' && $selectedProcess ? $selectedProcess->id : null,
        'production_date' => $date,
        'shift' => $shift,
    ], fn ($value) => $value !== null && $value !== ''))
    <a class="link-btn link-btn-secondary" href="{{ route('production.history', $historyQuery, false) }}">Lihat History</a>
@endsection

@section('content')
<style>
    .production-input-grid {
        align-items: start;
        display: grid;
        gap: 20px;
        grid-template-columns: minmax(520px, 760px);
        justify-content: center;
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

    .multi-entry-panel {
        border: 1.5px solid var(--line);
        border-radius: var(--radius-sm);
        display: grid;
        gap: 10px;
        padding: 12px;
    }

    .multi-entry-row {
        align-items: end;
        border-bottom: 1px solid var(--line);
        display: grid;
        gap: 10px;
        grid-template-columns: minmax(110px, 1fr) 78px minmax(110px, 1fr) 80px 78px 78px minmax(120px, 150px) auto;
        padding-bottom: 10px;
    }

    .multi-entry-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .multi-entry-row label {
        color: var(--muted);
        display: block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .multi-entry-actions {
        display: flex;
        gap: 8px;
    }

    .btn-mini {
        border-radius: 10px;
        font-size: 12px;
        min-height: 38px;
        padding: 8px 10px;
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

        .multi-entry-row {
            grid-template-columns: 1fr 1fr;
        }

        .multi-entry-row .operator-cell,
        .multi-entry-row .reason-cell,
        .multi-entry-row .remove-cell {
            grid-column: 1 / -1;
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

                @if($pageType === 'proses' && strcasecmp($selectedProcess->name, 'Binding') === 0)
                    <div class="field" data-binding-operator-field>
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
                        <div class="field-hint">Satu HP untuk satu operator, lalu bisa input beberapa style/size sekaligus.</div>
                    </div>

                @endif

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

                <div
                    data-custom-production-fields
                    data-production-mode-fields
                    data-page-type="{{ $pageType }}"
                    class="form-grid"
                    data-multi-master-fields-hidden
                    style="display:none"
                >
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
                            @foreach($sizes->pluck('production_code')->filter()->unique()->sort() as $productionCode)
                                <option value="{{ $productionCode }}">{{ $productionCode }}</option>
                            @endforeach
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
                    <div class="field" style="margin-bottom:12px">
                        <label>Jam Input</label>
                        <input type="time" name="input_time" value="{{ old('input_time', now('Asia/Jakarta')->format('H:i')) }}" required>
                        <div class="field-hint">Isi jam produksi sebenarnya. Report per jam akan mengikuti jam ini, bukan jam submit.</div>
                    </div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:10px">Jumlah Produksi</div>
                    <div class="multi-entry-panel" data-multi-entry-panel>
                        <div data-multi-entry-list>
                            <div class="multi-entry-row" data-multi-entry-row>
                                <div class="operator-cell">
                                    <label>Buyer</label>
                                    <select name="entries[0][buyer_id]" data-row-buyer required>
                                        <option value="">— Buyer —</option>
                                        @foreach($buyers as $buyer)
                                            <option value="{{ $buyer->id }}">{{ $buyer->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                    <div>
                                        <label>Code</label>
                                        <select name="entries[0][production_code]" data-row-production-code required>
                                            <option value="">— Code —</option>
                                            @foreach($sizes->pluck('production_code')->filter()->unique()->sort() as $productionCode)
                                                <option value="{{ $productionCode }}">{{ $productionCode }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                <div>
                                    <label>Size</label>
                                    <select name="entries[0][size_variant_id]" data-row-size required disabled>
                                        <option value="">— Code —</option>
                                        @foreach($sizes as $s)
                                            <option
                                                value="{{ $s->id }}"
                                                data-production-code="{{ $s->production_code }}"
                                                data-size-code="{{ $s->code }}"
                                            >{{ $s->display_label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label>Kategori</label>
                                    <select name="entries[0][production_category]" data-multi-category>
                                        <option value="N">Normal</option>
                                        <option value="R">R</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Good</label>
                                    <input type="number" min="0" name="entries[0][good_qty]" value="0" data-multi-good>
                                </div>
                                <div>
                                    <label>Reject</label>
                                    <input type="number" min="0" name="entries[0][reject_qty]" value="0" data-multi-reject>
                                </div>
                                <div class="reason-cell" data-multi-reject-reason-field style="display:none">
                                    <label>Alasan Reject</label>
                                    <select name="entries[0][reject_reason]" data-multi-reject-reason-select disabled>
                                        <option value="">— Alasan —</option>
                                        @foreach($rejectReasons as $reason)
                                            <option value="{{ $reason }}">{{ $reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="remove-cell">
                                    <button type="button" class="btn btn-secondary btn-mini" data-remove-multi-entry style="display:none">Hapus</button>
                                </div>
                            </div>
                        </div>
                        <div class="multi-entry-actions">
                            <button type="button" class="btn btn-secondary btn-mini" data-add-multi-entry>+ Tambah Style / Size</button>
                            <div class="field-hint" data-multi-entry-summary>Total input: 0 pcs dari 1 baris.</div>
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
            if (!troubleMode && section.hasAttribute('data-multi-master-fields-hidden')) {
                section.style.display = 'none';
                section.querySelectorAll('input, select, textarea').forEach((field) => field.disabled = true);
            }
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

    const operatorOptions = Array.from(document.querySelectorAll('#operator-suggestions option'));

    document.querySelectorAll('[data-operator-search]').forEach((operatorSearch) => {
        operatorSearch.addEventListener('input', () => {
            const operatorId = operatorSearch.closest('.field')?.querySelector('[data-operator-id]');
            const selected = operatorOptions.find((option) => option.value === operatorSearch.value);
            if (operatorId) {
                operatorId.value = selected?.dataset.operatorId || '';
            }
            operatorSearch.setCustomValidity(selected ? '' : 'Pilih operator dari suggestion.');
        });
    });

    function syncRejectReason() {
        const rejectInput = document.querySelector('input[name="reject_qty"]');
        const reasonField = document.querySelector('[data-reject-reason-field]');
        const reasonSelect = document.querySelector('[data-reject-reason-select]');
        const needsReason = Number(rejectInput?.value || 0) > 0;

        if (!reasonField || !reasonSelect) {
            return;
        }

        reasonField.style.display = needsReason ? '' : 'none';
        reasonSelect.disabled = !needsReason;
        reasonSelect.required = needsReason;

        if (!needsReason) {
            reasonSelect.value = '';
        }
    }

    document.querySelector('input[name="reject_qty"]')?.addEventListener('input', syncRejectReason);
    syncRejectReason();

    const multiEntryList = document.querySelector('[data-multi-entry-list]');
    const addMultiEntryButton = document.querySelector('[data-add-multi-entry]');
    const multiEntrySummary = document.querySelector('[data-multi-entry-summary]');

    function reindexMultiRows() {
        multiEntryList?.querySelectorAll('[data-multi-entry-row]').forEach((row, index) => {
            row.querySelector('[data-row-buyer]').name = `entries[${index}][buyer_id]`;
            row.querySelector('[data-row-production-code]').name = `entries[${index}][production_code]`;
            row.querySelector('[data-row-size]').name = `entries[${index}][size_variant_id]`;
            row.querySelector('[data-multi-category]').name = `entries[${index}][production_category]`;
            row.querySelector('[data-multi-good]').name = `entries[${index}][good_qty]`;
            row.querySelector('[data-multi-reject]').name = `entries[${index}][reject_qty]`;
            row.querySelector('[data-multi-reject-reason-select]').name = `entries[${index}][reject_reason]`;
            row.querySelector('[data-remove-multi-entry]').style.display = index === 0 && multiEntryList.children.length === 1 ? 'none' : '';
        });
    }

    function syncRowProductionCode(row) {
        const productionCodeSelect = row.querySelector('[data-row-production-code]');
        const sizeSelect = row.querySelector('[data-row-size]');
        const productionCode = productionCodeSelect?.value || '';
        const currentOption = sizeSelect?.selectedOptions[0];
        const currentSizeCode = currentOption?.dataset.sizeCode || '';

        if (!sizeSelect) {
            return;
        }

        sizeSelect.disabled = !productionCode;
        sizeSelect.options[0].textContent = productionCode ? '— Size —' : '— Code —';

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

        sizeSelect.value = matchingOption ? matchingOption.value : '';
    }

    function syncMultiRejectReason(row) {
        const rejectInput = row.querySelector('[data-multi-reject]');
        const reasonField = row.querySelector('[data-multi-reject-reason-field]');
        const reasonSelect = row.querySelector('[data-multi-reject-reason-select]');
        const needsReason = Number(rejectInput?.value || 0) > 0;

        reasonField.style.display = needsReason ? '' : 'none';
        reasonSelect.disabled = !needsReason;
        reasonSelect.required = needsReason;

        if (!needsReason) {
            reasonSelect.value = '';
        }
    }

    function syncMultiSummary() {
        if (!multiEntryList || !multiEntrySummary) {
            return;
        }

        const rows = Array.from(multiEntryList.querySelectorAll('[data-multi-entry-row]'));
        const total = rows.reduce((sum, row) => {
            return sum
                + Number(row.querySelector('[data-multi-good]')?.value || 0)
                + Number(row.querySelector('[data-multi-reject]')?.value || 0);
        }, 0);
        const filledRows = rows.filter((row) =>
            Number(row.querySelector('[data-multi-good]')?.value || 0)
            + Number(row.querySelector('[data-multi-reject]')?.value || 0) > 0
        ).length;

        multiEntrySummary.textContent = `Total input: ${total} pcs dari ${filledRows || rows.length} baris.`;
    }

    function bindMultiRow(row) {
        row.querySelector('[data-row-production-code]')?.addEventListener('change', () => syncRowProductionCode(row));
        row.querySelector('[data-multi-reject]')?.addEventListener('input', () => {
            syncMultiRejectReason(row);
            syncMultiSummary();
            updateSpkTargetInfo();
        });
        row.querySelector('[data-multi-good]')?.addEventListener('input', () => {
            syncMultiSummary();
            updateSpkTargetInfo();
        });
        row.querySelector('[data-remove-multi-entry]')?.addEventListener('click', () => {
            row.remove();
            reindexMultiRows();
            syncMultiSummary();
            updateSpkTargetInfo();
        });
        syncMultiRejectReason(row);
        syncRowProductionCode(row);
    }

    addMultiEntryButton?.addEventListener('click', () => {
        const firstRow = multiEntryList?.querySelector('[data-multi-entry-row]');
        if (!firstRow) {
            return;
        }

        const row = firstRow.cloneNode(true);
        row.querySelectorAll('input').forEach((input) => {
            input.value = input.type === 'number' ? '0' : '';
            input.setCustomValidity('');
        });
        row.querySelectorAll('select').forEach((select) => {
            select.value = '';
            select.disabled = select.hasAttribute('data-row-size') || select.hasAttribute('data-multi-reject-reason-select');
        });
        row.querySelector('[data-multi-reject-reason-field]').style.display = 'none';
        multiEntryList.appendChild(row);
        bindMultiRow(row);
        reindexMultiRows();
        syncMultiSummary();
        updateSpkTargetInfo();
    });

    multiEntryList?.querySelectorAll('[data-multi-entry-row]').forEach(bindMultiRow);
    reindexMultiRows();
    syncMultiSummary();
</script>
<script>
    const spkProcessTotals = @json($spkProcessTotals);

    function updateSpkTargetInfo() {
        const spkSelect = document.querySelector('select[name="spk_id"]');
        const processInput = document.querySelector('input[name="process_id"]:checked, input[type="hidden"][name="process_id"]');
        const targetInfo = document.querySelector('[data-spk-target-info]');
        const warning = document.querySelector('[data-spk-warning]');
        const multiRows = Array.from(document.querySelectorAll('[data-multi-entry-row]'));
        const multiQty = multiRows.reduce((sum, row) => {
            return sum
                + Number(row.querySelector('[data-multi-good]')?.value || 0)
                + Number(row.querySelector('[data-multi-reject]')?.value || 0);
        }, 0);
        const singleGoodInput = Number(document.querySelector('input[name="good_qty"]')?.value || 0);
        const singleRejectInput = Number(document.querySelector('input[name="reject_qty"]')?.value || 0);

        if (!spkSelect || !targetInfo || !warning) {
            return;
        }

        const selected = spkSelect.selectedOptions[0];
        const targetQty = selected ? Number(selected.dataset.targetQty || 0) : 0;
        const spkId = selected ? selected.value : null;
        const processId = processInput ? processInput.value : null;
        const entryQty = multiRows.length ? multiQty : singleGoodInput + singleRejectInput;

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
