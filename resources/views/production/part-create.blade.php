@extends('production.layout', ['title' => 'Tambah Part'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Tambah Part</h1>
        <p>Input satu data Part Master. Daftar part tetap di halaman Part Master.</p>
    </div>
    <a class="link-btn primary" href="/masters/parts">Kembali ke List</a>
</section>

<section class="panel">
    <div class="panel-head"><h2>Data Part</h2><span class="pill">Finish Good • WIP • RM</span></div>
    <form class="compact-form" method="post" action="/masters/parts">
        @csrf
        <div class="form-row">
            <label>Buyer
                <select name="buyer_id">
                    <option value="">Umum / semua buyer</option>
                    @foreach($buyers as $buyer)<option value="{{ $buyer->id }}">{{ $buyer->name }}</option>@endforeach
                </select>
            </label>
            <label>Kategori Part
                <select name="classification" required>
                    <option value="FG">Finish Good</option>
                    <option value="WIP">WIP</option>
                    <option value="RM">RM</option>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>Code<input name="code" placeholder="03.01.MAT-08T" required></label>
            <label>Name<input name="name" placeholder="8inch Spring mattress Twin-ORSM01-08T" required></label>
            <label>Spec<input name="spec" placeholder="75*39*8inch"></label>
            <label>Item No.<input name="item_no" placeholder="MAT-HY-BN-08T"></label>
        </div>
        <div class="form-row">
            <label>W (CM)<input type="number" step="0.01" min="0" name="width_cm" placeholder="28"></label>
            <label>D (CM)<input type="number" step="0.01" min="0" name="depth_cm" placeholder="28"></label>
            <label>H (CM)<input type="number" step="0.01" min="0" name="height_cm" placeholder="106"></label>
            <label>CBM/Unit<input type="number" step="0.0001" min="0" name="cbm_per_unit" placeholder="0.08"></label>
        </div>
        <div class="form-row">
            <label>Net W't/PC<input type="number" step="0.01" min="0" name="net_weight_pc" placeholder="12.26"></label>
            <label>Gross W't/PC<input type="number" step="0.01" min="0" name="gross_weight_pc" placeholder="13.76"></label>
            <label>Package/Box<input type="number" min="0" name="package_box" placeholder="1"></label>
            <label>Goods Description<input name="goods_description" placeholder="8 inch Hybrid Spring Mattress Twin"></label>
        </div>
        <button class="btn primary" type="submit">Tambah Part</button>
    </form>
</section>
@endsection
