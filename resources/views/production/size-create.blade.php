@extends('production.layout', ['title' => 'Tambah Size'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Tambah Size</h1>
        <p>Input satu data size/code. Daftar size tetap di halaman Size / Code Master.</p>
    </div>
    <a class="link-btn primary" href="/masters/sizes">Kembali ke List</a>
</section>

<section class="panel">
    <div class="panel-head"><h2>Data Size</h2></div>
    <form class="compact-form" method="post" action="/masters/sizes">
        @csrf
        <label>Kode Size<input name="code" placeholder="12Q" required></label>
        <label>Nama Size<input name="name" placeholder="Queen"></label>
        <button class="btn primary" type="submit">Tambah Size</button>
    </form>
</section>
@endsection
