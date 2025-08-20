<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;
use App\Models\TrainingType;

class TrainingRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'training_type_id',
        'certificate_number',
        'issuer',
        'issue_date',
        'expiry_date',
        'status',
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }
}
