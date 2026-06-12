@extends('production.layout', ['title' => 'Import Size Master'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Import Size Master</h1>
        <p>Upload file CSV dari Excel. Data dengan code yang sama akan diupdate.</p>
    </div>
    <a class="link-btn link-btn-primary" href="/masters/sizes">Kembali ke List</a>
</section>

<section class="grid two">
    <article class="panel">
        <div class="panel-head"><h2>Upload File</h2><span class="pill">CSV Excel</span></div>
        <form class="compact-form" method="post" action="/masters/sizes/import" enctype="multipart/form-data">
            @csrf
            <label>File CSV
                <input type="file" name="file" accept=".csv,.txt" required>
            </label>
            <button class="btn primary" type="submit">Import Size Master</button>
        </form>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Format Kolom</h2></div>
        <table>
            <thead><tr><th>Kolom</th><th>Keterangan</th></tr></thead>
            <tbody>
                <tr><td>code</td><td>Wajib. Contoh: 12Q, 8T, 14K.</td></tr>
                <tr><td>name</td><td>Opsional. Contoh: Queen, Twin, King.</td></tr>
            </tbody>
        </table>
    </article>
</section>
@endsection
