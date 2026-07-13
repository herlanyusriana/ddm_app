@extends('production.layout', ['title' => 'Input Hasil Rework', 'subtitle' => 'Pencatatan bongkaran dan pengurangan hutang rework'])

@section('topbar-actions')
<a class="link-btn link-btn-success" href="/rework-results-export?date={{ $date }}">Export Excel</a>
@if($results->isNotEmpty())
    <a class="link-btn link-btn-secondary" href="/rework-results/{{ $results->first()->id }}/additional-print?date={{ $date }}" target="_blank">Form Additional Terakhir</a>
@endif
<form class="filter-bar" method="get" style="margin:0"><input type="date" name="date" value="{{ $date }}"><button class="btn btn-secondary btn-sm">Filter</button></form>
@endsection

@section('content')
<div class="grid grid-2">
<section class="panel">
    <div class="panel-header"><h2>{{ $editResult ? 'Edit Hasil Rework' : 'Form Bongkaran' }}</h2></div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="{{ $editResult ? '/rework-results/'.$editResult->id : '/rework-results' }}">
            @csrf @if($editResult) @method('PUT') @endif
            <div class="field">
                <label>Sumber Reject / Style</label>
                <select data-rework-source-select required>
                    <option value="">— Pilih sumber reject —</option>
                    <optgroup label="Reject Produksi">
                        @foreach($productionSources as $source)
                            <option value="production:{{ $source->id }}" @selected(old('production_entry_id', $editResult?->production_entry_id) == $source->id)>
                                {{ $source->buyer?->code }} / {{ $source->sizeVariant?->code }} · {{ $source->process?->name }} · Sisa {{ $source->remaining_rework }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Reject Binding">
                        @foreach($bindingSources as $source)
                            <option value="binding:{{ $source->id }}" @selected(old('binding_reject_stock_id', $editResult?->binding_reject_stock_id) == $source->id)>
                                {{ $source->buyer?->code }} / {{ $source->sizeVariant?->code }} · Reject Binding · Sisa {{ $source->remaining_rework }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
                <input type="hidden" name="production_entry_id" value="{{ old('production_entry_id', $editResult?->production_entry_id) }}" data-production-entry-source>
                <input type="hidden" name="binding_reject_stock_id" value="{{ old('binding_reject_stock_id', $editResult?->binding_reject_stock_id) }}" data-binding-stock-source>
            </div>
            <div class="field"><label>Tanggal</label><input type="date" name="result_date" value="{{ old('result_date', $editResult?->result_date?->toDateString() ?? $date) }}" required></div>
            <div class="form-row-2">
                <div class="field"><label>Bagian</label><select name="component" required>@foreach($components as $component)<option value="{{ $component }}" @selected(old('component', $editResult?->component) === $component)>{{ $component }}</option>@endforeach</select></div>
                <div class="field"><label>Qty</label><input type="number" min="1" name="qty" value="{{ old('qty', $editResult?->qty ?? 1) }}" required></div>
            </div>
            <div class="field"><label>Operator</label><input name="operator_search" list="rework-operators" value="{{ $editResult?->operator?->operator_code }} · {{ $editResult?->operator?->name }}" data-rework-operator-search placeholder="Ketik nomor atau nama..." required><input type="hidden" name="operator_id" value="{{ old('operator_id', $editResult?->operator_id) }}" data-rework-operator-id><datalist id="rework-operators">@foreach($operators as $operator)<option value="{{ $operator->operator_code }} · {{ $operator->name }}" data-id="{{ $operator->id }}"></option>@endforeach</datalist></div>
            <div class="field"><label>Keterangan Reject</label><input name="reject_notes" value="{{ old('reject_notes', $editResult?->reject_notes) }}" placeholder="Jahit ulang / SOM / jebol..." required></div>
            @if($editResult)
                <button class="btn btn-primary">Simpan Perubahan</button>
                <a class="btn btn-secondary" href="/rework-results?date={{ $date }}">Batal</a>
            @else
                <div class="form-row-2">
                    <button class="btn btn-primary" type="submit" name="after_save" value="print">Simpan & Lanjut Print</button>
                    <button class="btn btn-secondary" type="submit" name="after_save" value="later">Simpan Saja / Nanti</button>
                </div>
                <div class="field-hint">Kalau pilih Nanti, klik tombol Form Additional per item di tabel Hasil Rework saat mau dilanjutkan.</div>
            @endif
        </form>
    </div>
</section>
<section class="panel">
    <div class="panel-header"><h2>Hasil Rework</h2><span class="badge badge-neutral">{{ $results->count() }} records</span></div>
    <div class="table-wrap"><table><thead><tr><th>Style</th><th>Bagian</th><th>Qty</th><th>Operator</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
    @forelse($results as $result)<tr><td>{{ $result->productionEntry?->buyer?->code ?? $result->bindingRejectStock?->buyer?->code }} / {{ $result->productionEntry?->sizeVariant?->code ?? $result->bindingRejectStock?->sizeVariant?->code }} <span class="badge badge-neutral">{{ $result->productionEntry ? 'Reject Produksi' : 'Reject Binding' }}</span></td><td>{{ $result->component }}</td><td>{{ $result->qty }}</td><td>{{ $result->operator?->operator_code }} · {{ $result->operator?->name }}</td><td>{{ $result->reject_notes }}</td><td><div style="display:flex;gap:6px;flex-wrap:wrap"><a class="btn btn-primary btn-sm" href="/rework-results/{{ $result->id }}/additional-print?date={{ $date }}" target="_blank">Lanjut Form Additional</a><a class="btn btn-secondary btn-sm" href="/rework-results/{{ $result->id }}/edit?date={{ $date }}">Edit</a><form method="post" action="/rework-results/{{ $result->id }}" onsubmit="return confirm('Hapus hasil rework?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form></div></td></tr>
    @empty<tr><td colspan="6"><div class="empty-state"><p>Belum ada hasil rework.</p></div></td></tr>@endforelse
    </tbody></table></div>
</section>
</div>
<script>
const reworkOperatorSearch=document.querySelector('[data-rework-operator-search]');
const reworkOperatorId=document.querySelector('[data-rework-operator-id]');
const reworkOperatorOptions=Array.from(document.querySelectorAll('#rework-operators option'));
reworkOperatorSearch?.addEventListener('input',()=>{const selected=reworkOperatorOptions.find(option=>option.value===reworkOperatorSearch.value);reworkOperatorId.value=selected?.dataset.id||'';reworkOperatorSearch.setCustomValidity(selected?'':'Pilih operator dari suggestion.');});
const reworkSourceSelect=document.querySelector('[data-rework-source-select]');
const productionEntrySource=document.querySelector('[data-production-entry-source]');
const bindingStockSource=document.querySelector('[data-binding-stock-source]');
function syncReworkSource(){const [type,id]=(reworkSourceSelect?.value||'').split(':');productionEntrySource.value=type==='production'?id||'':'';bindingStockSource.value=type==='binding'?id||'':'';}
if(reworkSourceSelect&&!reworkSourceSelect.value){if(productionEntrySource.value){reworkSourceSelect.value='production:'+productionEntrySource.value;}else if(bindingStockSource.value){reworkSourceSelect.value='binding:'+bindingStockSource.value;}}
reworkSourceSelect?.addEventListener('change',syncReworkSource);
syncReworkSource();
</script>
@endsection
