@extends('production.layout', ['title' => $pageTitle, 'subtitle' => $pageSubtitle])

@section('topbar-actions')
    @php($exportProcess = $hourlyReport['process'])
    @if($exportProcess && $historyView === 'input')
        <a
            class="link-btn link-btn-primary"
            href="{{ route('reports.production-hourly.print', array_filter(['production_date' => $date, 'shift' => $shift, 'process_id' => $exportProcess->id, 'operator_ids' => $selectedOperatorIds], fn ($value) => $value !== null && $value !== '' && $value !== []), false) }}"
            target="_blank"
        >Print Report Harian</a>
        <a
            class="link-btn link-btn-success"
            href="{{ route('reports.production-hourly', array_filter(['production_date' => $date, 'shift' => $shift, 'process_id' => $exportProcess->id, 'history_period' => $historyPeriod, 'production_month' => $productionMonth, 'operator_ids' => $selectedOperatorIds], fn ($value) => $value !== null && $value !== '' && $value !== []), false) }}"
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

    .history-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .history-tab {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        color: var(--ink2);
        font-size: 13px;
        font-weight: 800;
        min-height: 38px;
        padding: 9px 14px;
    }

    .history-tab.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
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
    @php($baseTabQuery = array_filter([
        'input_type' => $pageType,
        'process_id' => $pageType === 'proses' && $selectedProcess ? $selectedProcess->id : null,
        'history_period' => $historyPeriod,
        'production_date' => $date,
        'production_month' => $productionMonth,
        'shift' => $shift,
        'operator_ids' => $selectedOperatorIds,
    ], fn ($value) => $value !== null && $value !== '' && $value !== []))
    <div class="history-tabs" aria-label="Pilihan history produksi">
        <a class="history-tab {{ $historyView === 'input' ? 'active' : '' }}" href="{{ route('production.history', array_merge($baseTabQuery, ['view' => 'input']), false) }}">History Input</a>
        <a class="history-tab {{ $historyView === 'trouble' ? 'active' : '' }}" href="{{ route('production.history', array_merge($baseTabQuery, ['view' => 'trouble']), false) }}">History Trouble</a>
        <a class="history-tab {{ $historyView === 'correction' ? 'active' : '' }}" href="{{ route('production.history', array_merge($baseTabQuery, ['view' => 'correction']), false) }}">Koreksi Input</a>
    </div>

    <div class="history-toolbar">
        <form class="filter-bar" method="get" action="{{ route('production.history', [], false) }}">
            <input type="hidden" name="view" value="{{ $historyView }}">
            <input type="hidden" name="input_type" value="{{ $pageType }}">
            @if($pageType === 'proses' && $selectedProcess)
                <select name="process_id" style="min-height:36px;font-size:13px">
                    @foreach($inputProcesses as $process)
                        <option value="{{ $process->id }}" @selected($selectedProcess->id === $process->id)>{{ $process->name }}</option>
                    @endforeach
                </select>
            @endif
            @if($pageType === 'proses' && $selectedProcess && strcasecmp($selectedProcess->name, 'Binding') === 0)
                <select name="operator_ids[]" multiple size="3" style="min-height:80px;font-size:13px">
                    @foreach($operators as $operator)
                        <option value="{{ $operator->id }}" @selected(in_array((int) $operator->id, $selectedOperatorIds, true))>
                            {{ $operator->operator_code }} · {{ $operator->name }}
                        </option>
                    @endforeach
                </select>
                <span class="field-hint">Kosong = semua operator. Bisa pilih lebih dari satu.</span>
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

    @include('production.partials.history-panels', ['historyView' => $historyView])
</div>

<script>
    document.querySelector('[data-history-period]')?.addEventListener('change', (event) => {
        const monthly = event.target.value === 'monthly';
        document.querySelector('[data-daily-filter]').style.display = monthly ? 'none' : '';
        document.querySelector('[data-monthly-filter]').style.display = monthly ? '' : 'none';
    });
</script>
@endsection
