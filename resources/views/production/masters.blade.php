@extends('production.layout', ['title' => 'Master Data'])

@section('content')
@php
    $tabs = [
        'buyers' => ['label' => 'Buyer Master', 'url' => '/masters/buyers'],
        'parts' => ['label' => 'Part Master', 'url' => '/masters/parts'],
        'sizes' => ['label' => 'Size / Code Master', 'url' => '/masters/sizes'],
        'processes' => ['label' => 'Process Master', 'url' => '/masters/processes'],
    ];
@endphp

<section class="topbar">
    <div class="title">
        <h1>{{ $tabs[$section]['label'] ?? 'Master Data' }}</h1>
        <p>Master data dipisah per menu supaya admin tidak input di layar yang numpuk.</p>
    </div>
</section>

@if($section === 'buyers')
    <section class="grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Daftar Buyer</h2>
                <a class="link-btn primary" href="/masters/buyers/create">Tambah Buyer Baru</a>
            </div>
            <table>
                <thead><tr><th>Kode</th><th>Nama</th><th></th></tr></thead>
                <tbody>
                @forelse($buyers as $buyer)
                    <tr>
                        <td>{{ $buyer->code }}</td>
                        <td>{{ $buyer->name }}</td>
                        <td>
                            <form method="post" action="/masters/buyers/{{ $buyer->id }}" onsubmit="return confirm('Hapus buyer {{ $buyer->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada buyer.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </section>
@elseif($section === 'parts')
    <section class="grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Daftar Part</h2>
                <div class="td-actions">
                    <a class="link-btn link-btn-secondary" href="/masters/parts/export">Export Excel</a>
                    <a class="link-btn link-btn-secondary" href="/masters/parts/import">Import Excel</a>
                    <a class="link-btn link-btn-primary" href="/masters/parts/create">Tambah Part Baru</a>
                </div>
            </div>
            <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Buyer</th><th>Kategori</th><th>Code</th><th>Name</th><th>Spec</th><th>W</th><th>D</th><th>H</th><th>CBM/Unit</th><th>Net</th><th>Gross</th><th>Pack/Box</th><th>Item No.</th><th>Goods Description</th><th></th></tr></thead>
                <tbody>
                @forelse($parts as $part)
                    <tr>
                        <td>{{ $part->buyer?->name ?? '-' }}</td>
                        <td>{{ $part->classification === 'FG' ? 'Finish Good' : $part->classification }}</td>
                        <td>{{ $part->code }}</td>
                        <td>{{ $part->name }}</td>
                        <td>{{ $part->spec ?? '-' }}</td>
                        <td>{{ $part->width_cm ?? '-' }}</td>
                        <td>{{ $part->depth_cm ?? '-' }}</td>
                        <td>{{ $part->height_cm ?? '-' }}</td>
                        <td>{{ $part->cbm_per_unit ?? '-' }}</td>
                        <td>{{ $part->net_weight_pc ?? '-' }}</td>
                        <td>{{ $part->gross_weight_pc ?? '-' }}</td>
                        <td>{{ $part->package_box ?? '-' }}</td>
                        <td>{{ $part->item_no ?? '-' }}</td>
                        <td>{{ $part->goods_description ?? '-' }}</td>
                        <td>
                            <form method="post" action="/masters/parts/{{ $part->id }}" onsubmit="return confirm('Hapus part {{ $part->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14">Belum ada part.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </article>
    </section>
@elseif($section === 'sizes')
    <section class="grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Daftar Size</h2>
                <div class="td-actions">
                    <a class="link-btn link-btn-secondary" href="/masters/sizes/export">Export Excel</a>
                    <a class="link-btn link-btn-secondary" href="/masters/sizes/import">Import Excel</a>
                    <a class="link-btn link-btn-primary" href="/masters/sizes/create">Tambah Size Baru</a>
                </div>
            </div>
            <table>
                <thead><tr><th>Kode</th><th>Nama</th><th></th></tr></thead>
                <tbody>
                @forelse($sizes as $size)
                    <tr>
                        <td>{{ $size->code }}</td>
                        <td>{{ $size->name }}</td>
                        <td>
                            <form method="post" action="/masters/sizes/{{ $size->id }}" onsubmit="return confirm('Hapus size {{ $size->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada size.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </section>
@elseif($section === 'mappings')
    <section class="grid two">
        <article class="panel">
            <div class="panel-head"><h2>Buyer Part Mapping</h2><span class="pill">Relasi buyer-part-size</span></div>
            <form class="compact-form" method="post" action="/masters/mappings">
                @csrf
                <label>Buyer
                    <select name="buyer_id" required>
                        @foreach($buyers as $buyer)<option value="{{ $buyer->id }}">{{ $buyer->name }}</option>@endforeach
                    </select>
                </label>
                <label>Part
                    <select name="part_id" required>
                        @foreach($parts as $part)<option value="{{ $part->id }}">{{ $part->code }} - {{ $part->name }}</option>@endforeach
                    </select>
                </label>
                <label>Size
                    <select name="size_variant_id" required>
                        @foreach($sizes as $size)<option value="{{ $size->id }}">{{ $size->code }}</option>@endforeach
                    </select>
                </label>
                <button class="btn primary">Tambah Mapping</button>
            </form>
        </article>
        <article class="panel">
            <div class="panel-head"><h2>Daftar Mapping</h2></div>
            <table>
                <thead><tr><th>Buyer</th><th>Part</th><th>Size</th></tr></thead>
                <tbody>
                @forelse($mappings as $map)
                    <tr><td>{{ $map->buyer->name }}</td><td>{{ $map->part->code }}</td><td>{{ $map->sizeVariant->code }}</td></tr>
                @empty
                    <tr><td colspan="3">Belum ada mapping.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </section>
@elseif($section === 'processes')
    <section class="panel">
        <div class="panel-head"><h2>Process Master</h2><span class="pill">Alur proses produksi</span></div>
        <table>
            <thead><tr><th>Urutan</th><th>Proses</th><th>Input Good/NG</th><th>FG</th></tr></thead>
            <tbody>
            @forelse($processes as $process)
                <tr>
                    <td>{{ $process->sort_order }}</td>
                    <td>{{ $process->name }}</td>
                    <td>{{ $process->is_input_process ? 'Ya' : 'Tidak' }}</td>
                    <td>{{ $process->is_fg_process ? 'Ya' : 'Tidak' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada proses.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endif
@endsection
