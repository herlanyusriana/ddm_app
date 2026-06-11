@extends('production.layout', ['title' => 'Tambah Buyer'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Tambah Buyer</h1>
        <p>Input satu data buyer. Daftar buyer tetap di halaman Buyer Master.</p>
    </div>
    <a class="link-btn primary" href="/masters/buyers">Kembali ke List</a>
</section>

<section class="panel">
    <div class="panel-head"><h2>Data Buyer</h2></div>
    <form class="compact-form" method="post" action="/masters/buyers">
        @csrf
        <label>Kode Buyer<input name="code" placeholder="AMZ" required></label>
        <label>Nama Buyer<input name="name" placeholder="Amazon" required></label>
        <button class="btn primary" type="submit">Tambah Buyer</button>
    </form>
</section>
@endsection
