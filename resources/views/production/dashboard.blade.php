@extends('production.layout', ['title' => 'Dashboard Produksi', 'subtitle' => 'Ringkasan performa produksi per tanggal dan shift'])

@section('topbar-actions')
    <form class="filter-bar" method="get" action="/dashboard" style="margin:0;border:0;background:transparent;padding:0">
        <input type="date" name="production_date" value="{{ $date }}" style="min-height:36px;font-size:13px">
        <select name="shift" style="min-height:36px;font-size:13px">
            @foreach($shiftOptions as $key => $option)
                <option value="{{ $key }}" @selected((string) $shift === (string) $key)>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
        @if($isManualWindow)
            <a class="btn btn-ghost btn-sm" href="/dashboard">Kembali ke shift aktif</a>
        @endif
    </form>
@endsection

@section('content')
@php
    $totalGood = $summaries->sum('good_qty');
    $totalReject = $summaries->sum('ng_qty');
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
        .dashboard-stats { grid-template-columns: 1fr 1fr; }
        .process-dashboard-grid { grid-template-columns: 1fr; }

        .dashboard-stat strong {
            font-size: 24px;
        }

        .dashboard-stat {
            padding: 14px;
        }

        .dashboard-stat small {
            font-size: 11px;
        }
    }
</style>

<div
    data-realtime-dashboard
    data-summary-url="{{ route('production.dashboard.summary', $isManualWindow ? ['production_date' => $date, 'shift' => $shift] : [], false) }}"
>
<div class="flex gap-2" style="justify-content:flex-end;margin-bottom:10px">
    <span class="badge badge-success" data-realtime-status>● Realtime aktif</span>
    <span class="text-muted text-sm" data-realtime-updated>Menunggu pembaruan...</span>
</div>

<section class="dashboard-stats">
    <article class="dashboard-stat">
        <span>Total Produksi</span>
        <strong data-dashboard-total>{{ number_format($totalQty) }}</strong>
        <small>Good + Reject</small>
    </article>
    <article class="dashboard-stat">
        <span>Good</span>
        <strong style="color:var(--success)" data-dashboard-good>{{ number_format($totalGood) }}</strong>
        <small>Unit OK</small>
    </article>
    <article class="dashboard-stat">
        <span>Reject</span>
        <strong style="color:var(--danger)" data-dashboard-reject>{{ number_format($totalReject) }}</strong>
        <small>Masuk hutang rework</small>
    </article>
    <article class="dashboard-stat">
        <span>Proses Aktif</span>
        <strong data-dashboard-active>{{ number_format($activeProcesses) }}</strong>
        <small>Dari <span data-dashboard-process-count>{{ number_format($summaries->count()) }}</span> proses</small>
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
        <article class="process-card" data-process-id="{{ $summary['process']->id }}">
            <div class="process-card-head">
                <h2>{{ $summary['process']->name }}</h2>
                <div class="process-total" data-process-total>{{ number_format($total) }}</div>
            </div>
            <div class="process-breakdown">
                <div>
                    <span>Good</span>
                    <strong style="color:var(--success)" data-process-good>{{ number_format($good) }}</strong>
                </div>
                <div>
                    <span>Reject</span>
                    <strong style="color:var(--danger)" data-process-reject>{{ number_format($ng) }}</strong>
                </div>
            </div>
            <div class="process-meter" aria-label="Good rate {{ $goodRate }}%" data-process-meter>
                <div class="process-meter-fill" style="width:{{ $goodRate }}%" data-process-meter-fill></div>
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
</div>

<script>
    (() => {
        const dashboard = document.querySelector('[data-realtime-dashboard]');
        const intervalMs = 5000;
        let timer = null;

        if (!dashboard) {
            return;
        }

        const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(Number(value || 0));
        const status = dashboard.querySelector('[data-realtime-status]');
        const updated = dashboard.querySelector('[data-realtime-updated]');

        const render = (payload) => {
            dashboard.querySelector('[data-dashboard-total]').textContent = formatNumber(payload.totals.total_qty);
            dashboard.querySelector('[data-dashboard-good]').textContent = formatNumber(payload.totals.good_qty);
            dashboard.querySelector('[data-dashboard-reject]').textContent = formatNumber(payload.totals.reject_qty);
            dashboard.querySelector('[data-dashboard-active]').textContent = formatNumber(payload.totals.active_processes);
            dashboard.querySelector('[data-dashboard-process-count]').textContent = formatNumber(payload.totals.process_count);

            payload.processes.forEach((process) => {
                const card = dashboard.querySelector(`[data-process-id="${process.id}"]`);
                if (!card) {
                    return;
                }

                card.querySelector('[data-process-total]').textContent = formatNumber(process.total_qty);
                card.querySelector('[data-process-good]').textContent = formatNumber(process.good_qty);
                card.querySelector('[data-process-reject]').textContent = formatNumber(process.reject_qty);
                card.querySelector('[data-process-meter]').setAttribute('aria-label', `Good rate ${process.good_rate}%`);
                card.querySelector('[data-process-meter-fill]').style.width = `${process.good_rate}%`;
            });

            status.className = 'badge badge-success';
            status.textContent = '● Realtime aktif';
            updated.textContent = `Diperbarui ${new Date(payload.updated_at).toLocaleTimeString('id-ID')}`;
        };

        const refresh = async () => {
            if (document.hidden) {
                return;
            }

            try {
                const response = await fetch(dashboard.dataset.summaryUrl, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('Dashboard refresh failed');
                }

                render(await response.json());
            } catch (error) {
                status.className = 'badge badge-danger';
                status.textContent = '● Realtime terputus';
            }
        };

        const startPolling = () => {
            clearInterval(timer);
            refresh();
            timer = setInterval(refresh, intervalMs);
        };

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(timer);
                return;
            }

            startPolling();
        });

        startPolling();
    })();
</script>
@endsection
