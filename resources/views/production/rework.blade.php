@extends('production.layout', ['title' => 'Rework', 'subtitle' => 'Daftar hutang reject yang harus dirework per SPK dan proses'])

@section('content')
<style>
    .rework-summary {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-bottom: 18px;
    }

    .rework-stat {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 18px;
    }

    .rework-stat span {
        color: var(--muted);
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .07em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .rework-stat strong {
        display: block;
        font-size: 30px;
        font-weight: 900;
        line-height: 1;
    }

    @media (max-width: 760px) {
        .rework-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="rework-summary">
    <article class="rework-stat">
        <span>Total Hutang Rework</span>
        <strong style="color:var(--warning)">{{ number_format($totalReject) }}</strong>
    </article>
    <article class="rework-stat">
        <span>SPK Terdampak</span>
        <strong>{{ number_format($totalSpk) }}</strong>
    </article>
    <article class="rework-stat">
        <span>Proses Bermasalah</span>
        <strong>{{ number_format($rows->count()) }}</strong>
    </article>
</section>

<div class="panel">
    <div class="panel-header">
        <h2>Hutang Rework</h2>
        <span class="badge badge-warning">{{ number_format($totalReject) }} pcs reject</span>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SPK</th>
                        <th>Buyer</th>
                        <th>Item</th>
                        <th>Style</th>
                        <th>Proses Asal</th>
                        <th class="td-num">Reject / Hutang</th>
                        <th>Last Input</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            @if($row['spk'])
                                <a class="master-code" href="/spk/{{ $row['spk']->id }}">{{ $row['spk']->spk_no }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $row['spk']?->buyer?->name ?? '-' }}</td>
                        <td>{{ $row['spk']?->item ?? '-' }}</td>
                        <td>{{ $row['spk']?->style ?? '-' }}</td>
                        <td><span class="badge badge-neutral">{{ $row['process']?->name ?? '-' }}</span></td>
                        <td class="td-num font-bold" style="color:var(--warning)">{{ number_format($row['reject_qty']) }}</td>
                        <td>{{ $row['last_date'] ? date('d M Y', strtotime($row['last_date'])) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon">🔧</div>
                                <p>Belum ada hutang rework.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
