@extends('production.layout', ['title' => 'Tambah Operator', 'subtitle' => 'Tambahkan kode dan nama operator produksi'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/masters/operators">Kembali ke List</a>
@endsection

@section('content')
<section class="panel">
    <div class="panel-header">
        <h2>Data Operator</h2>
    </div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="/masters/operators">
            @csrf
            <div class="field">
                <label>Kode Operator</label>
                <input name="operator_code" value="{{ old('operator_code') }}" placeholder="OP-001" maxlength="40" required>
            </div>
            <div class="field">
                <label>Nama Operator</label>
                <input name="name" value="{{ old('name') }}" placeholder="Nama lengkap operator" maxlength="120" required>
            </div>
            <button class="btn btn-primary" type="submit">Tambah Operator</button>
        </form>
    </div>
</section>
@endsection
