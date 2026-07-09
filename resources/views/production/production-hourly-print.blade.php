@php
    $printDate = \Carbon\CarbonImmutable::parse($date)->format('d-M-y');
    $hourLabels = ['Jam 1', 'Jam 2', 'Jam 3', 'Jam 4', 'Jam 5', 'Jam 6', 'Jam 7'];
    $isBinding = strcasecmp($process->name, 'Binding') === 0;
    $rows = collect($report['rows']);
    $totals = $report['totals_row'];
    $minimumRows = 9;
    $blankRows = max(0, $minimumRows - $rows->count());

    $formatHourCell = function (?string $value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace([' · ', ', Reject = ', 'Alasan: '], ['  ', '  R: ', ''], $value);
        $value = preg_replace('/^\d{2}:\d{2}\s+/', '', $value);
        $value = str_replace(' = ', '    ', $value);

        return $value;
    };
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
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
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
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            font: 700 12px Arial, Helvetica, sans-serif;
            padding: 7px 11px;
            text-decoration: none;
        }

        .sheet {
            background: #b8b8b8;
            border: 2px solid #0f766e;
            height: 200mm;
            overflow: hidden;
            padding: 3px;
            width: 287mm;
        }

        .top-meta {
            height: 18px;
            line-height: 1.35;
            padding-left: 2px;
        }

        .top-meta .row {
            display: grid;
            grid-template-columns: 55px 1fr;
            width: 170px;
        }

        .report-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr 58mm;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 1px 3px;
            vertical-align: middle;
        }

        th {
            font-size: 8px;
            font-weight: 800;
            height: 14px;
            text-align: center;
        }

        .main-table {
            background: #b8b8b8;
        }

        .main-table td {
            font-size: 7px;
            height: 36px;
            line-height: 1.22;
        }

        .main-table .machine-col {
            text-align: center;
            width: 10mm;
        }

        .main-table .name-col {
            font-size: 7px;
            font-weight: 700;
            text-align: center;
            width: 28mm;
        }

        .main-table .target-col {
            text-align: center;
            width: 14mm;
        }

        .main-table .hour-col {
            width: 23mm;
        }

        .main-table .total-col {
            text-align: center;
            width: 16mm;
        }

        .hour-cell {
            font-size: 6.5px;
            line-height: 1.18;
            white-space: pre-line;
        }

        .total-row td {
            font-size: 7px;
            font-weight: 800;
            height: 13px;
            text-align: center;
        }

        .reject-table {
            background: #b8b8b8;
        }

        .reject-table th {
            height: 15px;
        }

        .reject-table td {
            font-size: 6.8px;
            font-weight: 700;
            height: 18px;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .reject-table .reject-col {
            width: 18mm;
        }

        .reject-table .pos-col {
            width: 18mm;
        }

        .reject-table .qty-col {
            text-align: center;
            width: 11mm;
        }

        .reject-table .ttd-col {
            width: 11mm;
        }

        .footer-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: 85mm 1fr 30mm;
            margin-top: 5px;
        }

        .weather-box,
        .approval-table,
        .qc-box {
            background: #fff;
            border: 1px solid #000;
            height: 22mm;
        }

        .weather-box {
            display: grid;
            grid-template-rows: 1fr 5mm;
        }

        .weather-note {
            font-size: 8px;
            font-weight: 800;
            padding: 7px;
        }

        .weather-sign {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
        }

        .weather-sign div {
            border-top: 1px solid #000;
            border-right: 1px solid #000;
            padding-top: 1px;
            text-align: center;
        }

        .weather-sign div:last-child {
            border-right: 0;
        }

        .approval-table {
            align-self: end;
            background: transparent;
            border: 0;
            display: grid;
            grid-template-columns: repeat(7, 18mm);
            justify-content: start;
            padding-top: 9mm;
        }

        .approval-table div {
            background: #fff;
            border: 1px solid #000;
            height: 12mm;
            padding-top: 2px;
            text-align: center;
        }

        .qc-box {
            display: grid;
            grid-template-rows: 1fr 5mm;
        }

        .qc-box .label {
            border-top: 1px solid #000;
            padding-top: 1px;
            text-align: center;
        }

        @media print {
            .toolbar {
                display: none;
            }

            .sheet {
                border: 0;
                height: 200mm;
                width: 287mm;
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
        <div class="top-meta">
            <div class="row"><strong>TANGGAL</strong><span>{{ $printDate }}</span></div>
            <div class="row"><strong>SHIFT</strong><span>{{ $shift }}</span></div>
        </div>

        <section class="report-grid">
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="machine-col">Mesin</th>
                        <th class="name-col">Nama</th>
                        <th class="target-col">Target</th>
                        @foreach($hourLabels as $label)
                            <th class="hour-col">{{ $label }}</th>
                        @endforeach
                        <th class="total-col">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $machine = $isBinding ? ($row[0] ?? '') : '';
                            $name = $isBinding ? ($row[1] ?? '—') : trim(($row[0] ?? '—').' / '.($row[1] ?? '—'), ' /');
                            $target = $isBinding ? ($row[2] ?? 0) : '';
                            $hourStart = $isBinding ? 3 : 2;
                            $totalIndex = $isBinding ? 10 : 9;
                        @endphp
                        <tr>
                            <td class="machine-col">{{ $machine }}</td>
                            <td class="name-col">{{ $name }}{{ $machine !== '' ? ' ('.$machine.')' : '' }}</td>
                            <td class="target-col">{{ $target }}</td>
                            @for($i = 0; $i < 7; $i++)
                                <td class="hour-cell">{{ $formatHourCell($row[$hourStart + $i] ?? '') }}</td>
                            @endfor
                            <td class="total-col">{{ $row[$totalIndex] ?? 0 }}</td>
                        </tr>
                    @endforeach

                    @for($i = 0; $i < $blankRows; $i++)
                        <tr>
                            <td class="machine-col">{{ $rows->count() + $i + 1 }}</td>
                            <td class="name-col"></td>
                            <td class="target-col"></td>
                            @foreach($hourLabels as $label)
                                <td class="hour-cell"></td>
                            @endforeach
                            <td class="total-col">0</td>
                        </tr>
                    @endfor

                    <tr class="total-row">
                        <td colspan="3">TOTAL</td>
                        @for($i = 0; $i < 7; $i++)
                            @php($totalText = (string) ($totals[$isBinding ? 3 + $i : 2 + $i] ?? ''))
                            <td>{{ preg_match('/TOTAL G:\s*(\d+)/', $totalText, $match) ? $match[1] : '' }}</td>
                        @endfor
                        <td>{{ $totals[$isBinding ? 10 : 9] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="reject-table">
                <thead>
                    <tr>
                        <th class="reject-col">Reject</th>
                        <th class="pos-col">Posisi</th>
                        <th class="qty-col">Qty</th>
                        <th class="ttd-col">TTD</th>
                    </tr>
                </thead>
                <tbody>
                    @if($rejectRows->isEmpty())
                        @for($i = 0; $i < 9; $i++)
                            <tr>
                                <td class="reject-col">BINDING:<br></td>
                                <td class="pos-col">SUDAH<br>REPAIR</td>
                                <td class="qty-col">0</td>
                                <td class="ttd-col"></td>
                            </tr>
                        @endfor
                    @else
                        @foreach($rejectRows as $reject)
                            <tr>
                                <td class="reject-col">BINDING:<br>{{ $reject['reject'] }}</td>
                                <td class="pos-col">SUDAH<br>REPAIR</td>
                                <td class="qty-col">{{ $reject['qty'] }}</td>
                                <td class="ttd-col"></td>
                            </tr>
                        @endforeach
                        @for($i = 0; $i < max(0, 9 - $rejectRows->count()); $i++)
                            <tr>
                                <td class="reject-col">PACKING:<br></td>
                                <td class="pos-col">REPAIR</td>
                                <td class="qty-col">0</td>
                                <td class="ttd-col"></td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
        </section>

        <footer class="footer-grid">
            <div class="weather-box">
                <div class="weather-note">16.00 - 24.00 = TIDAK<br>HUJAN</div>
                <div class="weather-sign">
                    <div>Hujan / Tidak Hujan</div>
                    <div>Write</div>
                    <div>Review 1</div>
                </div>
            </div>

            <div class="approval-table">
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
