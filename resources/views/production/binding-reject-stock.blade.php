@extends('production.layout', ['title' => 'Data Reject Binding', 'subtitle' => 'Stock card reject berdasarkan Buyer dan Style'])

@section('topbar-actions')
    <a class="link-btn link-btn-success" href="/binding-reject-stock-export?date={{ $date }}">Export Excel</a>
    <form class="filter-bar" method="get" style="margin:0">
        <input type="date" name="date" value="{{ $date }}">
        <button class="btn btn-secondary btn-sm">Filter</button>
    </form>
@endsection

@section('content')
<div class="grid grid-2">
    <section class="panel">
        <div class="panel-header"><h2>{{ $editRecord ? 'Edit Transaksi' : 'Input Stock Card' }}</h2></div>
        <div class="panel-body">
            <form class="form-grid" method="post" action="{{ $editRecord ? '/binding-reject-stock/'.$editRecord->id : '/binding-reject-stock' }}">
                @csrf
                @if($editRecord) @method('PUT') @endif
                <div class="form-row-2">
                    <div class="field"><label>Tanggal</label><input type="date" name="transaction_date" value="{{ old('transaction_date', $editRecord?->transaction_date?->toDateString() ?? $date) }}" required></div>
                    <div class="field"><label>Jam</label><input type="time" name="transaction_time" value="{{ old('transaction_time', $editRecord?->transaction_time ? substr($editRecord->transaction_time, 0, 5) : now('Asia/Jakarta')->format('H:i')) }}"></div>
                </div>
                <div class="form-row-2">
                    <div class="field"><label>Pallet</label><input name="pallet" value="{{ old('pallet', $editRecord?->pallet) }}"></div>
                    <div class="field"><label>PO / SPK</label><input name="po_no" value="{{ old('po_no', $editRecord?->po_no) }}"></div>
                </div>
                <div class="field"><label>Buyer</label><select name="buyer_id" required><option value="">— Pilih Buyer —</option>@foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(old('buyer_id', $editRecord?->buyer_id) == $buyer->id)>{{ $buyer->code }} · {{ $buyer->name }}</option>@endforeach</select></div>
                <div class="field"><label>Style</label><select name="size_variant_id" required><option value="">— Pilih Style —</option>@foreach($sizes as $size)<option value="{{ $size->id }}" @selected(old('size_variant_id', $editRecord?->size_variant_id) == $size->id)>{{ $size->display_label }}</option>@endforeach</select></div>
                <div class="form-row-2">
                    <div class="field"><label>IN</label><input type="number" min="0" name="qty_in" value="{{ old('qty_in', $editRecord?->qty_in ?? 0) }}" required></div>
                    <div class="field"><label>OUT</label><input type="number" min="0" name="qty_out" value="{{ old('qty_out', $editRecord?->qty_out ?? 0) }}" required></div>
                </div>
                <div class="field"><label>Paraf</label><input name="paraf" value="{{ old('paraf', $editRecord?->paraf) }}"></div>
                <button class="btn btn-primary" type="submit">{{ $editRecord ? 'Simpan Perubahan' : 'Simpan Stock Card' }}</button>
                @if($editRecord)<a class="btn btn-secondary" href="/binding-reject-stock?date={{ $date }}">Batal Edit</a>@endif
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header"><h2>Data Reject Binding</h2><span class="badge badge-neutral">{{ date('d M Y', strtotime($date)) }}</span></div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Buyer</th><th>Style</th><th class="td-num">QTY</th></tr></thead>
                    <tbody>
                    @forelse($summary as $group)
                        @foreach($group['styles'] as $style)
                            <tr><td>{{ $group['buyer']?->code ?? $group['buyer']?->name }}</td><td>{{ $style['size']?->code }}</td><td class="td-num">{{ $style['qty'] }}</td></tr>
                        @endforeach
                        <tr style="background:#e5e7eb;font-weight:800"><td colspan="2">{{ $group['buyer']?->code }} TOTAL</td><td class="td-num">{{ $group['styles']->sum('qty') }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><p>Belum ada stock reject Binding.</p></div></td></tr>
                    @endforelse
                    </tbody>
                    <tfoot><tr style="background:#fb923c;font-weight:900"><td colspan="2">GRAND TOTAL</td><td class="td-num">{{ $grandTotal }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </section>
</div>

<section class="panel mt-6">
    <div class="panel-header"><h2>Transaksi {{ date('d M Y', strtotime($date)) }}</h2><span class="badge badge-neutral">{{ $transactions->count() }} records</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Jam</th><th>Pallet</th><th>PO</th><th>Buyer</th><th>Style</th><th>IN</th><th>OUT</th><th>Balance</th><th>Paraf</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($transactions as $row)
                <tr>
                    <td>{{ $row->transaction_time ? substr($row->transaction_time, 0, 5) : '—' }}</td><td>{{ $row->pallet ?? '—' }}</td><td>{{ $row->po_no ?? '—' }}</td>
                    <td>{{ $row->buyer?->code }}</td><td>{{ $row->sizeVariant?->code }}</td><td>{{ $row->qty_in }}</td><td>{{ $row->qty_out }}</td><td class="td-num">{{ $row->balance }}</td><td>{{ $row->paraf ?? '—' }}</td>
                    <td><div style="display:flex;gap:6px"><a class="btn btn-secondary btn-sm" href="/binding-reject-stock/{{ $row->id }}/edit?date={{ $date }}">Edit</a><form method="post" action="/binding-reject-stock/{{ $row->id }}" onsubmit="return confirm('Hapus transaksi ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty-state"><p>Belum ada transaksi pada tanggal ini.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
