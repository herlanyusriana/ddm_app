<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kanban Card — {{ $spk->spk_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
            padding: 32px 16px;
            font-family: 'Courier New', Courier, monospace;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .toolbar a, .toolbar button {
            background: #1d5fa7;
            border: none;
            border-radius: 7px;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            padding: 10px 18px;
            text-decoration: none;
        }

        .toolbar a.back {
            background: #374151;
        }

        /* ─── The card itself ─── */
        .card {
            background: #fff;
            border: 2px solid #374151;
            border-radius: 4px;
            width: 380px;
            min-height: 520px;
            padding: 28px 32px 36px;
            position: relative;
            box-shadow: 0 4px 24px rgba(0,0,0,.18);
        }

        .card-header {
            text-align: center;
            border-bottom: 2.5px solid #111;
            padding-bottom: 12px;
            margin-bottom: 28px;
        }

        .card-header h1 {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .card-header .kanban-no-badge {
            display: inline-block;
            background: #111;
            color: #fff;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            padding: 3px 12px;
            margin-top: 6px;
        }

        .fields {
            display: grid;
            gap: 18px;
        }

        .field {
            display: grid;
            gap: 4px;
        }

        .field-label {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .field-value {
            font-size: 16px;
            font-weight: 900;
            border-bottom: 1.5px solid #d1d5db;
            padding-bottom: 6px;
            min-height: 32px;
            color: #111;
        }

        .field-value.empty {
            color: #d1d5db;
        }

        /* Reject section has 2 columns */
        .reject-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Good Qty large display */
        .good-qty-box {
            background: #f0fdf4;
            border: 2px solid #16a34a;
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 4px;
        }

        .good-qty-box .field-label {
            color: #15803d;
        }

        .good-qty-box .field-value {
            font-size: 28px;
            border-bottom: none;
            color: #15803d;
        }

        .reject-box {
            background: #fff7ed;
            border: 2px solid #ea580c;
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 4px;
        }

        .reject-box .field-label {
            color: #c2410c;
        }

        .reject-box .field-value {
            border-bottom: none;
        }

        /* QR / card number watermark */
        .card-footer {
            margin-top: 28px;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
        }

        /* ─── PRINT ─── */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .card {
                box-shadow: none;
                border: 2px solid #000;
                width: 100%;
                max-width: 380px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a class="back" href="/spk">← Kembali ke SPK</a>
    <button onclick="window.print()">🖨 Print Kanban Card</button>
</div>

<div class="card">
    <div class="card-header">
        <h1>Kanban Card</h1>
        <span class="kanban-no-badge">No. Kanban: 1</span>
    </div>

    <div class="fields">
        <div class="field">
            <div class="field-label">SPK No.</div>
            <div class="field-value">{{ $spk->spk_no }}</div>
        </div>

        <div class="field">
            <div class="field-label">Buyer</div>
            <div class="field-value">{{ $spk->buyer->name }}</div>
        </div>

        <div class="field">
            <div class="field-label">Item</div>
            <div class="field-value">{{ $spk->item ?? ($spk->part ? $spk->part->code . ' — ' . $spk->part->name : '-') }}</div>
        </div>

        <div class="field">
            <div class="field-label">Style</div>
            <div class="field-value">{{ $spk->style ?? $spk->sizeVariant?->code ?? '-' }}</div>
        </div>

        <div class="field">
            <div class="field-label">Target Qty (pcs)</div>
            <div class="field-value">{{ number_format($spk->target_qty) }}</div>
        </div>

        <div class="reject-row">
            <div class="reject-box">
                <div class="field-label">Reject Reason</div>
                <div class="field-value empty">_______________</div>
            </div>
            <div class="reject-box">
                <div class="field-label">Reject Qty</div>
                <div class="field-value empty">_______________</div>
            </div>
        </div>

        <div class="good-qty-box">
            <div class="field-label">Good Qty</div>
            <div class="field-value empty">_______________</div>
        </div>

        @if($spk->notes)
        <div class="field">
            <div class="field-label">Catatan</div>
            <div class="field-value" style="font-size:13px;border-bottom:none">{{ $spk->notes }}</div>
        </div>
        @endif
    </div>

    <div class="card-footer">
        Dicetak: {{ now()->format('d-m-Y H:i') }} &nbsp;|&nbsp; Status: {{ $spk->status }}
    </div>
</div>

</body>
</html>
