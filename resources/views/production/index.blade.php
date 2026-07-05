@extends('production.layout', ['title' => $pageTitle, 'subtitle' => 'Input Good dan Reject per proses'])

@section('topbar-actions')
    @php($exportProcess = $selectedProcess ?? $inputProcesses->first())
    @if($exportProcess)
        <a
            class="link-btn link-btn-success"
            href="{{ route('reports.production-hourly', ['production_date' => $date, 'shift' => $shift, 'process_id' => $exportProcess->id], false) }}"
        >Export History Excel</a>
    @endif
    <form class="filter-bar" method="get" action="" style="margin:0;border:0;background:transparent;padding:0">
        @if($pageType === 'proses' && $selectedProcess)
            <input type="hidden" name="process_id" value="{{ $selectedProcess->id }}">
        @endif
        <input type="date" name="production_date" value="{{ $date }}" style="min-height:36px;font-size:13px">
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

    @media (max-width: 760px) {
        .qty-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
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

                <div data-custom-production-fields data-page-type="{{ $pageType }}" class="form-grid">
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
                        <label>Kode Size</label>
                        <select name="size_variant_id" required data-custom-size-select>
                            <option value="">— Pilih Size —</option>
                            @foreach($sizes as $s)<option value="{{ $s->id }}">{{ $s->display_label }}</option>@endforeach
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
                            <select name="operator_id" required>
                                <option value="">— Pilih Operator —</option>
                                @foreach($operators as $operator)
                                    <option value="{{ $operator->id }}">
                                        {{ $operator->operator_code }} · {{ $operator->name }} · Target {{ number_format($operator->target_prod ?? 0) }}
                                    </option>
                                @endforeach
                            </select>
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

                <div>
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

                <div class="field">
                    <label>Catatan (opsional)</label>
                    <input name="notes" placeholder="Tambahan informasi...">
                </div>

                <button class="btn btn-primary btn-full btn-lg" type="submit">Simpan Input Produksi</button>
            </form>
            @endif
        </div>
    </div>

    {{-- History --}}
    <div class="panel">
        <div class="panel-header">
            <h2>History Input</h2>
            <span class="badge badge-neutral">{{ $entries->count() }} records</span>
        </div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>SPK</th>
                            <th>Proses</th>
                            @if($pageType === 'proses' && $selectedProcess && strcasecmp($selectedProcess->name, 'Binding') === 0)<th>Operator</th>@endif
                            <th>Buyer</th><th>Item</th><th>Size</th>
                            <th class="td-num">Good</th>
                            <th class="td-num">Reject</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>
                                @if($entry->spk_id)
                                    <a class="master-code" href="/spk/{{ $entry->spk_id }}">{{ $entry->spk?->spk_no ?? '—' }}</a>
                                @else
                                    <span class="badge badge-primary">Custom</span>
                                @endif
                            </td>
                            <td><span class="badge badge-neutral">{{ $entry->process->name }}</span></td>
                            @if($pageType === 'proses' && $selectedProcess && strcasecmp($selectedProcess->name, 'Binding') === 0)
                                <td>{{ $entry->operator?->name ?? '—' }}</td>
                            @endif
                            <td>{{ $entry->buyer?->name ?? '—' }}</td>
                            <td class="text-sm">{{ $entry->part?->code ?? '—' }}</td>
                            <td>{{ $entry->sizeVariant?->code ?? '—' }}</td>
                            <td class="td-num font-bold" style="color:var(--success)">{{ $entry->good_qty }}</td>
                            <td class="td-num" style="color:var(--warning)">{{ $entry->ng_qty }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $pageType === 'proses' && $selectedProcess && strcasecmp($selectedProcess->name, 'Binding') === 0 ? 8 : 7 }}">
                            <div class="empty-state"><div class="empty-icon">📭</div><p>Belum ada input untuk filter ini.</p></div>
                        </td></tr>
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
        }
    }

    document.querySelector('select[name="spk_id"]')?.addEventListener('change', syncProductionMasterFields);
    document.querySelector('[data-custom-buyer-select]')?.addEventListener('change', syncProductionMasterFields);
    syncProductionMasterFields();
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
