@extends('production.layout', ['title' => $pageTitle, 'subtitle' => $pageSubtitle])

@section('topbar-actions')
    @php($exportProcess = $hourlyReport['process'])
    @if($exportProcess)
        <a
            class="link-btn link-btn-success"
            href="{{ route('reports.production-hourly', ['production_date' => $date, 'shift' => $shift, 'process_id' => $exportProcess->id, 'history_period' => $historyPeriod, 'production_month' => $productionMonth], false) }}"
        >Export History Excel</a>
    @endif
    <a class="link-btn link-btn-secondary" href="{{ $pageType === 'hasil' ? route('input.hasil', ['production_date' => $date, 'shift' => $shift], false) : route('input.proses', array_filter(['process_id' => $selectedProcess?->id, 'production_date' => $date, 'shift' => $shift], fn ($value) => $value !== null && $value !== ''), false) }}">Kembali ke Input</a>
@endsection

@section('content')
<style>
    .history-grid {
        display: grid;
        gap: 20px;
    }

    .history-toolbar {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
    }

    .history-toolbar .filter-bar {
        margin: 0;
    }

    .hourly-history-table {
        min-width: 1420px;
    }

    .hourly-history-table th,
    .hourly-history-table td {
        white-space: nowrap;
    }

    .hourly-history-table .hour-column {
        min-width: 170px;
    }

    .hourly-history-table .identity-column {
        min-width: 100px;
    }

    .hourly-history-table tfoot td {
        background: var(--primary-soft);
        border-top: 2px solid #bfdbfe;
        color: var(--ink2);
        font-size: 12px;
        font-weight: 800;
    }

    .hour-good {
        color: var(--success);
    }

    .hour-reject {
        color: var(--danger);
    }

    .hour-meta {
        color: var(--ink2);
        font-weight: 800;
        margin-top: 3px;
    }

    .correction-actions {
        display: flex;
        gap: 6px;
    }

    @media (max-width: 760px) {
        .history-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .history-toolbar .filter-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .hourly-history-table {
            min-width: 1320px;
        }
    }
</style>

<div class="history-grid">
    <div class="history-toolbar">
        <form class="filter-bar" method="get" action="{{ route('production.history', [], false) }}">
            <input type="hidden" name="input_type" value="{{ $pageType }}">
            @if($pageType === 'proses' && $selectedProcess)
                <select name="process_id" style="min-height:36px;font-size:13px">
                    @foreach($inputProcesses as $process)
                        <option value="{{ $process->id }}" @selected($selectedProcess->id === $process->id)>{{ $process->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="history_period" data-history-period style="min-height:36px;font-size:13px">
                <option value="daily" @selected($historyPeriod === 'daily')>Harian</option>
                <option value="monthly" @selected($historyPeriod === 'monthly')>Bulanan</option>
            </select>
            <input type="date" name="production_date" value="{{ $date }}" data-daily-filter style="min-height:36px;font-size:13px;{{ $historyPeriod === 'monthly' ? 'display:none' : '' }}">
            <input type="month" name="production_month" value="{{ $productionMonth }}" data-monthly-filter style="min-height:36px;font-size:13px;{{ $historyPeriod === 'daily' ? 'display:none' : '' }}">
            <select name="shift" style="min-height:36px;font-size:13px">
                @foreach($shiftOptions as $key => $opt)
                    <option value="{{ $key }}" @selected((string) $shift === (string) $key)>{{ $opt['label'] }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
        </form>
    </div>

    @include('production.partials.history-panels')
</div>

<script>
    document.querySelector('[data-history-period]')?.addEventListener('change', (event) => {
        const monthly = event.target.value === 'monthly';
        document.querySelector('[data-daily-filter]').style.display = monthly ? 'none' : '';
        document.querySelector('[data-monthly-filter]').style.display = monthly ? '' : 'none';
    });
</script>
@endsection
