<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    protected $fillable = [
        'spk_no',
        'spk_date',
        'dept',
        'month',
        'buyer_id',
        'po_no',
        'item',
        'style',
        'part_id',
        'size_variant_id',
        'target_qty',
        'remarks',
        'shift',
        'status',
        'notes',
    ];

    protected $casts = [
        'spk_date' => 'date',
    ];

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function sizeVariant()
    {
        return $this->belongsTo(SizeVariant::class);
    }

    public function entries()
    {
        return $this->hasMany(ProductionEntry::class);
    }
}
