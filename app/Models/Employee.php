<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Department;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'department_id',
        'position',
        'status',
        'background_check_date',
        'background_check_notes',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
