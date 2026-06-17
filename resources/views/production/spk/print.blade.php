<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print SPK - {{ $spk->spk_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #e5e7eb; color: #111827; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.45; padding: 24px; }
        .toolbar { display: flex; gap: 10px; justify-content: center; margin-bottom: 18px; }
        .toolbar a, .toolbar button { background: #1f2937; border: 0; border-radius: 7px; color: #fff; cursor: pointer; font-size: 13px; font-weight: 700; padding: 9px 16px; text-decoration: none; }
        .toolbar button { background: #2563eb; }
        .sheet { background: #fff; border: 1px solid #111827; box-shadow: 0 10px 30px rgba(15,23,42,.18); margin: 0 auto; max-width: 1120px; min-height: 760px; padding: 22px; }
        .doc-head { display: grid; grid-template-columns: 1.2fr 2fr 1.2fr; border: 2px solid #111827; }
        .head-cell { border-right: 1px solid #111827; min-height: 86px; padding: 12px; display: flex; flex-direction: column; justify-content: center; }
        .head-cell:last-child { border-right: 0; }
        .company { font-size: 15px; font-weight: 800; }
        .muted { color: #4b5563; }
        .title { font-size: 22px; font-weight: 900; letter-spacing: .04em; text-align: center; }
        .subtitle { font-size: 12px; font-weight: 700; margin-top: 4px; text-align: center; }
        .meta { font-size: 11px; line-height: 1.75; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #9ca3af; padding: 9px 10px; text-align: left; vertical-align: middle; }
        th { background: #f3f4f6; font-weight: 800; width: 16%; }
        .info { margin-top: 16px; }
        .items { margin-top: 16px; }
        .items th { background: #d1d5db; text-align: center; width: auto; }
        .items td { height: 48px; }
        .num { text-align: right; font-weight: 800; }
        .notes { margin-top: 16px; border: 1px solid #9ca3af; min-height: 74px; padding: 10px; }
        .notes-title { font-weight: 800; margin-bottom: 6px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 34px; }
        .signature { border: 1px solid #111827; height: 120px; display: flex; flex-direction: column; justify-content: space-between; padding: 10px; text-align: center; }
        .signature-role { font-weight: 800; }
        .signature-line { border-top: 1px solid #111827; padding-top: 7px; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border: 0; box-shadow: none; max-width: none; min-height: 0; padding: 10mm; }
        }
        @media (max-width: 760px) {
            body { padding: 12px; }
            .toolbar { flex-wrap: wrap; }
            .sheet { padding: 14px; }
            .doc-head { grid-template-columns: 1fr; }
            .head-cell { border-right: 0; border-bottom: 1px solid #111827; min-height: auto; }
            .head-cell:last-child { border-bottom: 0; }
            .title { font-size: 18px; }
            .info, .items { display: block; overflow-x: auto; }
            .signatures { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="/spk/{{ $spk->id }}">Kembali</a>
    <button onclick="window.print()">Print / Save PDF</button>
</div>

<main class="sheet">
    <section class="doc-head">
        <div class="head-cell">
            <div class="company">PT. Daya Dajiang Midas</div>
            <div class="muted">Production Planning & Control</div>
        </div>
        <div class="head-cell">
            <div class="title">SURAT PERINTAH KERJA</div>
            <div class="subtitle">Production Order Sheet</div>
        </div>
        <div class="head-cell meta">
            <div>No. SPK: <strong>{{ $spk->spk_no }}</strong></div>
            <div>Tanggal Cetak: {{ now()->format('d-m-Y H:i') }}</div>
            <div>Status: {{ $spk->status }}</div>
        </div>
    </section>

    <table class="info">
        <tr>
            <th>Tanggal SPK</th>
            <td>{{ $spk->spk_date?->format('d M Y') ?? '-' }}</td>
            <th>Month</th>
            <td>{{ $spk->month ?? '-' }}</td>
        </tr>
        <tr>
            <th>Dept</th>
            <td>{{ $spk->dept ?? '-' }}</td>
            <th>Shift</th>
            <td>Shift {{ $spk->shift ?? '-' }}</td>
        </tr>
        <tr>
            <th>Buyer</th>
            <td>{{ ($spkItems ?? collect([$spk]))->pluck('buyer.name')->unique()->join(', ') }}</td>
            <th>PO</th>
            <td>{{ ($spkItems ?? collect([$spk]))->pluck('po_no')->filter()->unique()->join(', ') ?: '-' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:54px">No</th>
                <th>Buyer</th>
                <th>Item</th>
                <th>Style</th>
                <th class="num">QTY Produksi</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($spkItems ?? collect([$spk])) as $item)
                <tr>
                    <td style="text-align:center">{{ $loop->iteration }}</td>
                    <td>{{ $item->buyer->name }}</td>
                    <td>{{ $item->item ?? $item->part?->name ?? '-' }}</td>
                    <td>{{ $item->style ?? $item->sizeVariant?->code ?? '-' }}</td>
                    <td class="num">{{ number_format($item->target_qty) }} pcs</td>
                    <td>{{ $item->remarks ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="notes">
        <div class="notes-title">Catatan Produksi</div>
        <div>{{ $spk->notes ?: 'Barang diproses sesuai lot SPK. Kanban unit wajib mengikuti barang dan tidak boleh dicampur antar lot.' }}</div>
    </div>

    <section class="signatures">
        <div class="signature">
            <div class="signature-role">Prepared By</div>
            <div class="signature-line">PPIC</div>
        </div>
        <div class="signature">
            <div class="signature-role">Checked By</div>
            <div class="signature-line">Manager</div>
        </div>
        <div class="signature">
            <div class="signature-role">Approved By</div>
            <div class="signature-line">Factory Manager</div>
        </div>
    </section>
</main>
</body>
</html>
