@extends('production.layout', ['title' => 'Report Finish Good', 'subtitle' => 'Data packing good berdasarkan tanggal & shift'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/reports/fg?production_date={{ $date }}&shift={{ $shift }}&export=csv">⬇ Export CSV</a>
    <a class="link-btn link-btn-primary" href="/reports/fg/print?production_date={{ $date }}&shift={{ $shift }}" target="_blank">🖨 Print / PDF</a>
@endsection

@section('content')

{{-- Filter Bar --}}
<form class="filter-bar" method="get" action="/reports/fg">
    <div class="field" style="flex-direction:row;align-items:center;gap:8px;margin:0">
        <label style="text-transform:none;font-size:13px;white-space:nowrap;color:var(--muted);font-weight:700">Tanggal</label>
        <input type="date" name="production_date" value="{{ $date }}" style="min-height:38px">
    </div>
    <div class="field" style="flex-direction:row;align-items:center;gap:8px;margin:0">
        <label style="text-transform:none;font-size:13px;white-space:nowrap;color:var(--muted);font-weight:700">Shift</label>
        <select name="shift" style="min-height:38px">
            @foreach($shiftOptions as $key => $opt)
                <option value="{{ $key }}" @selected($shift === $key)>{{ $opt['label'] }} ({{ $opt['time'] }})</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-secondary" type="submit">Filter</button>
</form>

{{-- Summary stats --}}
<div class="grid grid-4" style="margin-bottom:20px">
    <div class="stat">
        <div class="stat-label">Total FG Good</div>
        <div class="stat-value" style="color:var(--success)">{{ number_format($fgReport['total']) }}</div>
        <div class="stat-sub">pcs packing</div>
    </div>
    <div class="stat">
        <div class="stat-label">Jumlah Buyer</div>
        <div class="stat-value">{{ $fgReport['groups']->count() }}</div>
        <div class="stat-sub">buyer aktif</div>
    </div>
    <div class="stat">
        <div class="stat-label">Tanggal</div>
        <div class="stat-value" style="font-size:18px">{{ date('d M Y', strtotime($date)) }}</div>
        <div class="stat-sub">Shift {{ $shift }} ({{ $shiftOptions[$shift]['time'] }})</div>
    </div>
    <div class="stat">
        <div class="stat-label">Variasi Size</div>
        @php $totalRows = $fgReport['groups']->flatten()->count(); @endphp
        <div class="stat-value">{{ $totalRows }}</div>
        <div class="stat-sub">kombinasi buyer-size</div>
    </div>
</div>

{{-- Formal report preview --}}
<div class="panel" style="margin-bottom:20px">
    <div class="panel-header">
        <h2>Laporan Hasil Finish Good</h2>
        <span class="badge badge-primary">Formal</span>
    </div>
    <div class="panel-body no-pad">
        <div style="padding:18px 20px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
            <div>
                <div class="detail-label">Dokumen</div>
                <div class="detail-value">Laporan Packing FG</div>
            </div>
            <div>
                <div class="detail-label">Tanggal Produksi</div>
                <div class="detail-value">{{ date('d M Y', strtotime($date)) }}</div>
            </div>
            <div>
                <div class="detail-label">Shift</div>
                <div class="detail-value">Shift {{ $shift }} ({{ $shiftOptions[$shift]['time'] }})</div>
            </div>
            <div>
                <div class="detail-label">Grand Total</div>
                <div class="detail-value">{{ number_format($fgReport['total']) }} pcs</div>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:64px">No</th>
                    <th>Buyer</th>
                    <th>Size Code</th>
                    <th class="td-num">Good Qty (pcs)</th>
                    <th class="td-num">Subtotal Buyer</th>
                </tr>
            </thead>
            <tbody>
            @php $rowNo = 1; @endphp
            @forelse($fgReport['groups'] as $buyer => $items)
                @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $rowNo++ }}</td>
                        <td class="font-bold">{{ $buyer }}</td>
                        <td>{{ $item->size_code }}</td>
                        <td class="td-num">{{ number_format((int) $item->good_qty) }}</td>
                        <td class="td-num">{{ $loop->first ? number_format($items->sum('good_qty')) : '' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="5"><div class="empty-state"><p>Belum ada data packing good untuk filter ini.</p></div></td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc">
                    <td colspan="3" class="font-bold">GRAND TOTAL FINISH GOOD</td>
                    <td class="td-num font-bold">{{ number_format($fgReport['total']) }}</td>
                    <td class="td-num font-bold">pcs</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<details style="margin-bottom:20px">
    <summary style="cursor:pointer;font-weight:700;color:var(--primary)">Lihat format chat operasional</summary>
    <pre style="margin-top:12px;white-space:pre-wrap;font-family:inherit;font-size:14px;line-height:1.65;background:#f8fafc;border:1px solid var(--line);border-radius:var(--radius-sm);padding:16px;color:var(--ink)">{{ $fgReport['chat'] }}</pre>
</details>

{{-- Detail table --}}
<div class="panel">
    <div class="panel-header">
        <h2>Detail Packing Good per Buyer</h2>
        <span class="badge badge-success">{{ $fgReport['total'] }} pcs total</span>
    </div>
    <div class="panel-body no-pad">
        @forelse($fgReport['groups'] as $buyer => $items)
            <div style="border-bottom:2px solid var(--line);padding:0">
                <div style="background:#f8fafc;padding:12px 20px;font-weight:800;font-size:13px;display:flex;justify-content:space-between;align-items:center">
                    <span style="display:flex;align-items:center;gap:8px"><span style="font-size:16px">👤</span>{{ $buyer }}</span>
                    <span class="badge badge-success">{{ number_format($items->sum('good_qty')) }} pcs</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="padding-left:32px">Size Code</th>
                            <th class="td-num">Good Qty</th>
                            <th class="td-num">%</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td style="padding-left:32px;font-weight:700">{{ $item->size_code }}</td>
                            <td class="td-num" style="color:var(--success);font-size:16px;font-weight:800">{{ number_format((int) $item->good_qty) }}</td>
                            <td class="td-num text-muted">
                                {{ $fgReport['total'] > 0 ? round($item->good_qty / $fgReport['total'] * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>Belum ada data packing good untuk filter ini.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
