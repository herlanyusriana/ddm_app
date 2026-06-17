@extends('production.layout', ['title' => 'Detail SPK — ' . $spk->spk_no, 'subtitle' => 'Informasi lengkap Surat Perintah Kerja'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/spk">← Kembali</a>
    <a class="link-btn link-btn-secondary" href="/spk/{{ $spk->id }}/print" target="_blank">Print SPK</a>
    <a class="link-btn link-btn-primary" href="/spk/{{ $spk->id }}/kanban-card" target="_blank">Kanban Unit</a>
@endsection

@section('content')
<div class="grid grid-2" style="align-items:start">

    {{-- Info Panel --}}
    <div class="panel">
        <div class="panel-header">
            <h2>{{ $spk->spk_no }}</h2>
            @if($spk->status === 'Pending')
                <span class="badge badge-warning">⏳ Pending</span>
            @elseif($spk->status === 'Material Prepared')
                <span class="badge badge-primary">📦 Material Ready</span>
            @else
                <span class="badge badge-success">{{ $spk->status }}</span>
            @endif
        </div>
        <div class="panel-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Nomor SPK</span>
                    <span class="detail-value">{{ $spk->spk_no }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tgl Terbit</span>
                    <span class="detail-value">{{ $spk->spk_date?->format('d M Y') ?? $spk->created_at->format('d M Y') }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Dept</span>
                    <span class="detail-value">{{ $spk->dept ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Month</span>
                    <span class="detail-value">{{ $spk->month ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Buyer</span>
                    <span class="detail-value">{{ $spk->buyer->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">PO</span>
                    <span class="detail-value">{{ $spk->po_no ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Item</span>
                    <span class="detail-value">{{ $spk->item ?? $spk->part?->name ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Style</span>
                    <span class="detail-value">{{ $spk->style ?? $spk->sizeVariant?->code ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Target Qty</span>
                    <span class="detail-value" style="font-size:22px;color:var(--primary)">{{ number_format($spk->target_qty) }} pcs</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Remarks</span>
                    <span class="detail-value">{{ $spk->remarks ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Shift</span>
                    <span class="detail-value">Shift {{ $spk->shift ?? '-' }}</span>
                </div>
                @if($spk->notes)
                <div class="detail-item" style="grid-column:span 2">
                    <span class="detail-label">Catatan</span>
                    <span class="detail-value" style="font-size:14px">{{ $spk->notes }}</span>
                </div>
                @endif
            </div>

            @if($spk->status === 'Pending')
            <div class="divider"></div>
            <form method="post" action="/warehouse/spk/{{ $spk->id }}/prepare" onsubmit="return confirm('Konfirmasi material sudah disiapkan?')">
                @csrf
                <button class="btn btn-success btn-full">📦 Tandai Material Disiapkan</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Progress panel --}}
    <div class="panel">
        <div class="panel-header">
            <h2>Progress Produksi</h2>
            <span class="badge badge-neutral">Realtime</span>
        </div>
        <div class="panel-body">
            @php
                $totalGood = $spk->entries->sum('good_qty');
                $totalReject = $spk->entries->sum('ng_qty');
                $pct = $spk->target_qty > 0 ? min(100, round($totalGood / $spk->target_qty * 100)) : 0;
            @endphp

            <div class="grid grid-2" style="gap:12px;margin-bottom:20px">
                <div class="stat" style="padding:14px">
                    <div class="stat-label" style="color:var(--success)">Good</div>
                    <div class="stat-value" style="font-size:26px;color:var(--success)">{{ number_format($totalGood) }}</div>
                </div>
                <div class="stat" style="padding:14px">
                    <div class="stat-label" style="color:var(--warning)">Reject / Hutang Rework</div>
                    <div class="stat-value" style="font-size:26px;color:var(--warning)">{{ number_format($totalReject) }}</div>
                </div>
            </div>

            <div class="field" style="gap:8px">
                <div class="flex" style="justify-content:space-between;margin-bottom:4px">
                    <span class="text-sm text-muted font-bold">Progress Good vs Target</span>
                    <span class="text-sm font-bold" style="color:var(--primary)">{{ $pct }}%</span>
                </div>
                <div style="height:12px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                    <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#2563eb,#16a34a);border-radius:999px;transition:width .5s"></div>
                </div>
                <div class="text-muted text-sm" style="margin-top:4px">{{ number_format($totalGood) }} / {{ number_format($spk->target_qty) }} pcs</div>
            </div>

            <div class="divider"></div>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Proses</th><th>Shift</th><th class="td-num">Good</th><th class="td-num">Reject</th></tr></thead>
                    <tbody>
                    @forelse($spk->entries->sortByDesc('id') as $entry)
                        <tr>
                            <td>{{ $entry->process->name }}</td>
                            <td>Shift {{ $entry->shift }}</td>
                            <td class="td-num" style="color:var(--success)">{{ $entry->good_qty }}</td>
                            <td class="td-num" style="color:var(--warning)">{{ $entry->ng_qty }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state" style="padding:24px"><p>Belum ada input produksi untuk SPK ini.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
