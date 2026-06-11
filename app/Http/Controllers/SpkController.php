<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Part;
use App\Models\SizeVariant;
use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpkController extends Controller
{
    public function index()
    {
        $spks = Spk::with(['buyer', 'part', 'sizeVariant'])->latest()->get();
        return view('production.spk.index', compact('spks'));
    }

    public function create()
    {
        $buyers = Buyer::orderBy('name')->get();
        $parts  = Part::orderBy('code')->get();
        $sizes  = SizeVariant::orderBy('code')->get();
        return view('production.spk.create', compact('buyers', 'parts', 'sizes'));
    }

    public function store(Request $request)
    {
        Spk::create($request->validate([
            'spk_no' => ['required', 'string', 'max:80'],
            'spk_date' => ['required', 'date'],
            'dept' => ['required', 'string', 'max:160'],
            'month' => ['required', 'string', 'max:20'],
            'buyer_id' => ['required', 'exists:buyers,id'],
            'po_no' => ['required', 'string', 'max:80'],
            'item' => ['required', 'string', 'max:120'],
            'style' => ['required', 'string', 'max:120'],
            'part_id' => ['nullable', 'exists:parts,id'],
            'size_variant_id' => ['nullable', 'exists:size_variants,id'],
            'target_qty' => ['required', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string', 'max:120'],
            'shift' => ['required', Rule::in(['1', '2', '3'])],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect('/spk')->with('status', 'SPK berhasil dibuat.');
    }

    public function show(Spk $spk)
    {
        $spk->load(['buyer', 'part', 'sizeVariant', 'entries.process']);
        return view('production.spk.show', compact('spk'));
    }

    public function destroy(Spk $spk)
    {
        $spk->delete();

        return redirect('/spk')->with('status', 'SPK berhasil dihapus.');
    }

    public function warehouseIndex()
    {
        $spks = Spk::with(['buyer', 'part', 'sizeVariant'])
            ->whereIn('status', ['Pending', 'Material Prepared'])
            ->latest()
            ->get();
        return view('production.warehouse.index', compact('spks'));
    }

    public function warehousePrepare(Spk $spk)
    {
        if ($spk->status === 'Pending') {
            $spk->update(['status' => 'Material Prepared']);
            return back()->with('status', 'Material SPK ' . $spk->spk_no . ' berhasil disiapkan.');
        }
        return back()->withErrors('SPK tidak dalam status Pending.');
    }

    public function kanbanCard(Spk $spk)
    {
        $spk->load(['buyer', 'part', 'sizeVariant']);
        return view('production.spk.kanban-card', compact('spk'));
    }
}
