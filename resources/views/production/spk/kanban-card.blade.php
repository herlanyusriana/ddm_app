<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kanban Unit - {{ $spk->spk_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #e5e7eb; color: #111827; font-family: Arial, sans-serif; min-height: 100vh; padding: 22px; }
        .toolbar { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 18px; }
        .toolbar a, .toolbar button { background: #1f2937; border: 0; border-radius: 8px; color: #fff; cursor: pointer; font-size: 13px; font-weight: 800; min-height: 40px; padding: 0 16px; text-decoration: none; }
        .toolbar button { background: #2563eb; }
        .summary { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; margin: 0 auto 18px; max-width: 1080px; padding: 14px 16px; }
        .summary-title { font-size: 16px; font-weight: 900; margin-bottom: 8px; }
        .summary-grid { display: grid; gap: 8px; grid-template-columns: repeat(4, 1fr); }
        .summary-label { color: #6b7280; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .summary-value { font-size: 13px; font-weight: 800; }
        .cards { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); margin: 0 auto; max-width: 1180px; }
        .card { background: #fff; border: 2px solid #111827; border-radius: 6px; min-height: 390px; padding: 14px; page-break-inside: avoid; position: relative; }
        .card-head { border-bottom: 2px solid #111827; display: grid; gap: 8px; grid-template-columns: 1fr auto; margin-bottom: 12px; padding-bottom: 10px; }
        .card-title { font-size: 16px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
        .unit-badge { align-items: center; background: #111827; border-radius: 999px; color: #fff; display: inline-flex; font-size: 11px; font-weight: 900; padding: 4px 10px; white-space: nowrap; }
        .warning { background: #fef2f2; border: 1.5px solid #dc2626; border-radius: 5px; color: #b91c1c; font-size: 11px; font-weight: 900; letter-spacing: .05em; margin-bottom: 12px; padding: 8px 10px; text-align: center; }
        .field-grid { display: grid; gap: 8px; }
        .field { border: 1px solid #d1d5db; border-radius: 5px; min-height: 52px; padding: 7px 9px; }
        .field.small { min-height: 45px; }
        .label { color: #6b7280; font-size: 9px; font-weight: 900; letter-spacing: .08em; margin-bottom: 4px; text-transform: uppercase; }
        .value { font-size: 13px; font-weight: 900; line-height: 1.25; word-break: break-word; }
        .two-col { display: grid; gap: 8px; grid-template-columns: 1fr 1fr; }
        .blank { color: #9ca3af; letter-spacing: .05em; }
        .footer { bottom: 10px; color: #6b7280; font-size: 10px; font-weight: 700; left: 14px; position: absolute; right: 14px; text-align: center; }
        @media (max-width: 700px) {
            body { padding: 12px; }
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .cards { grid-template-columns: 1fr; }
            .card { min-height: 370px; padding: 12px; }
            .card-head { grid-template-columns: 1fr; }
            .unit-badge { justify-content: center; }
        }
        @media print {
            @page { margin: 8mm; size: A4 portrait; }
            body { background: #fff; padding: 0; }
            .toolbar, .summary { display: none; }
            .cards { display: grid; gap: 6mm; grid-template-columns: 1fr 1fr; max-width: none; }
            .card { box-shadow: none; min-height: 128mm; break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="/spk/{{ $spk->id }}">Kembali</a>
    <button onclick="window.print()">Print Semua Kartu Unit</button>
</div>

<section class="summary">
    <div class="summary-title">Kanban Unit per Barang</div>
    <div class="summary-grid">
        <div><div class="summary-label">LOT / SPK</div><div class="summary-value">{{ $spk->spk_no }}</div></div>
        <div><div class="summary-label">Buyer</div><div class="summary-value">{{ $spk->buyer->name }}</div></div>
        <div><div class="summary-label">Item</div><div class="summary-value">{{ $spk->item ?? $spk->part?->name ?? '-' }}</div></div>
        <div><div class="summary-label">Total Unit</div><div class="summary-value">{{ number_format($spk->target_qty) }} kartu</div></div>
    </div>
</section>

<main class="cards">
@foreach($unitCards as $card)
    <article class="card">
        <header class="card-head">
            <div class="card-title">Kanban Unit</div>
            <div class="unit-badge">UNIT {{ $card['code'] }} / {{ $card['total'] }}</div>
        </header>

        <div class="warning">JANGAN CAMPUR LOT</div>

        <div class="field-grid">
            <div class="field">
                <div class="label">LOT / SPK</div>
                <div class="value">{{ $spk->spk_no }}</div>
            </div>
            <div class="two-col">
                <div class="field small">
                    <div class="label">Tanggal SPK</div>
                    <div class="value">{{ $spk->spk_date?->format('d-m-Y') ?? '-' }}</div>
                </div>
                <div class="field small">
                    <div class="label">Shift</div>
                    <div class="value">Shift {{ $spk->shift ?? '-' }}</div>
                </div>
            </div>
            <div class="field">
                <div class="label">Buyer</div>
                <div class="value">{{ $spk->buyer->name }}</div>
            </div>
            <div class="field">
                <div class="label">Item</div>
                <div class="value">{{ $spk->item ?? $spk->part?->name ?? '-' }}</div>
            </div>
            <div class="field">
                <div class="label">Style</div>
                <div class="value">{{ $spk->style ?? $spk->sizeVariant?->code ?? '-' }}</div>
            </div>
            <div class="two-col">
                <div class="field small">
                    <div class="label">Good Check</div>
                    <div class="value blank">________</div>
                </div>
                <div class="field small">
                    <div class="label">Reject / Hold</div>
                    <div class="value blank">________</div>
                </div>
            </div>
        </div>

        <div class="footer">
            Card {{ $card['code'] }} dari {{ $card['total'] }} | Dicetak {{ now()->format('d-m-Y H:i') }}
        </div>
    </article>
@endforeach
</main>
</body>
</html>
