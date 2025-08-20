<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'validity_months',
        'category',
        'description',
        'is_active',
    ];
}
