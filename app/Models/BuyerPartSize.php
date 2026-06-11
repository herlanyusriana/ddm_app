<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerPartSize extends Model
{
    use HasFactory;

    protected $fillable = ['buyer_id', 'part_id', 'size_variant_id', 'is_active'];

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
}
