<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class ProductionTrouble extends Model
{
    protected $fillable = [
        'spk_id',
        'operator_id',
        'production_date',
        'shift',
        'process_id',
        'trouble_type',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function getDurationMinutesAttribute(): int
    {
        $start = CarbonImmutable::parse($this->start_time);
        $end = CarbonImmutable::parse($this->end_time);

        if ($end->lessThan($start)) {
            $end = $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }
}
