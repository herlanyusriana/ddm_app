@php
    $printDate = \Carbon\CarbonImmutable::parse($date)->format('d-M-y');
    $hourLabels = ['Jam 1', 'Jam 2', 'Jam 3', 'Jam 4', 'Jam 5', 'Jam 6', 'Jam 7'];
    $isBinding = strcasecmp($process->name, 'Binding') === 0;
    $rows = collect($report['rows']);
    $totals = $report['totals_row'];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Harian {{ $process->name }} {{ $printDate }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            margin: 0;
        }

        .toolbar {
            background: #fff;
            border-bottom: 1px solid #111;
            display: flex;
            gap: 8px;
            padding: 8px;
        }

        .toolbar a,
        .toolbar button {
            background: #2563eb;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font: 700 12px Arial, Helvetica, sans-serif;
            padding: 8px 12px;
            text-decoration: none;
        }

        .sheet {
            background: #bfbfbf;
            min-height: 198mm;
            padding: 6px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .meta {
            line-height: 1.8;
            min-width: 180px;
        }

        .meta-row {
            display: grid;
            grid-template-columns: 70px 12px 1fr;
            gap: 4px;
        }

        .title-block {
            align-items: flex-end;
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 250px;
            text-align: right;
        }

        .title-block h1 {
            border-bottom: 3px solid #000;
            font-size: 24px;
            line-height: 1;
            margin: 0;
        }

        .company {
            font-weight: 800;
            line-height: 1.4;
        }

        .content-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 4fr) 150px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }

        th {
            font-weight: 800;
            text-align: center;
        }

        .main-table td {
            height: 34px;
        }

        .main-table .no-col {
            text-align: center;
            width: 28px;
        }

        .main-table .name-col {
            width: 110px;
        }

        .main-table .target-col,
        .main-table .total-col {
            text-align: center;
            width: 48px;
        }

        .hour-cell {
            font-size: 8.5px;
            line-height: 1.35;
            white-space: pre-line;
        }

        .total-row td {
            font-weight: 800;
            height: 20px;
            text-align: center;
        }

        .reject-table {
            font-size: 8.5px;
        }

        .reject-table td {
            height: 30px;
        }

        .footer-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: 300px 1fr 100px;
            margin-top: 8px;
        }

        .note-box,
        .sign-box,
        .qc-box {
            border: 1px solid #000;
            min-height: 78px;
        }

        .note-box {
            display: grid;
            grid-template-rows: 1fr 20px;
        }

        .note-box .note {
            padding: 8px;
        }

        .note-box .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
        }

        .note-box .signatures div,
        .qc-box .label {
            border-top: 1px solid #000;
            padding: 3px;
            text-align: center;
        }

        .approval-box {
            align-self: end;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            width: 430px;
        }

        .approval-box div {
            border: 1px solid #000;
            min-height: 38px;
            padding-top: 5px;
            text-align: center;
        }

        .qc-box {
            display: grid;
            grid-template-rows: 1fr 20px;
        }

        @media print {
            .toolbar {
                display: none;
            }

            .sheet {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print Report Harian</button>
        <a href="{{ url()->previous() }}">Kembali</a>
    </div>

    <main class="sheet">
        <header class="report-header">
            <div class="meta">
                <div class="meta-row"><strong>TANGGAL</strong><span>:</span><span>{{ $printDate }}</span></div>
                <div class="meta-row"><strong>SHIFT</strong><span>:</span><span>{{ $shift }}</span></div>
                <div class="meta-row"><strong>PROSES</strong><span>:</span><span>{{ strtoupper($process->name) }}</span></div>
            </div>
            <div class="title-block">
                <h1>Form Additional</h1>
                <div class="company">
                    PT DAYA DAIJANG MIDAS<br>
                    Departemen : {{ strtoupper($process->name) }}<br>
                    Shift : {{ $shift }}<br>
                    Tanggal : {{ $printDate }}
                </div>
            </div>
        </header>

        <section class="content-grid">
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="no-col">Mesin</th>
                        <th class="name-col">Nama</th>
                        <th class="target-col">Target</th>
                        @foreach($hourLabels as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th class="total-col">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $machine = $isBinding ? ($row[0] ?? '') : '';
                            $name = $isBinding ? ($row[1] ?? '—') : trim(($row[0] ?? '—').' / '.($row[1] ?? '—'), ' /');
                            $target = $isBinding ? ($row[2] ?? 0) : '';
                            $hourStart = $isBinding ? 3 : 2;
                            $totalIndex = $isBinding ? 10 : 9;
                        @endphp
                        <tr>
                            <td class="no-col">{{ $machine }}</td>
                            <td class="name-col">{{ $name }}{{ $machine !== '' ? ' ('.$machine.')' : '' }}</td>
                            <td class="target-col">{{ $target }}</td>
                            @for($i = 0; $i < 7; $i++)
                                <td class="hour-cell">{{ $row[$hourStart + $i] ?? '' }}</td>
                            @endfor
                            <td class="total-col">{{ $row[$totalIndex] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="height: 80px; text-align: center;">Belum ada data produksi.</td>
                        </tr>
                    @endforelse

                    <tr class="total-row">
                        <td colspan="3">TOTAL</td>
                        @for($i = 0; $i < 7; $i++)
                            <td class="hour-cell">{{ $totals[$isBinding ? 3 + $i : 2 + $i] ?? '' }}</td>
                        @endfor
                        <td>{{ $totals[$isBinding ? 10 : 9] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="reject-table">
                <thead>
                    <tr>
                        <th>Reject</th>
                        <th>Posisi</th>
                        <th>Qty</th>
                        <th>TTD</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rejectRows as $reject)
                        <tr>
                            <td>{{ strtoupper($reject['reject']) }}</td>
                            <td>{{ $reject['position'] }}</td>
                            <td style="text-align: center;">{{ $reject['qty'] }}</td>
                            <td></td>
                        </tr>
                    @empty
                        @for($i = 0; $i < 7; $i++)
                            <tr>
                                <td>BINDING</td>
                                <td>SUDAH REPAIR</td>
                                <td style="text-align: center;">0</td>
                                <td></td>
                            </tr>
                        @endfor
                    @endforelse
                </tbody>
            </table>
        </section>

        <footer class="footer-grid">
            <div class="note-box">
                <div class="note">16.00 - 24.00 = TIDAK HUJAN</div>
                <div class="signatures">
                    <div>Hujan / Tidak Hujan</div>
                    <div>Write</div>
                    <div>Review 1</div>
                </div>
            </div>
            <div class="approval-box">
                <div>Write</div>
                <div>Review 1</div>
                <div>Review 2</div>
                <div>Review 3</div>
                <div>Review 4</div>
                <div>Review 5</div>
                <div>Approval</div>
            </div>
            <div class="qc-box">
                <div></div>
                <div class="label">QC</div>
            </div>
        </footer>
    </main>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
