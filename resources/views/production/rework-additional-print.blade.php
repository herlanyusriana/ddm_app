<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Form Additional</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { color:#000; font-family:Arial, Helvetica, sans-serif; font-size:10.5px; margin:0; }
        .no-print { display:flex; gap:8px; margin:8px; }
        .no-print button, .no-print a { background:#2563eb; border:0; border-radius:6px; color:#fff; font-weight:700; padding:8px 12px; text-decoration:none; }
        .sheet { border:1.5px solid #111; display:flex; flex-direction:column; height:190mm; overflow:hidden; padding:8mm 10mm; width:100%; }
        .header { display:grid; flex:0 0 31mm; grid-template-columns:75mm 1fr 55mm; }
        .meta { align-self:end; display:grid; gap:3px; }
        .meta-row { display:grid; grid-template-columns:24mm 5mm 1fr; }
        .title { align-self:center; font-size:23px; font-weight:900; text-align:center; text-decoration:underline; }
        .company { align-self:start; display:grid; justify-items:center; line-height:1.35; text-align:left; }
        .stamp { align-items:center; border:1.5px solid #111; border-radius:50%; display:flex; font-size:7.5px; font-weight:800; height:21mm; justify-content:center; line-height:1.2; text-align:center; width:21mm; }
        .company-text { justify-self:stretch; margin-top:1mm; }
        .from-line { flex:0 0 5mm; font-weight:700; line-height:5mm; margin:0; text-align:right; width:100%; }
        table.form { border-collapse:collapse; table-layout:fixed; width:100%; }
        .form th, .form td { border:1.2px solid #111; height:7.2mm; padding:1.5px 5px; vertical-align:middle; }
        .form th { font-size:10px; font-weight:900; text-align:center; }
        .no { text-align:center; width:9mm; }
        .material-code { width:66mm; }
        .style { width:53mm; }
        .material-name { width:70mm; }
        .spec { width:42mm; }
        .qty { text-align:center; width:14mm; }
        .unit { text-align:center; width:17mm; }
        .footer { align-items:end; display:grid; flex:1 0 auto; grid-template-columns:108mm 1fr; margin-top:4mm; min-height:22mm; }
        .doc { font-weight:800; height:19mm; writing-mode:vertical-rl; transform:rotate(180deg); }
        .signatures { align-self:end; border-collapse:collapse; table-layout:fixed; width:92mm; }
        .signatures td { border:1.2px solid #111; height:19mm; padding-top:2mm; text-align:center; vertical-align:top; }
        @media print {
            .no-print { display:none; }
            .sheet { border:1.5px solid #111; height:190mm; page-break-after:always; }
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
    <div class="header">
        <div class="meta">
            <div class="meta-row"><strong>Departemen</strong><span>:</span><span>WH</span></div>
            <div class="meta-row"><strong>Shift</strong><span>:</span><span>—</span></div>
            <div class="meta-row"><strong>Tanggal</strong><span>:</span><span>{{ $result->result_date?->format('d M Y') }}</span></div>
        </div>
        <div class="title">Form Additional</div>
        <div class="company">
            <div class="stamp">PT DAYA<br>DAIJANG<br>MIDAS</div>
            <div class="company-text">
                <strong>PT DAYA DAIJANG MIDAS</strong><br>
                Departemen :<br>
                Shift :<br>
                Tanggal :
            </div>
        </div>
    </div>

    <div class="from-line">From : {{ $sourceName }} / Rework</div>
    <table class="form">
        <thead>
        <tr>
            <th class="no">No.</th>
            <th class="material-code">Material Code</th>
            <th class="style">Style</th>
            <th class="material-name">Material Name</th>
            <th class="spec">SPEC</th>
            <th class="qty">QTY</th>
            <th class="unit">Unit</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="no">1</td>
            <td>{{ $result->reject_notes }}</td>
            <td>{{ $style }}</td>
            <td>{{ $result->component }}</td>
            <td>{{ $spec }}</td>
            <td class="qty">{{ $result->qty }}</td>
            <td class="unit">Pcs</td>
        </tr>
        @for($i = 2; $i <= 12; $i++)
            <tr>
                <td class="no">{{ $i }}</td>
                <td></td><td></td><td></td><td></td><td class="qty"></td><td class="unit"></td>
            </tr>
        @endfor
        </tbody>
    </table>

    <div class="footer">
        <div style="display:flex;gap:4mm;align-items:end">
            <div class="doc">Doc.FM-DDM-PRD.01.004</div>
            <table class="signatures">
                <tr>
                    <td>Write</td><td>Review 1</td><td>Review 2</td><td>Review 3</td><td>Review 4</td><td>Review 5</td><td>Approval</td>
                </tr>
            </table>
        </div>
        <div></div>
    </div>
</section>
<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
