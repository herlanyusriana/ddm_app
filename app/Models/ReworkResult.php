<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReworkResult extends Model
{
    protected $fillable = ['production_entry_id', 'result_date', 'component', 'qty', 'operator_id', 'reject_notes'];

    protected $casts = ['result_date' => 'date'];

    public function productionEntry() { return $this->belongsTo(ProductionEntry::class); }
    public function operator() { return $this->belongsTo(Operator::class); }
}
