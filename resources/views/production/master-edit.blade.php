@extends('production.layout', ['title' => 'Edit Master Data', 'subtitle' => 'Perbarui data tanpa mengubah relasi yang sudah tercatat'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/masters/{{ $type }}">Kembali ke List</a>
@endsection

@section('content')
<section class="panel">
    <div class="panel-header"><h2>Edit {{ ucfirst(rtrim($type, 's')) }}</h2></div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="/masters/{{ $type }}/{{ $record->id }}">
            @csrf
            @method('PUT')

            @if($type === 'buyers')
                <div class="form-row-2">
                    <div class="field"><label>Kode Buyer</label><input name="code" value="{{ old('code', $record->code) }}" required></div>
                    <div class="field"><label>Nama Buyer</label><input name="name" value="{{ old('name', $record->name) }}" required></div>
                </div>
                <div class="field"><label>Status</label><select name="is_active"><option value="1" @selected($record->is_active)>Aktif</option><option value="0" @selected(!$record->is_active)>Arsip</option></select></div>
            @elseif($type === 'operators')
                <div class="form-row">
                    <div class="field"><label>No</label><input name="operator_code" value="{{ old('operator_code', $record->operator_code) }}" required></div>
                    <div class="field"><label>Nama</label><input name="name" value="{{ old('name', $record->name) }}" required></div>
                    <div class="field"><label>QC Label</label><input name="qc_label" value="{{ old('qc_label', $record->qc_label) }}"></div>
                    <div class="field"><label>Group</label><input name="leader_name" value="{{ old('leader_name', $record->leader_name) }}"></div>
                    <div class="field"><label>Target Prod</label><input type="number" min="0" name="target_prod" value="{{ old('target_prod', $record->target_prod) }}"></div>
                </div>
            @elseif($type === 'parts')
                <div class="form-row">
                    <div class="field"><label>Buyer</label><select name="buyer_id"><option value="">Umum</option>@foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(old('buyer_id', $record->buyer_id) == $buyer->id)>{{ $buyer->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Kategori</label><select name="classification">@foreach(['FG','WIP','RM'] as $value)<option value="{{ $value }}" @selected(old('classification', $record->classification) === $value)>{{ $value }}</option>@endforeach</select></div>
                    <div class="field"><label>Code</label><input name="code" value="{{ old('code', $record->code) }}" required></div>
                    <div class="field"><label>Name</label><input name="name" value="{{ old('name', $record->name) }}" required></div>
                </div>
                <div class="form-row">
                    @foreach(['spec'=>'Spec','uom'=>'UOM','item_no'=>'Item No.','goods_description'=>'Goods Description'] as $field => $label)
                        <div class="field"><label>{{ $label }}</label><input name="{{ $field }}" value="{{ old($field, $record->$field) }}"></div>
                    @endforeach
                </div>
                <div class="form-row">
                    @foreach(['width_cm'=>'W (CM)','depth_cm'=>'D (CM)','height_cm'=>'H (CM)','cbm_per_unit'=>'CBM/Unit','net_weight_pc'=>'Net Wt/PC','gross_weight_pc'=>'Gross Wt/PC','package_box'=>'Package/Box'] as $field => $label)
                        <div class="field"><label>{{ $label }}</label><input type="number" min="0" step="any" name="{{ $field }}" value="{{ old($field, $record->$field) }}"></div>
                    @endforeach
                </div>
            @elseif($type === 'sizes')
                <div class="form-row">
                    <div class="field"><label>Code Produksi</label><select name="production_code">@foreach(['A','B'] as $value)<option value="{{ $value }}" @selected(old('production_code', $record->production_code) === $value)>{{ $value }}</option>@endforeach</select></div>
                    <div class="field"><label>Type</label><input name="code" value="{{ old('code', $record->code) }}" required></div>
                    <div class="field"><label>Point</label><input type="number" min="0" step="0.01" name="point" value="{{ old('point', $record->point) }}" required></div>
                    <div class="field"><label>Status</label><select name="is_active"><option value="1" @selected($record->is_active)>Aktif</option><option value="0" @selected(!$record->is_active)>Arsip</option></select></div>
                </div>
            @else
                <div class="form-row">
                    <div class="field"><label>Nama Proses</label><input name="name" value="{{ old('name', $record->name) }}" required></div>
                    <div class="field"><label>Urutan</label><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $record->sort_order) }}" required></div>
                    <div class="field"><label>Input Good/Reject</label><select name="is_input_process"><option value="1" @selected($record->is_input_process)>Ya</option><option value="0" @selected(!$record->is_input_process)>Tidak</option></select></div>
                    <div class="field"><label>Finish Good</label><select name="is_fg_process"><option value="1" @selected($record->is_fg_process)>Ya</option><option value="0" @selected(!$record->is_fg_process)>Tidak</option></select></div>
                </div>
            @endif

            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </div>
</section>
@endsection
