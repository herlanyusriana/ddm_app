@extends('production.layout', ['title' => 'Dashboard Produksi', 'subtitle' => 'Ringkasan performa produksi per tanggal dan shift'])

@section('topbar-actions')
    <form class="filter-bar" method="get" action="/dashboard" style="margin:0;border:0;background:transparent;padding:0">
        <input type="date" name="production_date" value="{{ $date }}" style="min-height:36px;font-size:13px">
        <select name="shift" style="min-height:36px;font-size:13px">
            @foreach($shiftOptions as $key => $option)
                <option value="{{ $key }}" @selected($shift === $key)>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
    </form>
@endsection

@section('content')
@php
    $totalGood = $summaries->sum('good_qty');
    $totalNg = $summaries->sum('ng_qty');
    $totalQty = $summaries->sum('total_qty');
    $activeProcesses = $summaries->filter(fn ($summary) => $summary['total_qty'] > 0)->count();
@endphp

<style>
    .dashboard-stats {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 20px;
    }

    .dashboard-stat {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        min-width: 0;
        padding: 18px;
    }

    .dashboard-stat span {
        color: var(--muted);
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .07em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .dashboard-stat strong {
        color: var(--ink);
        display: block;
        font-size: 30px;
        font-weight: 900;
        letter-spacing: 0;
        line-height: 1;
    }

    .dashboard-stat small {
        color: var(--muted);
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-top: 8px;
    }

    .process-dashboard-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .process-card {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 18px;
    }

    .process-card-head {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .process-card h2 {
        font-size: 16px;
        font-weight: 850;
    }

    .process-total {
        color: var(--ink);
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
    }

    .process-breakdown {
        display: grid;
        gap: 10px;
        grid-template-columns: 1fr 1fr;
        margin-bottom: 12px;
    }

    .process-breakdown div {
        background: #f8fafc;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        padding: 10px;
    }

    .process-breakdown span {
        color: var(--muted);
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .process-breakdown strong {
        display: block;
        font-size: 22px;
        font-weight: 900;
        margin-top: 2px;
    }

    .process-meter {
        background: #e5e7eb;
        border-radius: 999px;
        height: 8px;
        overflow: hidden;
    }

    .process-meter-fill {
        background: var(--success);
        border-radius: 999px;
        height: 100%;
        min-width: 0;
    }

    @media (max-width: 900px) {
        .dashboard-stats,
        .process-dashboard-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 760px) {
        .dashboard-stats,
        .process-dashboard-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-stat strong {
            font-size: 26px;
        }
    }
</style>

<section class="dashboard-stats">
    <article class="dashboard-stat">
        <span>Total Produksi</span>
        <strong>{{ number_format($totalQty) }}</strong>
        <small>Good + NG</small>
    </article>
    <article class="dashboard-stat">
        <span>Good</span>
        <strong style="color:var(--success)">{{ number_format($totalGood) }}</strong>
        <small>Unit OK</small>
    </article>
    <article class="dashboard-stat">
        <span>NG</span>
        <strong style="color:var(--danger)">{{ number_format($totalNg) }}</strong>
        <small>Rework + Scrap</small>
    </article>
    <article class="dashboard-stat">
        <span>Proses Aktif</span>
        <strong>{{ number_format($activeProcesses) }}</strong>
        <small>Dari {{ number_format($summaries->count()) }} proses</small>
    </article>
</section>

<section class="process-dashboard-grid">
    @forelse($summaries as $summary)
        @php
            $total = max(0, (int) $summary['total_qty']);
            $good = max(0, (int) $summary['good_qty']);
            $ng = max(0, (int) $summary['ng_qty']);
            $goodRate = $total > 0 ? round($good / $total * 100) : 0;
        @endphp
        <article class="process-card">
            <div class="process-card-head">
                <h2>{{ $summary['process']->name }}</h2>
                <div class="process-total">{{ number_format($total) }}</div>
            </div>
            <div class="process-breakdown">
                <div>
                    <span>Good</span>
                    <strong style="color:var(--success)">{{ number_format($good) }}</strong>
                </div>
                <div>
                    <span>NG</span>
                    <strong style="color:var(--danger)">{{ number_format($ng) }}</strong>
                </div>
            </div>
            <div class="process-meter" aria-label="Good rate {{ $goodRate }}%">
                <div class="process-meter-fill" style="width:{{ $goodRate }}%"></div>
            </div>
        </article>
    @empty
        <div class="panel">
            <div class="empty-state">
                <p>Belum ada proses input aktif.</p>
            </div>
        </div>
    @endforelse
</section>
@endsection
