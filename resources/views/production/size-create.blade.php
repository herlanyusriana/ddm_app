@extends('production.layout', ['title' => 'Tambah Size', 'subtitle' => 'Input kombinasi code, type size, dan point produksi.'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/masters/sizes">Kembali ke List</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">
        <h2>Data Size</h2>
        <span class="badge badge-neutral">Master Size</span>
    </div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="/masters/sizes">
            @csrf
            <div class="form-row-3">
                <div class="field">
                    <label>Code Produksi</label>
                    <input name="production_code" value="{{ old('production_code') }}" placeholder="A / B / WF / AMZ" required>
                    <div class="field-hint">Bebas, tidak dibatasi A/B.</div>
                </div>
                <div class="field">
                    <label>Type Size</label>
                    <input name="code" value="{{ old('code') }}" placeholder="12Q" required>
                </div>
                <div class="field">
                    <label>Point</label>
                    <input name="point" type="number" min="0" step="0.01" value="{{ old('point') }}" placeholder="1.3" required>
                </div>
            </div>
            <button class="btn btn-primary btn-lg" type="submit">Tambah Size</button>
        </form>
    </div>
</div>
@endsection
