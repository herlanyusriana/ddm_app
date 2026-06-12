@extends('production.layout', ['title' => 'Warehouse', 'subtitle' => 'Persiapan material untuk SPK sebelum masuk proses produksi'])

@section('content')
@php
    $pending = $spks->where('status', 'Pending');
    $prepared = $spks->where('status', 'Material Prepared');
    $totalQty = $spks->sum('target_qty');
@endphp

<style>
    .warehouse-summary {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-bottom: 20px;
    }

    .warehouse-metric {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 18px;
    }

    .warehouse-metric span {
        color: var(--muted);
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .07em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .warehouse-metric strong {
        display: block;
        font-size: 30px;
        font-weight: 900;
        line-height: 1;
    }

    .warehouse-workflow-grid {
        display: grid;
        gap: 14px;
    }

    .warehouse-card {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        display: grid;
        gap: 16px;
        grid-template-columns: minmax(190px, 240px) 1fr auto;
        padding: 18px;
    }

    .warehouse-card-main {
        min-width: 0;
    }

    .warehouse-spk-no {
        color: var(--ink);
        display: block;
        font-size: 17px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .warehouse-buyer {
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .warehouse-detail-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .warehouse-detail {
        background: #f8fafc;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        min-width: 0;
        padding: 10px;
    }

    .warehouse-detail span {
        color: var(--muted);
        display: block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .warehouse-detail strong {
        color: var(--ink);
        display: block;
        font-size: 13px;
        font-weight: 800;
        margin-top: 2px;
        overflow-wrap: anywhere;
    }

    .warehouse-action {
        align-items: flex-end;
        display: flex;
        flex-direction: column;
        gap: 10px;
        justify-content: center;
    }

    @media (max-width: 1040px) {
        .warehouse-card {
            grid-template-columns: 1fr;
        }

        .warehouse-action {
            align-items: stretch;
        }
    }

    @media (max-width: 760px) {
        .warehouse-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .warehouse-detail-grid { grid-template-columns: 1fr 1fr; }

        .warehouse-card {
            padding: 14px;
        }

        .warehouse-metric {
            padding: 12px 10px;
        }

        .warehouse-metric span {
            font-size: 9px;
        }

        .warehouse-metric strong {
            font-size: 23px;
        }
    }
</style>

<section class="warehouse-summary">
    <article class="warehouse-metric">
        <span>Material Pending</span>
        <strong style="color:var(--warning)">{{ number_format($pending->count()) }}</strong>
    </article>
    <article class="warehouse-metric">
        <span>Material Siap</span>
        <strong style="color:var(--success)">{{ number_format($prepared->count()) }}</strong>
    </article>
    <article class="warehouse-metric">
        <span>Total Qty SPK</span>
        <strong>{{ number_format($totalQty) }}</strong>
    </article>
</section>

<section class="warehouse-workflow-grid">
    @forelse($spks as $spk)
        <article class="warehouse-card">
            <div class="warehouse-card-main">
                <span class="warehouse-spk-no">{{ $spk->spk_no }}</span>
                <div class="warehouse-buyer">{{ $spk->buyer->name }}</div>
            </div>

            <div class="warehouse-detail-grid">
                <div class="warehouse-detail">
                    <span>Tanggal</span>
                    <strong>{{ $spk->spk_date?->format('d-m-Y') ?? $spk->created_at->format('d-m-Y') }}</strong>
                </div>
                <div class="warehouse-detail">
                    <span>Item</span>
                    <strong>{{ $spk->item ?? $spk->part?->code ?? '-' }}</strong>
                </div>
                <div class="warehouse-detail">
                    <span>Style</span>
                    <strong>{{ $spk->style ?? $spk->sizeVariant?->code ?? '-' }}</strong>
                </div>
                <div class="warehouse-detail">
                    <span>Qty</span>
                    <strong>{{ number_format($spk->target_qty) }} pcs</strong>
                </div>
            </div>

            <div class="warehouse-action">
                @if($spk->status === 'Pending')
                    <span class="badge badge-warning">Pending</span>
                    <form method="post" action="/warehouse/spk/{{ $spk->id }}/prepare" onsubmit="return confirm('Konfirmasi material untuk SPK {{ $spk->spk_no }} sudah disiapkan?')">
                        @csrf
                        <button class="btn btn-primary" type="submit">Siapkan Material</button>
                    </form>
                @else
                    <span class="badge badge-success">Material Siap</span>
                    <a class="link-btn link-btn-secondary" href="/spk/{{ $spk->id }}">Lihat SPK</a>
                @endif
            </div>
        </article>
    @empty
        <div class="panel">
            <div class="empty-state">
                <p>Tidak ada SPK yang perlu disiapkan.</p>
            </div>
        </div>
    @endforelse
</section>
@endsection
