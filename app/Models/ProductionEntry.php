<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'spk_id',
        'operator_id',
        'production_date',
        'shift',
        'input_time',
        'production_category',
        'buyer_id',
        'part_id',
        'size_variant_id',
        'process_id',
        'good_qty',
        'ng_qty',
        'reject_reason',
        'repairable_qty',
        'scrap_qty',
        'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
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

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
