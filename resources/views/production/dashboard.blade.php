@extends('production.layout', ['title' => 'Dashboard Produksi'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Dashboard Produksi</h1>
        <p>Ringkasan Good dan NG per proses.</p>
    </div>
    <form class="filter" method="get" action="/dashboard">
        <input type="date" name="production_date" value="{{ $date }}">
        <select name="shift">@foreach($shiftOptions as $key => $option)<option value="{{ $key }}" @selected($shift === $key)>{{ $option['label'] }} ({{ $option['time'] }})</option>@endforeach</select>
        <button class="btn secondary">Filter</button>
    </form>
</section>

<section class="summary">
    @foreach($summaries as $summary)
        <article class="metric">
            <span>{{ $summary['process']->name }}</span>
            <strong>{{ $summary['total_qty'] }}</strong>
            <small>Good {{ $summary['good_qty'] }} • NG {{ $summary['ng_qty'] }}</small>
        </article>
    @endforeach
</section>
@endsection
