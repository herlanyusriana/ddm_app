<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Part;
use App\Models\SizeVariant;
use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $buyers = Buyer::where('is_active', true)->orderBy('name')->get();
        $parts  = Part::with('buyer')->where('classification', 'FG')->orderBy('code')->get();
        $sizes  = SizeVariant::where('is_active', true)->orderBy('production_code')->orderBy('code')->get();
        return view('production.spk.create', compact('buyers', 'parts', 'sizes'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->map(function ($item) {
                    if (($item['buyer_id'] ?? null) === '__new') {
                        $item['buyer_id'] = null;
                    }

                    return $item;
                })
                ->values()
                ->toArray(),
        ]);

        $validated = $request->validate([
            'spk_date' => ['required', 'date'],
            'dept' => ['required', 'string', 'max:160'],
            'month' => ['required', 'string', 'max:20'],
            'shift' => ['required', Rule::in(['1', '2', '3'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.buyer_id' => ['nullable', 'exists:buyers,id'],
            'items.*.buyer_name' => ['nullable', 'string', 'max:120'],
            'items.*.po_no' => ['required', 'string', 'max:80'],
            'items.*.item' => ['nullable', 'string', 'max:120'],
            'items.*.style' => ['nullable', 'string', 'max:120'],
            'items.*.part_id' => [
                'nullable',
                Rule::exists('parts', 'id')->where(fn ($query) => $query->where('classification', 'FG')),
            ],
            'items.*.size_variant_id' => ['nullable', 'exists:size_variants,id'],
            'items.*.target_qty' => ['required', 'integer', 'min:1'],
            'items.*.remarks' => ['nullable', 'string', 'max:120'],
        ]);

        $items = collect($validated['items'])
            ->map(fn ($item) => $this->hydrateSpkItemFromPart($item))
            ->values()
            ->all();

        $errors = [];
        foreach ($items as $index => $item) {
            if (empty($item['buyer_id']) && trim((string) ($item['buyer_name'] ?? '')) === '') {
                $errors["items.$index.buyer_id"] = 'Pilih part yang punya buyer, pilih buyer, atau isi buyer baru.';
            }
            if (trim((string) ($item['item'] ?? '')) === '') {
                $errors["items.$index.item"] = 'Item wajib diisi atau pilih Part Master yang punya nama.';
            }
            if (trim((string) ($item['style'] ?? '')) === '') {
                $errors["items.$index.style"] = 'Style wajib diisi atau pilih Part Master yang punya spec.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($validated, $items) {
            $spkNo = $this->generateSpkNumber($validated['spk_date']);

            foreach ($items as $item) {
                $buyer = $this->buyerForSpkItem($item);

                Spk::create([
                    'spk_no' => $spkNo,
                    'spk_date' => $validated['spk_date'],
                    'dept' => $validated['dept'],
                    'month' => $validated['month'],
                    'buyer_id' => $buyer->id,
                    'po_no' => $item['po_no'],
                    'item' => $item['item'],
                    'style' => $item['style'],
                    'part_id' => $item['part_id'] ?? null,
                    'size_variant_id' => $item['size_variant_id'] ?? null,
                    'target_qty' => $item['target_qty'],
                    'remarks' => $item['remarks'] ?? null,
                    'shift' => $validated['shift'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'Pending',
                ]);
            }
        });

        return redirect('/spk')->with('status', 'SPK berhasil dibuat.');
    }

    public function show(Spk $spk)
    {
        $spk->load(['buyer', 'part', 'sizeVariant', 'entries.process']);
        return view('production.spk.show', compact('spk'));
    }

    public function print(Spk $spk)
    {
        $spk->load(['buyer', 'part', 'sizeVariant']);
        $spkItems = Spk::with(['buyer', 'part', 'sizeVariant'])
            ->where('spk_no', $spk->spk_no)
            ->orderBy('id')
            ->get();

        return view('production.spk.print', compact('spk', 'spkItems'));
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
        $unitCards = collect(range(1, max(1, $spk->target_qty)))->map(fn ($number) => [
            'number' => $number,
            'code' => str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'total' => str_pad((string) max(1, $spk->target_qty), 3, '0', STR_PAD_LEFT),
        ]);

        return view('production.spk.kanban-card', compact('spk', 'unitCards'));
    }

    private function generateSpkNumber(string $date): string
    {
        $prefix = 'SPK-' . date('Ymd', strtotime($date)) . '-';
        $sequence = 1;

        do {
            $number = $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Spk::where('spk_no', $number)->exists());

        return $number;
    }

    private function buyerForSpkItem(array $item): Buyer
    {
        if (! empty($item['buyer_id'])) {
            return Buyer::findOrFail($item['buyer_id']);
        }

        $name = trim((string) ($item['buyer_name'] ?? ''));

        return Buyer::firstOrCreate(
            ['name' => $name],
            ['code' => $this->uniqueBuyerCode($name), 'is_active' => true]
        );
    }

    private function hydrateSpkItemFromPart(array $item): array
    {
        if (empty($item['part_id'])) {
            return $item;
        }

        $part = Part::find($item['part_id']);
        if (! $part) {
            return $item;
        }

        $item['buyer_id'] = ! empty($item['buyer_id']) ? $item['buyer_id'] : $part->buyer_id;
        $item['item'] = trim((string) ($item['item'] ?? '')) !== '' ? $item['item'] : $part->name;
        $item['style'] = trim((string) ($item['style'] ?? '')) !== '' ? $item['style'] : ($part->spec ?: $part->goods_description);
        $item['size_variant_id'] = ! empty($item['size_variant_id']) ? $item['size_variant_id'] : $this->sizeVariantIdFromPart($part);

        return $item;
    }

    private function sizeVariantIdFromPart(Part $part): ?int
    {
        $haystack = strtoupper($part->code.' '.$part->name.' '.$part->spec.' '.$part->item_no);

        return SizeVariant::all()
            ->sortByDesc(fn (SizeVariant $size) => strlen($size->code))
            ->first(fn (SizeVariant $size) => $size->code !== '' && str_contains($haystack, strtoupper($size->code)))
            ?->id;
    }

    private function uniqueBuyerCode(string $name): string
    {
        $base = strtoupper(Str::slug($name, ''));
        $base = substr($base !== '' ? $base : 'BUYER', 0, 12);
        $code = $base;
        $suffix = 1;

        while (Buyer::where('code', $code)->exists()) {
            $code = substr($base, 0, 9) . str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
            $suffix++;
        }

        return $code;
    }
}
