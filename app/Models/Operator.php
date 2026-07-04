<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_code',
        'name',
        'qc_label',
        'leader_name',
        'target_prod',
    ];
}
