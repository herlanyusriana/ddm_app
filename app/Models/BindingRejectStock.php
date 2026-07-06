<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BindingRejectStock extends Model
{
    protected $fillable = [
        'transaction_date',
        'transaction_time',
        'pallet',
        'po_no',
        'buyer_id',
        'size_variant_id',
        'qty_in',
        'qty_out',
        'paraf',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function sizeVariant()
    {
        return $this->belongsTo(SizeVariant::class);
    }
}
