<?php

// ========================================================================
// app/Models/Employee.php - Enhanced Employee Model
// ========================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'background_check_date' => 'date',
    ];

    /**
     * Get the department that owns the employee
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all training records for the employee
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get active training records
     */
    public function activeTrainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'active');
    }

    /**
     * Get expiring training records
     */
    public function expiringTrainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expiring_soon');
    }

    /**
     * Check if employee has valid certification for a training type
     */
    public function hasValidCertification($trainingTypeId): bool
    {
        return $this->trainingRecords()
            ->where('training_type_id', $trainingTypeId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get employee's compliance rate
     */
    public function getComplianceRateAttribute(): float
    {
        $totalRecords = $this->trainingRecords()->count();
        if ($totalRecords === 0) {
            return 0;
        }

        $activeRecords = $this->activeTrainingRecords()->count();
        return round(($activeRecords / $totalRecords) * 100, 2);
    }

    /**
     * Scope for active employees
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for employees in specific department
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
