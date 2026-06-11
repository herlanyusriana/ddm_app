@extends('production.layout', ['title' => 'Warehouse SPK Preparation'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Persiapan Material (Warehouse)</h1>
        <p>Siapkan raw material untuk SPK yang berstatus Pending.</p>
    </div>
</section>

<section class="grid">
    <article class="panel">
        <div class="panel-head"><h2>Daftar SPK Aktif</h2></div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>No SPK</th><th>Tanggal SPK</th><th>Buyer</th><th>Item</th><th>Style</th><th class="num">QTY Produksi</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($spks as $spk)
                    <tr>
                        <td>{{ $spk->spk_no }}</td>
                        <td>{{ $spk->spk_date?->format('d-m-Y') ?? $spk->created_at->format('d-m-Y') }}</td>
                        <td>{{ $spk->buyer->name }}</td>
                        <td>{{ $spk->item ?? $spk->part?->code ?? '-' }}</td>
                        <td>{{ $spk->style ?? $spk->sizeVariant?->code ?? '-' }}</td>
                        <td class="num">{{ $spk->target_qty }}</td>
                        <td>
                            @if($spk->status === 'Pending') <span class="pill" style="background:#fff7ed;color:#9a3412">{{ $spk->status }}</span>
                            @elseif($spk->status === 'Material Prepared') <span class="pill" style="background:#eaf2ff;color:#0e3f75">{{ $spk->status }}</span>
                            @else <span class="pill">{{ $spk->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($spk->status === 'Pending')
                            <form method="post" action="/warehouse/spk/{{ $spk->id }}/prepare" onsubmit="return confirm('Konfirmasi material untuk SPK {{ $spk->spk_no }} sudah disiapkan?')">
                                @csrf
                                <button class="btn primary" type="submit" style="min-height:32px;font-size:12px;padding:0 10px">Tandai Disiapkan</button>
                            </form>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Tidak ada SPK yang perlu disiapkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>
@endsection
