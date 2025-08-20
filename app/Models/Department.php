<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Get all employees in this department
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get active employees in this department
     */
    public function activeEmployees(): HasMany
    {
        return $this->hasMany(Employee::class)->where('status', 'active');
    }

    /**
     * Get all training records through employees
     */
    public function trainingRecords()
    {
        return $this->hasManyThrough(TrainingRecord::class, Employee::class);
    }

    /**
     * Get department compliance statistics
     */
    public function getComplianceStatsAttribute(): array
    {
        $totalEmployees = $this->activeEmployees()->count();
        $trainingRecords = $this->trainingRecords();

        $total = $trainingRecords->count();
        $active = $trainingRecords->where('status', 'active')->count();
        $expiring = $trainingRecords->where('status', 'expiring_soon')->count();
        $expired = $trainingRecords->where('status', 'expired')->count();

        return [
            'total_employees' => $totalEmployees,
            'total_certificates' => $total,
            'active_certificates' => $active,
            'expiring_certificates' => $expiring,
            'expired_certificates' => $expired,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Scope for departments with employees
     */
    public function scopeWithEmployees($query)
    {
        return $query->has('employees');
    }
}

