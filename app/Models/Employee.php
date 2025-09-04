<?php
// app/Models/Employee.php - Updated for Container System

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'nip',
        'employee_id',
        'position',
        'department_id',
        'phone',
        'email',
        'hire_date',
        'status',
        'background_check_status',
        'background_check_date',
        'background_check_notes',
        'background_check_files',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'datetime',
        'background_check_files' => 'array',
    ];

    protected $dates = [
        'hire_date',
        'background_check_date',
        'deleted_at',
    ];

    /**
     * Get the department that the employee belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employee certificates
     */
    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    /**
     * Get active employee certificates
     */
    public function activeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)
                    ->where('status', 'active');
    }

    /**
     * Get expired employee certificates
     */
    public function expiredCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)
                    ->where('status', 'expired');
    }

    /**
     * Get expiring soon employee certificates
     */
    public function expiringSoonCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)
                    ->where('status', 'expiring_soon');
    }

    /**
     * Scope for active employees
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for employees with department
     */
    public function scopeWithDepartment($query)
    {
        return $query->with('department');
    }

    /**
     * Get container statistics
     */
    public function getContainerStatsAttribute()
    {
        return [
            'certificates_total' => $this->employeeCertificates()->count(),
            'certificates_active' => $this->activeCertificates()->count(),
            'certificates_expired' => $this->expiredCertificates()->count(),
            'certificates_expiring_soon' => $this->expiringSoonCertificates()->count(),
            'background_check_status' => $this->background_check_status,
            'background_check_files_count' => count($this->background_check_files ?? []),
            'has_background_check' => !empty($this->background_check_files),
        ];
    }

    /**
     * Get background check status badge color
     */
    public function getBackgroundCheckStatusColorAttribute()
    {
        return match($this->background_check_status) {
            'cleared' => 'green',
            'pending_review' => 'yellow',
            'in_progress' => 'blue',
            'requires_follow_up' => 'orange',
            'rejected' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get full container data for display
     */
    public function getContainerData()
    {
        $this->load([
            'department',
            'employeeCertificates.certificateType',
        ]);

        return [
            'profile' => [
                'id' => $this->id,
                'name' => $this->name,
                'nip' => $this->nip ?? $this->employee_id,
                'employee_id' => $this->employee_id ?? $this->nip,
                'position' => $this->position,
                'phone' => $this->phone,
                'email' => $this->email,
                'hire_date' => $this->hire_date?->format('Y-m-d'),
                'status' => $this->status ?? 'active',
                'department' => $this->department?->name,
            ],
            'background_check' => [
                'status' => $this->background_check_status ?? 'not_started',
                'date' => $this->background_check_date?->format('Y-m-d'),
                'notes' => $this->background_check_notes,
                'files' => $this->background_check_files ?? [],
                'files_count' => count($this->background_check_files ?? []),
            ],
            'certificates' => [
                'total' => $this->employeeCertificates->count(),
                'active' => $this->employeeCertificates->where('status', 'active')->count(),
                'expired' => $this->employeeCertificates->where('status', 'expired')->count(),
                'expiring_soon' => $this->employeeCertificates->where('status', 'expiring_soon')->count(),
                'items' => $this->employeeCertificates,
            ],
            'statistics' => $this->container_stats,
        ];
    }
}
