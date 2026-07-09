<div class="panel hourly-history-panel">
    <div class="panel-header">
        <h2>History Input</h2>
        <span class="badge badge-neutral">{{ $hourlyReport['record_count'] }} records</span>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="hourly-history-table">
                <thead>
                    <tr>
                        @foreach($hourlyReport['headers'] as $header)
                            <th class="{{ str_starts_with($header, 'Total ') ? 'td-num' : '' }} {{ str_starts_with($header, 'Jam ') ? 'hour-column' : 'identity-column' }}">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse($hourlyReport['rows'] as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            <td class="{{ str_starts_with($hourlyReport['headers'][$index] ?? '', 'Total ') ? 'td-num' : '' }}">
                                {!! nl2br(e((string) $cell)) !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(1, count($hourlyReport['headers'])) }}">
                        <div class="empty-state"><div class="empty-icon">📭</div><p>Belum ada input untuk filter ini.</p></div>
                    </td></tr>
                @endforelse
                </tbody>
                @if($hourlyReport['totals_row'])
                    <tfoot>
                        <tr>
                            @foreach($hourlyReport['totals_row'] as $index => $cell)
                                <td class="{{ str_starts_with($hourlyReport['headers'][$index] ?? '', 'Total ') ? 'td-num' : '' }}">
                                    @if(str_starts_with($hourlyReport['headers'][$index] ?? '', 'Jam '))
                                        @foreach(explode("\n", (string) $cell) as $line)
                                            @if(preg_match('/^TOTAL G: (\d+) · R: (\d+)$/', $line, $totals))
                                                <div style="margin-top:4px">
                                                    <strong>TOTAL </strong>
                                                    <span class="hour-good">G: {{ $totals[1] }}</span>
                                                    <span> · </span>
                                                    <span class="hour-reject">R: {{ $totals[2] }}</span>
                                                </div>
                                            @elseif(preg_match('/^(Target|Operator): (.+)$/', $line, $meta))
                                                <div class="hour-meta">{{ $meta[1] }}: {{ $meta[2] }}</div>
                                            @else
                                                <div>{{ $line }}</div>
                                            @endif
                                        @endforeach
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="panel trouble-history-panel">
    <div class="panel-header">
        <h2>History Trouble</h2>
        <span class="badge badge-neutral">{{ $troubles->count() }} records</span>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        @if($historyPeriod === 'monthly')<th>Tanggal</th>@endif
                        <th>Waktu</th>
                        <th>Durasi</th>
                        <th>Jenis</th>
                        <th>Operator</th>
                        <th>SPK</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($troubles as $trouble)
                    <tr>
                        @if($historyPeriod === 'monthly')<td>{{ $trouble->production_date->format('d M Y') }}</td>@endif
                        <td>{{ substr($trouble->start_time, 0, 5) }} - {{ substr($trouble->end_time, 0, 5) }}</td>
                        <td>{{ $trouble->duration_minutes }} menit</td>
                        <td><span class="badge badge-warning">{{ $trouble->trouble_type }}</span></td>
                        <td>{{ $trouble->operator?->name ?? '—' }}</td>
                        <td>{{ $trouble->spk?->spk_no ?? 'Custom' }}</td>
                        <td>{{ $trouble->notes }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $historyPeriod === 'monthly' ? 7 : 6 }}">
                        <div class="empty-state"><div class="empty-icon">🛠️</div><p>Belum ada trouble untuk filter ini.</p></div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel trouble-history-panel">
    <div class="panel-header">
        <h2>Koreksi Input Produksi</h2>
        <span class="badge badge-neutral">{{ $correctionEntries->count() }} records</span>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Operator</th><th>Style</th><th>Good</th><th>Reject</th><th>Alasan</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($correctionEntries as $entry)
                    <tr>
                        <td>{{ $entry->created_at->timezone('Asia/Jakarta')->format('H:i') }}</td>
                        <td>{{ $entry->operator?->name ?? '—' }}</td>
                        <td>{{ $entry->buyer?->code ?? '—' }} / {{ $entry->sizeVariant?->production_code }}-{{ $entry->sizeVariant?->code }}</td>
                        <td class="td-num">{{ $entry->good_qty }}</td>
                        <td class="td-num">{{ $entry->ng_qty }}</td>
                        <td>{{ $entry->reject_reason ?? '—' }}</td>
                        <td>
                            <div class="correction-actions">
                                <a class="btn btn-secondary btn-sm" href="/production-entries/{{ $entry->id }}/edit">Edit</a>
                                <form method="post" action="/production-entries/{{ $entry->id }}" onsubmit="return confirm('Hapus input ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><p>Belum ada input untuk dikoreksi.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
