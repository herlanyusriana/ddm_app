<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeVariant extends Model
{
    use HasFactory;

    protected $fillable = ['production_code', 'code', 'name', 'point', 'is_active'];

    protected $casts = [
        'point' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getDisplayLabelAttribute(): string
    {
        $prefix = $this->production_code ? $this->production_code.' - ' : '';
        $point = $this->point === null
            ? ''
            : ' ('.rtrim(rtrim(number_format((float) $this->point, 2, '.', ''), '0'), '.').' point)';

        return $prefix.$this->code.$point;
    }
}
