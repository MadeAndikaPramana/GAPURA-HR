<?php

/// ===== app/Models/Department.php (Updated) =====
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description'
    ];

    // ===== RELATIONSHIPS =====

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function activeEmployees(): HasMany
    {
        return $this->hasMany(Employee::class)->where('status', 'active');
    }

    // Through employees to certificates
    public function employeeCertificates()
    {
        return $this->hasManyThrough(EmployeeCertificate::class, Employee::class);
    }

    // ===== HELPER METHODS =====

    /**
     * Get department container statistics
     */
    public function getContainerStats()
    {
        return [
            'total_employees' => $this->employees()->count(),
            'active_employees' => $this->activeEmployees()->count(),
            'total_containers' => $this->employees()->whereNotNull('container_created_at')->count(),
            'total_certificates' => $this->employeeCertificates()->count(),
            'employees_with_bg_check' => $this->employees()
                ->whereNotNull('background_check_files')
                ->count()
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
