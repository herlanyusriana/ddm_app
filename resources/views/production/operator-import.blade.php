@extends('production.layout', ['title' => 'Import Operator Master', 'subtitle' => 'Upload data operator dari file XLSX'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/masters/operators">Kembali ke List</a>
@endsection

@section('content')
<div class="grid grid-2">
    <section class="panel">
        <div class="panel-header">
            <h2>Upload File</h2>
            <span class="badge badge-primary">XLSX Excel</span>
        </div>
        <div class="panel-body">
            <form class="form-grid" method="post" action="/masters/operators/import" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label>File XLSX</label>
                    <input type="file" name="file" accept=".xlsx" required>
                </div>
                <button class="btn btn-primary" type="submit">Import Operator Master</button>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Format Kolom</h2>
        </div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <tr><td>No</td><td>Wajib, angka unik. Contoh: 0012.</td></tr>
                        <tr><td>Nama</td><td>Wajib, nama operator.</td></tr>
                        <tr><td>QC LABEL</td><td>Opsional, angka. Contoh: 007.</td></tr>
                        <tr><td>Group</td><td>Opsional, nama leader.</td></tr>
                        <tr><td>Target Prod</td><td>Opsional, target per orang berupa angka bulat.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
