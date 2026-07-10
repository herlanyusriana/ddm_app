@extends('production.layout', ['title' => 'Data Reject Binding', 'subtitle' => 'Stock card reject berdasarkan Buyer dan Style'])

@section('topbar-actions')
    <a class="link-btn link-btn-success" href="/binding-reject-stock-export?date={{ $date }}">Export Excel</a>
    <form class="filter-bar" method="get" style="margin:0">
        <input type="date" name="date" value="{{ $date }}">
        <button class="btn btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
<style>
    .stock-entry-panel {
        background: #f8fafc;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        display: grid;
        gap: 12px;
        padding: 12px;
    }

    .stock-entry-row {
        align-items: end;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        display: grid;
        gap: 10px;
        grid-template-columns: 1.1fr 1.1fr .55fr .55fr .8fr auto;
        padding: 12px;
    }

    .stock-entry-row .field label {
        font-size: 10px;
    }

    .stock-entry-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
    }

    @media (max-width: 760px) {
        .stock-entry-row {
            grid-template-columns: 1fr;
        }

        .stock-entry-row .btn {
            width: 100%;
        }
    }
</style>

<div class="grid grid-2">
    <section class="panel">
        <div class="panel-header"><h2>{{ $editRecord ? 'Edit Transaksi' : 'Input Stock Card' }}</h2></div>
        <div class="panel-body">
            <form class="form-grid" method="post" action="{{ $editRecord ? '/binding-reject-stock/'.$editRecord->id : '/binding-reject-stock' }}">
                @csrf
                @if($editRecord) @method('PUT') @endif
                <div class="form-row-2">
                    <div class="field"><label>Tanggal</label><input type="date" name="transaction_date" value="{{ old('transaction_date', $editRecord?->transaction_date?->toDateString() ?? $date) }}" required></div>
                    <div class="field"><label>Jam</label><input type="time" name="transaction_time" value="{{ old('transaction_time', $editRecord?->transaction_time ? substr($editRecord->transaction_time, 0, 5) : now('Asia/Jakarta')->format('H:i')) }}"></div>
                </div>
                <div class="form-row-2">
                    <div class="field"><label>Pallet</label><input name="pallet" value="{{ old('pallet', $editRecord?->pallet) }}"></div>
                    <div class="field"><label>PO / SPK</label><input name="po_no" value="{{ old('po_no', $editRecord?->po_no) }}"></div>
                </div>
                @if($editRecord)
                    <div class="field"><label>Buyer</label><select name="buyer_id" required><option value="">— Pilih Buyer —</option>@foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(old('buyer_id', $editRecord?->buyer_id) == $buyer->id)>{{ $buyer->code }} · {{ $buyer->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Style</label><select name="size_variant_id" required><option value="">— Pilih Style —</option>@foreach($sizes as $size)<option value="{{ $size->id }}" @selected(old('size_variant_id', $editRecord?->size_variant_id) == $size->id)>{{ $size->display_label }}</option>@endforeach</select></div>
                    <div class="form-row-2">
                        <div class="field"><label>IN</label><input type="number" min="0" name="qty_in" value="{{ old('qty_in', $editRecord?->qty_in ?? 0) }}" required></div>
                        <div class="field"><label>OUT</label><input type="number" min="0" name="qty_out" value="{{ old('qty_out', $editRecord?->qty_out ?? 0) }}" required></div>
                    </div>
                    <div class="field"><label>Paraf</label><input name="paraf" value="{{ old('paraf', $editRecord?->paraf) }}"></div>
                @else
                    <div class="stock-entry-panel" data-stock-entry-panel>
                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--muted)">Detail per Buyer / Style</div>
                        <div data-stock-entry-list>
                            <div class="stock-entry-row" data-stock-entry-row>
                                <div class="field"><label>Buyer</label><select name="entries[0][buyer_id]" required data-stock-buyer><option value="">— Pilih Buyer —</option>@foreach($buyers as $buyer)<option value="{{ $buyer->id }}">{{ $buyer->code }} · {{ $buyer->name }}</option>@endforeach</select></div>
                                <div class="field"><label>Style</label><select name="entries[0][size_variant_id]" required data-stock-size><option value="">— Pilih Style —</option>@foreach($sizes as $size)<option value="{{ $size->id }}">{{ $size->display_label }}</option>@endforeach</select></div>
                                <div class="field"><label>IN</label><input type="number" min="0" name="entries[0][qty_in]" value="0" required data-stock-in></div>
                                <div class="field"><label>OUT</label><input type="number" min="0" name="entries[0][qty_out]" value="0" required data-stock-out></div>
                                <div class="field"><label>Paraf</label><input name="entries[0][paraf]" data-stock-paraf></div>
                                <button type="button" class="btn btn-danger btn-sm" data-remove-stock-entry style="display:none">Hapus</button>
                            </div>
                        </div>
                        <div class="stock-entry-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-add-stock-entry>+ Tambah Baris</button>
                            <div class="field-hint" data-stock-entry-summary>Total 0 pcs dari 1 baris.</div>
                        </div>
                    </div>
                @endif
                <button class="btn btn-primary" type="submit">{{ $editRecord ? 'Simpan Perubahan' : 'Simpan Stock Card' }}</button>
                @if($editRecord)<a class="btn btn-secondary" href="/binding-reject-stock?date={{ $date }}">Batal Edit</a>@endif
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header"><h2>Data Reject Binding</h2><span class="badge badge-neutral">{{ date('d M Y', strtotime($date)) }}</span></div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Buyer</th><th>Style</th><th class="td-num">QTY</th></tr></thead>
                    <tbody>
                    @forelse($summary as $group)
                        @foreach($group['styles'] as $style)
                            <tr><td>{{ $group['buyer']?->code ?? $group['buyer']?->name }}</td><td>{{ $style['size']?->code }}</td><td class="td-num">{{ $style['qty'] }}</td></tr>
                        @endforeach
                        <tr style="background:#e5e7eb;font-weight:800"><td colspan="2">{{ $group['buyer']?->code }} TOTAL</td><td class="td-num">{{ $group['styles']->sum('qty') }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><p>Belum ada stock reject Binding.</p></div></td></tr>
                    @endforelse
                    </tbody>
                    <tfoot><tr style="background:#fb923c;font-weight:900"><td colspan="2">GRAND TOTAL</td><td class="td-num">{{ $grandTotal }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </section>
</div>

<section class="panel mt-6">
    <div class="panel-header"><h2>Stock Card s/d {{ date('d M Y', strtotime($date)) }}</h2><span class="badge badge-neutral">{{ $transactions->count() }} records</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Jam</th><th>Pallet</th><th>PO</th><th>Buyer</th><th>Style</th><th>IN</th><th>OUT</th><th>Balance</th><th>Paraf</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($transactions as $row)
                <tr>
                    <td>{{ $row->transaction_time ? substr($row->transaction_time, 0, 5) : '—' }}</td><td>{{ $row->pallet ?? '—' }}</td><td>{{ $row->po_no ?? '—' }}</td>
                    <td>{{ $row->buyer?->code }}</td><td>{{ $row->sizeVariant?->code }}</td><td>{{ $row->qty_in }}</td><td>{{ $row->qty_out }}</td><td class="td-num">{{ $row->balance }}</td><td>{{ $row->paraf ?? '—' }}</td>
                    <td><div style="display:flex;gap:6px"><a class="btn btn-secondary btn-sm" href="/binding-reject-stock/{{ $row->id }}/edit?date={{ $date }}">Edit</a><form method="post" action="/binding-reject-stock/{{ $row->id }}" onsubmit="return confirm('Hapus transaksi ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty-state"><p>Belum ada transaksi sampai tanggal ini.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<script>
    const stockEntryList = document.querySelector('[data-stock-entry-list]');
    const addStockEntryButton = document.querySelector('[data-add-stock-entry]');
    const stockEntrySummary = document.querySelector('[data-stock-entry-summary]');

    function stockEntryRows() {
        return Array.from(document.querySelectorAll('[data-stock-entry-row]'));
    }

    function refreshStockEntries() {
        const rows = stockEntryRows();
        let total = 0;

        rows.forEach((row, index) => {
            row.querySelector('[data-stock-buyer]').name = `entries[${index}][buyer_id]`;
            row.querySelector('[data-stock-size]').name = `entries[${index}][size_variant_id]`;
            row.querySelector('[data-stock-in]').name = `entries[${index}][qty_in]`;
            row.querySelector('[data-stock-out]').name = `entries[${index}][qty_out]`;
            row.querySelector('[data-stock-paraf]').name = `entries[${index}][paraf]`;
            row.querySelector('[data-remove-stock-entry]').style.display = rows.length > 1 ? '' : 'none';
            total += Number(row.querySelector('[data-stock-in]').value || 0) + Number(row.querySelector('[data-stock-out]').value || 0);
        });

        if (stockEntrySummary) {
            stockEntrySummary.textContent = `Total ${total} pcs dari ${rows.length} baris.`;
        }
    }

    addStockEntryButton?.addEventListener('click', () => {
        const firstRow = stockEntryRows()[0];
        const clone = firstRow.cloneNode(true);
        clone.querySelectorAll('select').forEach((select) => select.value = '');
        clone.querySelector('[data-stock-in]').value = 0;
        clone.querySelector('[data-stock-out]').value = 0;
        clone.querySelector('[data-stock-paraf]').value = '';
        stockEntryList.appendChild(clone);
        refreshStockEntries();
    });

    stockEntryList?.addEventListener('click', (event) => {
        if (!event.target.matches('[data-remove-stock-entry]')) {
            return;
        }

        event.target.closest('[data-stock-entry-row]')?.remove();
        refreshStockEntries();
    });

    stockEntryList?.addEventListener('input', refreshStockEntries);
    stockEntryList?.addEventListener('change', refreshStockEntries);
    refreshStockEntries();
</script>
@endsection
