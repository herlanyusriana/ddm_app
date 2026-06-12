<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'classification',
        'name',
        'spec',
        'uom',
        'width_cm',
        'depth_cm',
        'height_cm',
        'cbm_per_unit',
        'net_weight_pc',
        'gross_weight_pc',
        'package_box',
        'item_no',
        'goods_description',
        'is_active',
        'buyer_id',
    ];

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }
}
