@extends('production.layout', ['title' => 'Tambah Size'])

@section('content')
<section class="topbar">
    <div class="title">
        <h1>Tambah Size</h1>
        <p>Input kombinasi code, type size, dan point produksi.</p>
    </div>
    <a class="link-btn primary" href="/masters/sizes">Kembali ke List</a>
</section>

<section class="panel">
    <div class="panel-head"><h2>Data Size</h2></div>
    <form class="compact-form" method="post" action="/masters/sizes">
        @csrf
        <label>Code
            <select name="production_code" required>
                <option value="A">A</option>
                <option value="B">B</option>
            </select>
        </label>
        <label>Type<input name="code" placeholder="12Q" required></label>
        <label>Point<input name="point" type="number" min="0" step="0.01" placeholder="1.3" required></label>
        <button class="btn primary" type="submit">Tambah Size</button>
    </form>
</section>
@endsection
