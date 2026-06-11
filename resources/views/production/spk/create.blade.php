@extends('production.layout', ['title' => 'Buat SPK Baru', 'subtitle' => 'Isi form di bawah untuk menerbitkan SPK'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/spk">← Kembali ke Daftar SPK</a>
@endsection

@section('content')
<div class="panel" style="max-width:780px">
    <div class="panel-header">
        <h2>Form SPK</h2>
        <span class="badge badge-neutral">Surat Perintah Kerja</span>
    </div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="/spk">
            @csrf

            <div class="form-row">
                <div class="field">
                    <label>No SPK</label>
                    <input name="spk_no" placeholder="PO-MD-26-23" value="{{ old('spk_no') }}" required>
                </div>
                <div class="field">
                    <label>Tanggal SPK</label>
                    <input type="date" name="spk_date" value="{{ old('spk_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label>Month</label>
                    <input name="month" placeholder="Juni" value="{{ old('month') }}" required>
                </div>
                <div class="field">
                    <label>Shift</label>
                    <select name="shift" required>
                        <option value="1" @selected(old('shift') === '1')>Shift 1</option>
                        <option value="2" @selected(old('shift') === '2')>Shift 2</option>
                        <option value="3" @selected(old('shift') === '3')>Shift 3</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="field col-span-2">
                    <label>Dept</label>
                    <input name="dept" placeholder="Hotmelt, Binding, Packing" value="{{ old('dept') }}" required>
                </div>
                <div class="field">
                    <label>Buyer</label>
                    <select name="buyer_id" required>
                        <option value="">— Pilih Buyer —</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>PO</label>
                    <input name="po_no" placeholder="PO-MD-26-23" value="{{ old('po_no') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>Item</label>
                    <input name="item" placeholder="Bonel Spring" value="{{ old('item') }}" required>
                </div>
                <div class="field">
                    <label>Style</label>
                    <input name="style" placeholder='12" Queen' value="{{ old('style') }}" required>
                </div>
                <div class="field">
                    <label>QTY Produksi</label>
                    <input type="number" name="target_qty" min="1" value="{{ old('target_qty') }}" required>
                </div>
                <div class="field">
                    <label>Remarks</label>
                    <input name="remarks" placeholder="W~24" value="{{ old('remarks') }}">
                </div>
            </div>

            <div class="form-row">
                <div class="field col-span-2">
                    <label>Part Master (opsional)</label>
                    <select name="part_id">
                        <option value="">— Tidak pakai part master —</option>
                        @foreach($parts as $part)
                            <option value="{{ $part->id }}" @selected(old('part_id') == $part->id)>{{ $part->code }} — {{ $part->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Size Master (opsional)</label>
                    <select name="size_variant_id">
                        <option value="">— Tidak pakai size master —</option>
                        @foreach($sizes as $size)
                            <option value="{{ $size->id }}" @selected(old('size_variant_id') == $size->id)>{{ $size->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Catatan</label>
                    <input name="notes" placeholder="Catatan tambahan..." value="{{ old('notes') }}">
                </div>
            </div>

            <div class="divider"></div>

            <div class="flex gap-2">
                <button class="btn btn-primary btn-lg" type="submit">📋 Terbitkan SPK</button>
                <a class="btn btn-secondary btn-lg" href="/spk">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
