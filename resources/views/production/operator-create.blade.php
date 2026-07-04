@extends('production.layout', ['title' => 'Tambah Operator', 'subtitle' => 'Tambahkan kode dan nama operator produksi'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/masters/operators">Kembali ke List</a>
@endsection

@section('content')
<style>
    .operator-production-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: .75fr 1.5fr 1fr 1.25fr 1fr;
    }

    @media (max-width: 900px) {
        .operator-production-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 600px) {
        .operator-production-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="panel">
    <div class="panel-header">
        <h2>Data Operator</h2>
    </div>
    <div class="panel-body">
        <form class="form-grid" method="post" action="/masters/operators">
            @csrf
            <div class="operator-production-grid">
                <div class="field">
                    <label>No</label>
                    <input name="operator_code" value="{{ old('operator_code') }}" placeholder="0012" maxlength="40" inputmode="numeric" pattern="[0-9]+" required>
                </div>
                <div class="field">
                    <label>Nama</label>
                    <input name="name" value="{{ old('name') }}" placeholder="Nama operator" maxlength="120" required>
                </div>
                <div class="field">
                    <label>QC Label</label>
                    <input name="qc_label" value="{{ old('qc_label') }}" placeholder="007" maxlength="40" inputmode="numeric" pattern="[0-9]+">
                </div>
                <div class="field">
                    <label>Group</label>
                    <input name="leader_name" value="{{ old('leader_name') }}" placeholder="Nama leader" maxlength="120">
                </div>
                <div class="field">
                    <label>Target Prod</label>
                    <input type="number" name="target_prod" value="{{ old('target_prod') }}" placeholder="250" min="0" step="1">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Tambah Operator</button>
        </form>
    </div>
</section>
@endsection
