<?php
// app/Models/Employee.php (Updated for NIK field)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',              // NEW: NIK field
        'employee_id',      // NIP
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
        'background_check_files',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'profile_photo_path',
        'notes'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'background_check_files' => 'array',
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
     * Get all certificates for this employee
     */
    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->orderBy('issue_date', 'desc');
    }

    /**
     * Get active certificates only
     */
    public function activeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'active');
    }

    /**
     * Get expired certificates
     */
    public function expiredCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'expired');
    }

    /**
     * Get certificates expiring soon
     */
    public function expiringSoonCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'expiring_soon');
    }

    /**
     * Scope to search employees - Updated to include NIK
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")  // NIP
                  ->orWhere('nik', 'like', "%{$search}%")          // NEW: NIK search
                  ->orWhere('position', 'like', "%{$search}%")     // NEW: Position search
                  ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to get employees by department
     */
    public function scopeInDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to get active employees
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get inactive employees
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Check if employee data is complete
     */
    public function isDataComplete(): bool
    {
        return !empty($this->employee_id) &&
               !empty($this->name) &&
               !empty($this->position);
    }

    /**
     * Get completion percentage of employee data
     */
    public function getDataCompletionAttribute(): int
    {
        $requiredFields = ['employee_id', 'name', 'position', 'department_id'];
        $completedFields = 0;

        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * Get employee summary for listings
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->employee_id,
            'name' => $this->name,
            'position' => $this->position,
            'department' => $this->department?->name,
            'status' => $this->status,
            'completion' => $this->data_completion,
            'is_complete' => $this->isDataComplete()
        ];
    }
}
