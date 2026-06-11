@extends('production.layout', ['title' => 'SPK Management', 'subtitle' => 'Daftar Surat Perintah Kerja'])

@section('topbar-actions')
    <a class="link-btn link-btn-primary" href="/spk/create">＋ Buat SPK Baru</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">
        <h2>Daftar SPK</h2>
        <span class="badge badge-neutral">{{ $spks->count() }} SPK</span>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No SPK</th>
                        <th>Tanggal SPK</th>
                        <th>Dept</th>
                        <th>Month</th>
                        <th>Buyer</th>
                        <th>PO</th>
                        <th>Item</th>
                        <th>Style</th>
                        <th class="td-num">QTY Produksi</th>
                        <th>Remarks</th>
                        <th>Shift</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($spks as $spk)
                    <tr>
                        <td><span class="font-bold">{{ $spk->spk_no }}</span></td>
                        <td class="text-muted text-sm">{{ $spk->spk_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $spk->dept ?? '-' }}</td>
                        <td>{{ $spk->month ?? '-' }}</td>
                        <td>{{ $spk->buyer->name }}</td>
                        <td>{{ $spk->po_no ?? '-' }}</td>
                        <td>{{ $spk->item ?? $spk->part?->name ?? '-' }}</td>
                        <td>{{ $spk->style ?? $spk->sizeVariant?->code ?? '-' }}</td>
                        <td class="td-num font-bold">{{ number_format($spk->target_qty) }}</td>
                        <td>{{ $spk->remarks ?? '-' }}</td>
                        <td>Shift {{ $spk->shift ?? '-' }}</td>
                        <td>
                            @if($spk->status === 'Pending')
                                <span class="badge badge-warning">⏳ Pending</span>
                            @elseif($spk->status === 'Material Prepared')
                                <span class="badge badge-primary">📦 Material Ready</span>
                            @elseif($spk->status === 'In Production')
                                <span class="badge badge-success">⚙️ In Production</span>
                            @else
                                <span class="badge badge-neutral">{{ $spk->status }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="td-actions">
                                <a href="/spk/{{ $spk->id }}" class="link-btn link-btn-secondary btn-sm">Detail</a>
                                <a href="/spk/{{ $spk->id }}/kanban-card" target="_blank" class="link-btn link-btn-primary btn-sm">🖨 Kanban</a>
                                <form method="post" action="/spk/{{ $spk->id }}" onsubmit="return confirm('Hapus SPK {{ $spk->spk_no }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <p>Belum ada SPK. Klik "Buat SPK Baru" untuk memulai.</p>
                        </div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
