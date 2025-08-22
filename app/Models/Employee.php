<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'phone',
        'department_id',
        'position',
        'position_level',
        'employment_type',
        'hire_date',
        'supervisor_id',
        'status',
        'background_check_date',
        'background_check_status',
        'background_check_notes',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'profile_photo_path'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the department this employee belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the supervisor of this employee
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Get all employees supervised by this employee
     */
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * Get all training records for this employee
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get active training records
     */
    public function activeTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'active');
    }

    /**
     * Get expiring training records
     */
    public function expiringTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expiring_soon');
    }

    /**
     * Get expired training records
     */
    public function expiredTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expired');
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatusAttribute(): string
    {
        $totalRecords = $this->trainingRecords()->count();
        $activeRecords = $this->activeTrainingRecords()->count();

        if ($totalRecords === 0) {
            return 'no_records';
        }

        $complianceRate = ($activeRecords / $totalRecords) * 100;

        if ($complianceRate >= 90) {
            return 'compliant';
        } elseif ($complianceRate >= 70) {
            return 'partial';
        } else {
            return 'non_compliant';
        }
    }

    /**
     * Scope for active employees
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for employees with training records
     */
    public function scopeWithTrainingRecords(Builder $query)
    {
        return $query->has('trainingRecords');
    }
}
