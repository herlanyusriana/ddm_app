@extends('production.layout', ['title' => 'Buat SPK Baru', 'subtitle' => 'Nomor SPK dibuat otomatis oleh sistem'])

@section('topbar-actions')
    <a class="link-btn link-btn-secondary" href="/spk">← Kembali ke Daftar SPK</a>
@endsection

@section('content')
@php
    $oldItems = old('items', [[
        'buyer_id' => '',
        'buyer_name' => '',
        'po_no' => '',
        'item' => '',
        'style' => '',
        'part_id' => '',
        'size_variant_id' => '',
        'target_qty' => '',
        'remarks' => '',
    ]]);
@endphp

<style>
    .spk-create-shell {
        display: grid;
        gap: 16px;
        max-width: 1180px;
    }

    .spk-item-card {
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: #fff;
        overflow: hidden;
    }

    .spk-item-head {
        align-items: center;
        background: #f8fafc;
        border-bottom: 1px solid var(--line);
        display: flex;
        justify-content: space-between;
        padding: 12px 14px;
    }

    .spk-item-head strong {
        font-size: 14px;
        font-weight: 850;
    }

    .spk-item-body {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        padding: 14px;
    }

    .buyer-new-field {
        display: none;
    }

    .readonly-pill {
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid var(--line);
        border-radius: var(--radius-sm);
        color: var(--muted);
        display: flex;
        font-size: 14px;
        font-weight: 700;
        min-height: 42px;
        padding: 10px 12px;
    }

    .spk-item-card.use-new-buyer .buyer-new-field {
        display: flex;
    }

    @media (max-width: 900px) {
        .spk-item-body {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 760px) {
        .spk-item-body {
            grid-template-columns: 1fr;
        }
    }
</style>

<form class="spk-create-shell" method="post" action="/spk">
    @csrf

    <div class="panel">
        <div class="panel-header">
            <h2>Header SPK</h2>
            <span class="badge badge-primary">Auto Number</span>
        </div>
        <div class="panel-body">
            <div class="form-row">
                <div class="field">
                    <label>No SPK</label>
                    <div class="readonly-pill">Dibuat otomatis setelah simpan</div>
                </div>
                <div class="field">
                    <label>Tanggal SPK</label>
                    <input type="date" name="spk_date" value="{{ old('spk_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label>Month</label>
                    <input name="month" placeholder="Juni" value="{{ old('month', now()->locale('id')->translatedFormat('F')) }}" required>
                </div>
                <div class="field">
                    <label>Shift</label>
                    <select name="shift" required>
                        <option value="1" @selected(old('shift') === '1')>Shift 1</option>
                        <option value="2" @selected(old('shift') === '2')>Shift 2</option>
                        <option value="3" @selected(old('shift') === '3')>Shift 3</option>
                    </select>
                </div>
            </div>

            <div class="form-row-2 mt-4">
                <div class="field">
                    <label>Dept</label>
                    <input name="dept" placeholder="Hotmelt, Binding, Packing" value="{{ old('dept', 'Hotmelt, Binding, Packing') }}" required>
                </div>
                <div class="field">
                    <label>Catatan Header</label>
                    <input name="notes" placeholder="Catatan untuk semua item..." value="{{ old('notes') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Item Produksi</h2>
            <button class="btn btn-secondary btn-sm" type="button" data-add-spk-item>＋ Tambah Item</button>
        </div>
        <div class="panel-body">
            <div class="form-grid" data-spk-items>
                @foreach($oldItems as $index => $item)
                    <div class="spk-item-card" data-spk-item>
                        <div class="spk-item-head">
                            <strong>Item {{ $index + 1 }}</strong>
                            <button class="btn btn-danger btn-sm" type="button" data-remove-spk-item>Hapus</button>
                        </div>
                        <div class="spk-item-body">
                            <div class="field">
                                <label>Buyer</label>
                                <select name="items[{{ $index }}][buyer_id]" data-buyer-select>
                                    <option value="">— Pilih Buyer —</option>
                                    @foreach($buyers as $buyer)
                                        <option value="{{ $buyer->id }}" @selected(($item['buyer_id'] ?? '') == $buyer->id)>{{ $buyer->name }}</option>
                                    @endforeach
                                    <option value="__new" @selected(($item['buyer_id'] ?? '') === '__new')>＋ Buyer Baru</option>
                                </select>
                            </div>
                            <div class="field buyer-new-field">
                                <label>Nama Buyer Baru</label>
                                <input name="items[{{ $index }}][buyer_name]" placeholder="Wayfair" value="{{ $item['buyer_name'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>PO</label>
                                <input name="items[{{ $index }}][po_no]" placeholder="PO-MD-26-23" value="{{ $item['po_no'] ?? '' }}" required>
                            </div>
                            <div class="field">
                                <label>Item</label>
                                <input name="items[{{ $index }}][item]" placeholder="Pocket Spring" value="{{ $item['item'] ?? '' }}" required>
                            </div>
                            <div class="field">
                                <label>Style</label>
                                <input name="items[{{ $index }}][style]" placeholder='12" Queen' value="{{ $item['style'] ?? '' }}" required>
                            </div>
                            <div class="field">
                                <label>QTY Produksi</label>
                                <input type="number" name="items[{{ $index }}][target_qty]" min="1" value="{{ $item['target_qty'] ?? '' }}" required>
                            </div>
                            <div class="field">
                                <label>Remarks</label>
                                <input name="items[{{ $index }}][remarks]" placeholder="W~24" value="{{ $item['remarks'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Part Master</label>
                                <select name="items[{{ $index }}][part_id]">
                                    <option value="">— Opsional —</option>
                                    @foreach($parts as $part)
                                        <option value="{{ $part->id }}" @selected(($item['part_id'] ?? '') == $part->id)>{{ $part->code }} — {{ $part->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Size Master</label>
                                <select name="items[{{ $index }}][size_variant_id]">
                                    <option value="">— Opsional —</option>
                                    @foreach($sizes as $size)
                                        <option value="{{ $size->id }}" @selected(($item['size_variant_id'] ?? '') == $size->id)>{{ $size->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="btn btn-primary btn-lg" type="submit">📋 Terbitkan SPK</button>
        <a class="btn btn-secondary btn-lg" href="/spk">Batal</a>
    </div>
</form>

<template data-spk-item-template>
    <div class="spk-item-card" data-spk-item>
        <div class="spk-item-head">
            <strong>Item __NUMBER__</strong>
            <button class="btn btn-danger btn-sm" type="button" data-remove-spk-item>Hapus</button>
        </div>
        <div class="spk-item-body">
            <div class="field">
                <label>Buyer</label>
                <select name="items[__INDEX__][buyer_id]" data-buyer-select>
                    <option value="">— Pilih Buyer —</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                    @endforeach
                    <option value="__new">＋ Buyer Baru</option>
                </select>
            </div>
            <div class="field buyer-new-field">
                <label>Nama Buyer Baru</label>
                <input name="items[__INDEX__][buyer_name]" placeholder="Wayfair">
            </div>
            <div class="field"><label>PO</label><input name="items[__INDEX__][po_no]" placeholder="PO-MD-26-23" required></div>
            <div class="field"><label>Item</label><input name="items[__INDEX__][item]" placeholder="Pocket Spring" required></div>
            <div class="field"><label>Style</label><input name="items[__INDEX__][style]" placeholder='12" Queen' required></div>
            <div class="field"><label>QTY Produksi</label><input type="number" name="items[__INDEX__][target_qty]" min="1" required></div>
            <div class="field"><label>Remarks</label><input name="items[__INDEX__][remarks]" placeholder="W~24"></div>
            <div class="field">
                <label>Part Master</label>
                <select name="items[__INDEX__][part_id]">
                    <option value="">— Opsional —</option>
                    @foreach($parts as $part)
                        <option value="{{ $part->id }}">{{ $part->code }} — {{ $part->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Size Master</label>
                <select name="items[__INDEX__][size_variant_id]">
                    <option value="">— Opsional —</option>
                    @foreach($sizes as $size)
                        <option value="{{ $size->id }}">{{ $size->code }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</template>

<script>
    function refreshSpkItems() {
        document.querySelectorAll('[data-spk-item]').forEach((card, index) => {
            card.querySelector('.spk-item-head strong').textContent = `Item ${index + 1}`;
            card.querySelector('[data-remove-spk-item]').style.display = document.querySelectorAll('[data-spk-item]').length > 1 ? '' : 'none';
            card.querySelectorAll('input, select').forEach((field) => {
                field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
            });

            const buyerSelect = card.querySelector('[data-buyer-select]');
            card.classList.toggle('use-new-buyer', buyerSelect?.value === '__new');
        });
    }

    document.querySelector('[data-add-spk-item]')?.addEventListener('click', () => {
        const wrapper = document.querySelector('[data-spk-items]');
        const template = document.querySelector('[data-spk-item-template]').innerHTML;
        const index = document.querySelectorAll('[data-spk-item]').length;
        wrapper.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index).replaceAll('__NUMBER__', index + 1));
        refreshSpkItems();
    });

    document.addEventListener('click', (event) => {
        if (event.target.matches('[data-remove-spk-item]')) {
            event.target.closest('[data-spk-item]').remove();
            refreshSpkItems();
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-buyer-select]')) {
            refreshSpkItems();
        }
    });

    refreshSpkItems();
</script>
@endsection
