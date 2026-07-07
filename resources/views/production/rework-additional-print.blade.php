<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Form Additional</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        .no-print { display: flex; gap: 8px; margin: 0 0 10px; }
        .no-print button, .no-print a { background: #2563eb; border: 0; border-radius: 6px; color: #fff; cursor: pointer; font-weight: 700; padding: 8px 12px; text-decoration: none; }
        .sheet { border: 2px solid #111; min-height: 185mm; padding: 10mm; position: relative; }
        .title { font-size: 26px; font-weight: 800; position: absolute; right: 55mm; text-decoration: underline; top: 18mm; }
        .company { line-height: 1.35; position: absolute; right: 12mm; text-align: left; top: 12mm; width: 48mm; }
        .stamp { border: 2px solid #333; border-radius: 50%; display: inline-grid; font-size: 9px; font-weight: 700; height: 27mm; margin-bottom: 4px; place-items: center; text-align: center; width: 27mm; }
        .meta { display: grid; gap: 6px; margin: 25mm 0 7mm 0; width: 72mm; }
        .meta-row { display: grid; grid-template-columns: 25mm 5mm 1fr; }
        .from { position: absolute; right: 63mm; top: 55mm; width: 48mm; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1.5px solid #111; height: 12mm; padding: 4px 6px; vertical-align: middle; }
        th { font-size: 11px; font-weight: 800; text-align: center; }
        td.num { text-align: center; width: 10mm; }
        td.qty { text-align: center; width: 14mm; }
        td.unit { text-align: center; width: 18mm; }
        .signatures { bottom: 12mm; display: grid; grid-template-columns: repeat(7, 1fr); left: 10mm; position: absolute; width: 130mm; }
        .signatures div { border: 1.5px solid #111; height: 22mm; padding-top: 2mm; text-align: center; }
        .doc { bottom: 35mm; font-weight: 700; left: 12mm; position: absolute; writing-mode: vertical-rl; }
        @media print {
            .no-print { display: none; }
            .sheet { min-height: auto; height: calc(100vh - 1px); }
        }
    </style>
</head>
<body>
@php
    $buyer = $result->productionEntry?->buyer ?? $result->bindingRejectStock?->buyer;
    $size = $result->productionEntry?->sizeVariant ?? $result->bindingRejectStock?->sizeVariant;
    $sourceName = $result->productionEntry?->process?->name ?? 'Binding';
    $style = trim(($buyer?->code ? $buyer->code.' / ' : '').($size?->code ?? '—'));
    $spec = trim(($size?->production_code ? $size->production_code.'-' : '').($size?->code ?? '—'));
@endphp
<div class="no-print">
    <button onclick="window.print()">🖨 Print Form Additional</button>
    <a href="/rework-results?date={{ $date }}">Kembali</a>
</div>
<section class="sheet">
    <div class="company">
        <div class="stamp">PT DAYA<br>DAIJANG<br>MIDAS</div>
        <div><strong>PT DAYA DAIJANG MIDAS</strong></div>
        <div>Departemen :</div>
        <div>Shift :</div>
        <div>Tanggal :</div>
    </div>
    <h1 class="title">Form Additional</h1>
    <div class="meta">
        <div class="meta-row"><strong>Departemen</strong><span>:</span><span>WH</span></div>
        <div class="meta-row"><strong>Shift</strong><span>:</span><span>—</span></div>
        <div class="meta-row"><strong>Tanggal</strong><span>:</span><span>{{ $result->result_date?->format('d M Y') }}</span></div>
    </div>
    <div class="from"><strong>From :</strong> {{ $sourceName }} / Rework</div>

    <table>
        <thead>
        <tr>
            <th style="width:10mm">No.</th>
            <th>Material Code</th>
            <th>Style</th>
            <th>Material Name</th>
            <th>SPEC</th>
            <th style="width:14mm">QTY</th>
            <th style="width:18mm">Unit</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="num">1</td>
            <td>{{ $result->reject_notes }}</td>
            <td>{{ $style }}</td>
            <td>{{ $result->component }}</td>
            <td>{{ $spec }}</td>
            <td class="qty">{{ $result->qty }}</td>
            <td class="unit">Pcs</td>
        </tr>
        @for($i = 2; $i <= 12; $i++)
            <tr><td class="num">{{ $i }}</td><td></td><td></td><td></td><td></td><td class="qty"></td><td class="unit"></td></tr>
        @endfor
        </tbody>
    </table>

    <div class="doc">Doc.FM-DDM-PRD.01.004</div>
    <div class="signatures">
        <div>Write</div>
        <div>Review 1</div>
        <div>Review 2</div>
        <div>Review 3</div>
        <div>Review 4</div>
        <div>Review 5</div>
        <div>Approval</div>
    </div>
</section>
<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
