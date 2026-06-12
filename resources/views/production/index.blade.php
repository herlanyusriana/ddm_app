@extends('production.layout', ['title' => $pageTitle, 'subtitle' => 'Input Good, Rework, dan Scrap per proses'])

@section('topbar-actions')
    <form class="filter-bar" method="get" action="" style="margin:0;border:0;background:transparent;padding:0">
        <input type="date" name="production_date" value="{{ $date }}" style="min-height:36px;font-size:13px">
        <select name="shift" style="min-height:36px;font-size:13px">
            @foreach($shiftOptions as $key => $opt)
                <option value="{{ $key }}" @selected($shift === $key)>{{ $opt['label'] }}</option>
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
</style>

<div class="production-input-grid">

    {{-- Input Form --}}
    <div class="panel">
        <div class="panel-header">
            <h2>Form Input</h2>
            <span class="badge badge-neutral">{{ date('d M Y', strtotime($date)) }} · Shift {{ $shift }}</span>
        </div>
        <div class="panel-body">
            <form class="form-grid" method="post" action="/production-entries">
                @csrf
                <input type="hidden" name="production_date" value="{{ $date }}">
                <input type="hidden" name="shift" value="{{ $shift }}">

                <div class="field">
                    <label>SPK / Lot Produksi</label>
                    <select name="spk_id" required>
                        <option value="">— Pilih SPK —</option>
                        @foreach($spks as $spk)
                            <option value="{{ $spk->id }}">
                                {{ $spk->spk_no }} · {{ $spk->buyer?->name }} · {{ $spk->item }} · {{ $spk->style }} · {{ number_format($spk->target_qty) }} pcs
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($pageType === 'hasil')
                <div class="field">
                    <label>Buyer</label>
                    <select name="buyer_id">
                        <option value="">Ikut buyer SPK</option>
                        @foreach($buyers as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Part</label>
                    <select name="part_id" required>
                        <option value="">— Pilih Part —</option>
                        @foreach($parts as $p)<option value="{{ $p->id }}">{{ $p->code }} – {{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Size</label>
                    <select name="size_variant_id" required>
                        <option value="">— Pilih Size —</option>
                        @foreach($sizes as $s)<option value="{{ $s->id }}">{{ $s->code }}</option>@endforeach
                    </select>
                </div>
                <div class="divider"></div>
                @endif

                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:10px">Pilih Proses</div>
                    <div class="processes" style="grid-template-columns:repeat(2,1fr)">
                        @foreach($inputProcesses as $process)
                            <label class="process-label">
                                <input type="radio" name="process_id" value="{{ $process->id }}" required>
                                {{ $process->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:10px">Jumlah Produksi</div>
                    <div class="qty-grid">
                        <div class="qty-box good">
                            <label>✅ Good</label>
                            <input type="number" min="0" name="good_qty" value="0">
                        </div>
                        <div class="qty-box rework">
                            <label>🔧 Rework</label>
                            <input type="number" min="0" name="repairable_qty" value="0">
                        </div>
                        <div class="qty-box scrap">
                            <label>🗑 Scrap</label>
                            <input type="number" min="0" name="scrap_qty" value="0">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label>Catatan (opsional)</label>
                    <input name="notes" placeholder="Tambahan informasi...">
                </div>

                <button class="btn btn-primary btn-full btn-lg" type="submit">Simpan Input Produksi</button>
            </form>
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
                            @if($pageType === 'hasil')<th>Buyer</th><th>Part</th><th>Size</th>@endif
                            <th class="td-num">Good</th>
                            <th class="td-num">Rework</th>
                            <th class="td-num">Scrap</th>
                            <th class="td-num text-muted">Total NG</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td><a class="master-code" href="/spk/{{ $entry->spk_id }}">{{ $entry->spk?->spk_no ?? '—' }}</a></td>
                            <td><span class="badge badge-neutral">{{ $entry->process->name }}</span></td>
                            @if($pageType === 'hasil')
                            <td>{{ $entry->buyer?->name ?? '—' }}</td>
                            <td class="text-sm">{{ $entry->part?->code ?? '—' }}</td>
                            <td>{{ $entry->sizeVariant?->code ?? '—' }}</td>
                            @endif
                            <td class="td-num font-bold" style="color:var(--success)">{{ $entry->good_qty }}</td>
                            <td class="td-num" style="color:var(--warning)">{{ $entry->repairable_qty }}</td>
                            <td class="td-num" style="color:var(--danger)">{{ $entry->scrap_qty }}</td>
                            <td class="td-num text-muted text-sm">{{ $entry->ng_qty }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $pageType === 'hasil' ? 9 : 6 }}">
                            <div class="empty-state"><div class="empty-icon">📭</div><p>Belum ada input untuk filter ini.</p></div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
