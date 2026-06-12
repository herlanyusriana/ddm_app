@php
    $labels = [
        'buyers' => 'Buyer Master',
        'parts' => 'Part Master',
        'sizes' => 'Size Master',
        'processes' => 'Process Master',
        'mappings' => 'Buyer Part Mapping',
    ];

    $title = $labels[$section] ?? 'Master Data';
@endphp

@extends('production.layout', [
    'title' => $title,
    'subtitle' => 'Kelola master data produksi dengan list, import, export, dan delete yang terpisah.'
])

@section('topbar-actions')
    @if($section === 'buyers')
        <a class="link-btn link-btn-primary" href="/masters/buyers/create">Tambah Buyer Baru</a>
    @elseif($section === 'parts')
        <a class="link-btn link-btn-secondary" href="/masters/parts/export">Export Excel</a>
        <a class="link-btn link-btn-secondary" href="/masters/parts/import">Import Excel</a>
        <a class="link-btn link-btn-primary" href="/masters/parts/create">Tambah Part Baru</a>
    @elseif($section === 'sizes')
        <a class="link-btn link-btn-secondary" href="/masters/sizes/export">Export Excel</a>
        <a class="link-btn link-btn-secondary" href="/masters/sizes/import">Import Excel</a>
        <a class="link-btn link-btn-primary" href="/masters/sizes/create">Tambah Size Baru</a>
    @endif
@endsection

@section('content')
<style>
    .master-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .master-metric { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 14px 16px; }
    .master-metric span { color: var(--muted); display: block; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .master-metric strong { display: block; font-size: 24px; font-weight: 900; letter-spacing: -.03em; margin-top: 4px; }
    .master-toolbar { align-items: center; background: #f8fafc; border-bottom: 1px solid var(--line); display: flex; gap: 10px; justify-content: space-between; padding: 12px 16px; }
    .master-search { max-width: 360px; position: relative; width: 100%; }
    .master-search input { background: #fff; border: 1.5px solid var(--line); border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; min-height: 38px; padding: 8px 12px 8px 34px; width: 100%; }
    .master-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); outline: none; }
    .master-search::before { color: var(--muted); content: "⌕"; font-size: 18px; left: 12px; position: absolute; top: 5px; }
    .master-count { color: var(--muted); font-size: 12px; font-weight: 800; white-space: nowrap; }
    .master-table-wrap { max-height: calc(100vh - 300px); min-height: 260px; overflow: auto; }
    .master-table { min-width: 100%; }
    .master-table thead th { padding: 10px 14px; position: sticky; top: 0; z-index: 2; }
    .master-table tbody td { font-size: 13px; padding: 11px 14px; }
    .master-table tbody tr:nth-child(even) td { background: #fcfdff; }
    .master-table tbody tr:hover td { background: var(--primary-soft); }
    .master-primary { color: var(--ink); font-weight: 850; }
    .master-secondary { color: var(--muted); display: block; font-size: 11px; font-weight: 650; margin-top: 2px; white-space: normal; }
    .master-code { color: var(--primary-dark); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 850; }
    .master-actions-cell { background: inherit; position: sticky; right: 0; z-index: 1; }
    .master-empty { color: var(--muted); padding: 34px; text-align: center; }
    .master-empty strong { color: var(--ink); display: block; font-size: 15px; margin-bottom: 4px; }
    .master-chip { align-items: center; border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 850; line-height: 1; padding: 6px 10px; white-space: nowrap; }
    .chip-fg { background: #eff6ff; color: #1d4ed8; }
    .chip-wip { background: #fffbeb; color: #b45309; }
    .chip-rm { background: #f0fdf4; color: #15803d; }
    .chip-neutral { background: #f1f5f9; color: #475569; }
    .master-actions { display: flex; gap: 6px; justify-content: flex-end; }
    @media (max-width: 900px) {
        .master-summary { grid-template-columns: 1fr 1fr; }
        .master-toolbar { align-items: stretch; flex-direction: column; }
        .master-search { max-width: none; }
        .master-table-wrap { max-height: none; }
    }
</style>

@if($section === 'buyers')
    <section class="master-summary">
        <div class="master-metric"><span>Total Buyer</span><strong>{{ $buyers->count() }}</strong></div>
        <div class="master-metric"><span>Aktif</span><strong>{{ $buyers->where('is_active', true)->count() }}</strong></div>
        <div class="master-metric"><span>Menu</span><strong>Buyer</strong></div>
        <div class="master-metric"><span>Input</span><strong>Manual</strong></div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Daftar Buyer</h2>
            <span class="badge badge-neutral">{{ $buyers->count() }} data</span>
        </div>
        <div class="master-toolbar">
            <div class="master-search"><input data-master-search placeholder="Cari kode atau nama buyer..."></div>
            <div class="master-count">Buyer master</div>
        </div>
        <div class="master-table-wrap">
            <table class="master-table">
                <thead><tr><th>Kode</th><th>Nama Buyer</th><th class="master-actions-cell">Aksi</th></tr></thead>
                <tbody data-master-body>
                @forelse($buyers as $buyer)
                    <tr data-master-row="{{ strtolower($buyer->code.' '.$buyer->name) }}">
                        <td><span class="master-code">{{ $buyer->code }}</span></td>
                        <td><span class="master-primary">{{ $buyer->name }}</span></td>
                        <td class="master-actions-cell">
                            <form class="master-actions" method="post" action="/masters/buyers/{{ $buyer->id }}" onsubmit="return confirm('Hapus buyer {{ $buyer->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3"><div class="master-empty"><strong>Belum ada buyer.</strong>Tambah buyer baru dari tombol kanan atas.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@elseif($section === 'parts')
    <section class="master-summary">
        <div class="master-metric"><span>Total Part</span><strong>{{ $parts->count() }}</strong></div>
        <div class="master-metric"><span>Finish Good</span><strong>{{ $parts->where('classification', 'FG')->count() }}</strong></div>
        <div class="master-metric"><span>WIP</span><strong>{{ $parts->where('classification', 'WIP')->count() }}</strong></div>
        <div class="master-metric"><span>RM</span><strong>{{ $parts->where('classification', 'RM')->count() }}</strong></div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Daftar Part</h2>
            <span class="badge badge-neutral">{{ $parts->count() }} data</span>
        </div>
        <div class="master-toolbar">
            <div class="master-search"><input data-master-search placeholder="Cari buyer, code, name, item no..."></div>
            <div class="master-count">Scroll kanan untuk kolom detail</div>
        </div>
        <div class="master-table-wrap">
            <table class="master-table" style="min-width:1280px">
                <thead>
                    <tr>
                        <th>Buyer</th><th>Kategori</th><th>Code</th><th>Name</th><th>Spec</th><th>UOM</th>
                        <th class="td-num">W</th><th class="td-num">D</th><th class="td-num">H</th>
                        <th class="td-num">CBM/Unit</th><th class="td-num">Net</th><th class="td-num">Gross</th>
                        <th class="td-num">Pack/Box</th><th>Item No.</th><th>Goods Description</th><th class="master-actions-cell">Aksi</th>
                    </tr>
                </thead>
                <tbody data-master-body>
                @forelse($parts as $part)
                    @php
                        $chipClass = ['FG' => 'chip-fg', 'WIP' => 'chip-wip', 'RM' => 'chip-rm'][$part->classification] ?? 'chip-neutral';
                        $classification = $part->classification === 'FG' ? 'Finish Good' : $part->classification;
                    @endphp
                    <tr data-master-row="{{ strtolower(($part->buyer?->name ?? '').' '.$part->classification.' '.$part->code.' '.$part->name.' '.$part->spec.' '.$part->item_no.' '.$part->goods_description) }}">
                        <td><span class="master-primary">{{ $part->buyer?->name ?? 'Umum' }}</span><span class="master-secondary">{{ $part->buyer?->code ?? 'Semua buyer' }}</span></td>
                        <td><span class="master-chip {{ $chipClass }}">{{ $classification }}</span></td>
                        <td><span class="master-code">{{ $part->code }}</span></td>
                        <td><span class="master-primary">{{ $part->name }}</span></td>
                        <td>{{ $part->spec ?? '-' }}</td>
                        <td>{{ $part->uom ?? '-' }}</td>
                        <td class="td-num">{{ $part->width_cm ?? '-' }}</td>
                        <td class="td-num">{{ $part->depth_cm ?? '-' }}</td>
                        <td class="td-num">{{ $part->height_cm ?? '-' }}</td>
                        <td class="td-num">{{ $part->cbm_per_unit ?? '-' }}</td>
                        <td class="td-num">{{ $part->net_weight_pc ?? '-' }}</td>
                        <td class="td-num">{{ $part->gross_weight_pc ?? '-' }}</td>
                        <td class="td-num">{{ $part->package_box ?? '-' }}</td>
                        <td><span class="master-code">{{ $part->item_no ?? '-' }}</span></td>
                        <td><span class="master-secondary" style="max-width:260px">{{ $part->goods_description ?? '-' }}</span></td>
                        <td class="master-actions-cell">
                            <form class="master-actions" method="post" action="/masters/parts/{{ $part->id }}" onsubmit="return confirm('Hapus part {{ $part->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="16"><div class="master-empty"><strong>Belum ada part.</strong>Import dari Excel atau tambah manual dari tombol kanan atas.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@elseif($section === 'sizes')
    <section class="master-summary">
        <div class="master-metric"><span>Total Size</span><strong>{{ $sizes->count() }}</strong></div>
        <div class="master-metric"><span>Menu</span><strong>Size</strong></div>
        <div class="master-metric"><span>Import</span><strong>XLSX</strong></div>
        <div class="master-metric"><span>Export</span><strong>Excel</strong></div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Daftar Size</h2>
            <span class="badge badge-neutral">{{ $sizes->count() }} data</span>
        </div>
        <div class="master-toolbar">
            <div class="master-search"><input data-master-search placeholder="Cari kode atau nama size..."></div>
            <div class="master-count">Size / code master</div>
        </div>
        <div class="master-table-wrap">
            <table class="master-table">
                <thead><tr><th>Kode</th><th>Nama Size</th><th class="master-actions-cell">Aksi</th></tr></thead>
                <tbody data-master-body>
                @forelse($sizes as $size)
                    <tr data-master-row="{{ strtolower($size->code.' '.$size->name) }}">
                        <td><span class="master-code">{{ $size->code }}</span></td>
                        <td><span class="master-primary">{{ $size->name ?? '-' }}</span></td>
                        <td class="master-actions-cell">
                            <form class="master-actions" method="post" action="/masters/sizes/{{ $size->id }}" onsubmit="return confirm('Hapus size {{ $size->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3"><div class="master-empty"><strong>Belum ada size.</strong>Import dari Excel atau tambah manual dari tombol kanan atas.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@elseif($section === 'mappings')
    <section class="panel">
        <div class="panel-header"><h2>Buyer Part Mapping</h2><span class="badge badge-neutral">{{ $mappings->count() }} data terakhir</span></div>
        <div class="panel-body">
            <div class="empty-state" style="padding:24px">
                <p>Mapping buyer-part-size sedang tidak dipakai di flow terbaru. Part Master sudah bisa umum atau khusus buyer.</p>
            </div>
        </div>
    </section>
@elseif($section === 'processes')
    <section class="master-summary">
        <div class="master-metric"><span>Total Proses</span><strong>{{ $processes->count() }}</strong></div>
        <div class="master-metric"><span>Input WIP</span><strong>{{ $processes->where('is_input_process', true)->where('is_fg_process', false)->count() }}</strong></div>
        <div class="master-metric"><span>Finish Good</span><strong>{{ $processes->where('is_fg_process', true)->count() }}</strong></div>
        <div class="master-metric"><span>Warehouse</span><strong>{{ $processes->where('is_input_process', false)->count() }}</strong></div>
    </section>

    <section class="panel">
        <div class="panel-header"><h2>Process Master</h2><span class="badge badge-neutral">Alur produksi</span></div>
        <div class="master-table-wrap">
            <table class="master-table">
                <thead><tr><th>Urutan</th><th>Proses</th><th>Input Good/NG</th><th>FG</th></tr></thead>
                <tbody>
                @forelse($processes as $process)
                    <tr>
                        <td><span class="master-code">{{ $process->sort_order }}</span></td>
                        <td><span class="master-primary">{{ $process->name }}</span></td>
                        <td><span class="master-chip {{ $process->is_input_process ? 'chip-fg' : 'chip-neutral' }}">{{ $process->is_input_process ? 'Ya' : 'Tidak' }}</span></td>
                        <td><span class="master-chip {{ $process->is_fg_process ? 'chip-rm' : 'chip-neutral' }}">{{ $process->is_fg_process ? 'Ya' : 'Tidak' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="master-empty"><strong>Belum ada proses.</strong>Jalankan seeder proses produksi.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif

<script>
    document.querySelectorAll('[data-master-search]').forEach((input) => {
        const scope = input.closest('.panel');
        const rows = scope ? scope.querySelectorAll('[data-master-row]') : [];

        input.addEventListener('input', () => {
            const value = input.value.trim().toLowerCase();

            rows.forEach((row) => {
                row.style.display = row.dataset.masterRow.includes(value) ? '' : 'none';
            });
        });
    });
</script>
@endsection
