<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Report FG — {{ date('d M Y', strtotime($date)) }} Shift {{ $shift }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 24px; }
        .doc-header { display: grid; grid-template-columns: 1fr 2fr 1fr; border: 2px solid #111; margin-bottom: 14px; }
        .doc-cell { border-right: 1px solid #111; min-height: 72px; padding: 10px; display: flex; flex-direction: column; justify-content: center; }
        .doc-cell:last-child { border-right: 0; }
        .company { font-weight: 800; font-size: 14px; }
        .doc-title { text-align: center; font-size: 18px; font-weight: 900; letter-spacing: .04em; }
        .doc-subtitle { text-align: center; margin-top: 4px; font-size: 12px; }
        .doc-meta { font-size: 11px; line-height: 1.7; }
        .info-table { margin-bottom: 14px; }
        .info-table th { width: 16%; background: #f3f4f6; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 4px; }
        th, td { border: 1px solid #d1d5db; font-size: 12px; padding: 8px 12px; }
        th { background: #e5e7eb; font-weight: 800; text-align: left; }
        .num { text-align: right; font-weight: 700; }
        .total-row { background: #f3f4f6; font-weight: 900; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 34px; page-break-inside: avoid; }
        .signature-box { border: 1px solid #111; height: 118px; display: flex; flex-direction: column; justify-content: space-between; text-align: center; padding: 10px; }
        .signature-role { font-weight: 800; }
        .signature-name { border-top: 1px solid #111; padding-top: 7px; min-height: 24px; }
        .toolbar { margin-bottom: 20px; display: flex; gap: 10px; }
        .toolbar button { background: #2563eb; border: none; border-radius: 7px; color: #fff; cursor: pointer; font-size: 13px; font-weight: 700; padding: 9px 18px; }
        .toolbar a { background: #374151; border-radius: 7px; color: #fff; font-size: 13px; font-weight: 700; padding: 9px 18px; text-decoration: none; }
        @media print {
            .toolbar { display: none; }
            body { padding: 12px; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="/reports/fg?production_date={{ $date }}&shift={{ $shift }}&spk_id={{ $spkId }}">← Kembali</a>
    <button onclick="window.print()">🖨 Print / Save PDF</button>
</div>

<div class="doc-header">
    <div class="doc-cell">
        <div class="company">PT. Daya Dajiang Midas</div>
        <div>Production Department</div>
    </div>
    <div class="doc-cell">
        <div class="doc-title">LAPORAN HASIL FINISH GOOD</div>
        <div class="doc-subtitle">Packing Output Report</div>
    </div>
    <div class="doc-cell doc-meta">
        <div>No. Dokumen: FG-RPT/{{ date('Ymd', strtotime($date)) }}/S{{ $shift }}</div>
        <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
    </div>
</div>

<table class="info-table">
    <tr>
        <th>Tanggal Produksi</th>
        <td>{{ date('d M Y', strtotime($date)) }}</td>
        <th>Shift</th>
        <td>Shift {{ $shift }} ({{ $shiftOptions[$shift]['time'] ?? '' }})</td>
    </tr>
    <tr>
        <th>Proses</th>
        <td>Packing / Finish Good</td>
        <th>Total Output</th>
        <td><strong>{{ number_format($fgReport['total']) }} pcs</strong></td>
    </tr>
    <tr>
        <th>SPK</th>
        <td colspan="3">{{ $selectedSpk ? $selectedSpk->spk_no.' - '.$selectedSpk->buyer?->name.' - '.$selectedSpk->item.' - '.$selectedSpk->style : 'Semua SPK' }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width:50px">No</th>
            <th>Buyer</th>
            <th>Size Code</th>
            <th class="num">Good Qty (pcs)</th>
            <th class="num">Subtotal Buyer</th>
        </tr>
    </thead>
    <tbody>
    @php $rowNo = 1; @endphp
@forelse($fgReport['groups'] as $buyer => $items)
    @foreach($items as $item)
        <tr>
            <td>{{ $rowNo++ }}</td>
            <td>{{ $buyer }}</td>
            <td>{{ $item->size_code }}</td>
            <td class="num">{{ number_format((int)$item->good_qty) }}</td>
            <td class="num">{{ $loop->first ? number_format($items->sum('good_qty')) : '' }}</td>
        </tr>
    @endforeach
@empty
        <tr><td colspan="5" style="color:#888;text-align:center;padding:32px">Belum ada data packing good.</td></tr>
@endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3">GRAND TOTAL FINISH GOOD</td>
            <td class="num">{{ number_format($fgReport['total']) }}</td>
            <td class="num">pcs</td>
        </tr>
    </tfoot>
</table>

<div class="signatures">
    <div class="signature-box">
        <div class="signature-role">Prepared By</div>
        <div class="signature-name">Admin Produksi</div>
    </div>
    <div class="signature-box">
        <div class="signature-role">Checked By</div>
        <div class="signature-name">Supervisor</div>
    </div>
    <div class="signature-box">
        <div class="signature-role">Approved By</div>
        <div class="signature-name">Manager</div>
    </div>
</div>
</body>
</html>
