@extends('production.layout', ['title' => 'Import Part Master'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Import Part Master</h1>
        <p>Upload file CSV dari Excel. Data dengan code yang sama akan diupdate.</p>
    </div>
    <a class="link-btn link-btn-primary" href="/masters/parts">Kembali ke List</a>
</section>

<section class="grid two">
    <article class="panel">
        <div class="panel-head"><h2>Upload File</h2><span class="pill">CSV Excel</span></div>
        <form class="compact-form" method="post" action="/masters/parts/import" enctype="multipart/form-data">
            @csrf
            <label>File CSV
                <input type="file" name="file" accept=".csv,.txt" required>
            </label>
            <button class="btn primary" type="submit">Import Part Master</button>
        </form>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Format Kolom</h2></div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr><td>buyer_code</td><td>Opsional. Isi kode/nama buyer jika part khusus buyer tertentu.</td></tr>
                    <tr><td>classification</td><td>FG, WIP, atau RM.</td></tr>
                    <tr><td>code</td><td>Wajib. Kode part unik.</td></tr>
                    <tr><td>name</td><td>Wajib. Nama part.</td></tr>
                    <tr><td>spec</td><td>Opsional.</td></tr>
                    <tr><td>width_cm, depth_cm, height_cm</td><td>Opsional, angka.</td></tr>
                    <tr><td>cbm_per_unit, net_weight_pc, gross_weight_pc</td><td>Opsional, angka.</td></tr>
                    <tr><td>package_box</td><td>Opsional, angka bulat.</td></tr>
                    <tr><td>item_no, goods_description</td><td>Opsional.</td></tr>
                </tbody>
            </table>
        </div>
    </article>
</section>
@endsection
