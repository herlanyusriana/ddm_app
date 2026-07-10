@extends('production.layout', ['title' => 'Edit Input Produksi', 'subtitle' => 'Koreksi input yang salah tanpa membuat catatan baru'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/input-proses?process_id={{ $entry->process_id }}&production_date={{ $entry->production_date->toDateString() }}&shift={{ $entry->shift }}">Kembali</a>
@endsection

@section('content')
<section class="panel">
    <div class="panel-header"><h2>{{ $entry->process?->name }} · {{ $entry->production_date->format('d M Y') }}</h2></div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="/production-entries/{{ $entry->id }}">
            @csrf
            @method('PUT')
            <div class="form-row-2">
                <div class="field"><label>Buyer</label><select name="buyer_id" required>@foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(old('buyer_id', $entry->buyer_id) == $buyer->id)>{{ $buyer->code }} · {{ $buyer->name }}</option>@endforeach</select></div>
                <div class="field"><label>Style / Code-Size</label><select name="size_variant_id" required>@foreach($sizes as $size)<option value="{{ $size->id }}" @selected(old('size_variant_id', $entry->size_variant_id) == $size->id)>{{ $size->display_label }}</option>@endforeach</select></div>
            </div>
            @if(strcasecmp($entry->process?->name ?? '', 'Binding') === 0)
                <div class="field"><label>Operator</label><select name="operator_id" required>@foreach($operators as $operator)<option value="{{ $operator->id }}" @selected(old('operator_id', $entry->operator_id) == $operator->id)>{{ $operator->operator_code }} · {{ $operator->name }}</option>@endforeach</select></div>
            @endif
            <div class="field">
                <label>Jam Input</label>
                <input type="time" name="input_time" value="{{ old('input_time', $entry->input_time ? substr((string) $entry->input_time, 0, 5) : $entry->created_at->setTimezone('Asia/Jakarta')->format('H:i')) }}" required>
                <div class="field-hint">Dipakai untuk menentukan Jam 1 sampai Jam 7 di report.</div>
            </div>
            <div class="form-row-2">
                <div class="field"><label>Good</label><input type="number" min="0" name="good_qty" value="{{ old('good_qty', $entry->good_qty) }}" required></div>
                <div class="field"><label>Reject</label><input type="number" min="0" name="reject_qty" value="{{ old('reject_qty', $entry->ng_qty) }}" required></div>
            </div>
            <div class="field">
                <label>Alasan Reject</label>
                <select name="reject_reason">
                    <option value="">— Pilih alasan reject —</option>
                    @foreach($rejectReasons as $reason)
                        <option value="{{ $reason }}" @selected(old('reject_reason', $entry->reject_reason) === $reason)>{{ $reason }}</option>
                    @endforeach
                </select>
                <div class="field-hint">Wajib jika Reject lebih dari 0.</div>
            </div>
            <div class="field"><label>Catatan</label><input name="notes" value="{{ old('notes', $entry->notes) }}"></div>
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </div>
</section>
@endsection
