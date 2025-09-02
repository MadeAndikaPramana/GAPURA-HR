<?php
// app/Models/Employee.php (Updated for Container System)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

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
        'background_check_files', // NEW: JSON field for file attachments
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'profile_photo_path',
        'notes' // NEW: General notes field
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'background_check_files' => 'array', // NEW: Cast to array
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
     * NEW: Get all certificates for this employee (replaces trainingRecords)
     */
    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->orderBy('issue_date', 'desc');
    }

    /**
     * NEW: Get active certificates only
     */
    public function activeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'active');
    }

    /**
     * NEW: Get expired certificates
     */
    public function expiredCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'expired');
    }

    /**
     * NEW: Get certificates expiring soon
     */
    public function expiringSoonCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'expiring_soon');
    }

    /**
     * LEGACY: Keep old relationship for backward compatibility
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * LEGACY: Keep old methods for backward compatibility
     */
    public function activeTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'active');
    }

    public function expiringTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expiring_soon');
    }

    public function expiredTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expired');
    }

    /**
     * NEW: Get current certificates grouped by type (for recurrent certificate display)
     */
    public function getCurrentCertificatesByType()
    {
        return $this->employeeCertificates()
                   ->with('certificateType')
                   ->whereIn('status', ['active', 'expiring_soon'])
                   ->get()
                   ->groupBy('certificate_type_id')
                   ->map(function ($certificates) {
                       // Return the most recent certificate for each type
                       return $certificates->sortByDesc('issue_date')->first();
                   });
    }

    /**
     * NEW: Get certificate history grouped by type
     */
    public function getCertificateHistoryByType()
    {
        return $this->employeeCertificates()
                   ->with('certificateType')
                   ->get()
                   ->groupBy('certificate_type_id');
    }

    /**
     * NEW: Get compliance statistics for this employee
     */
    public function getComplianceStatistics(): array
    {
        $totalCerts = $this->employeeCertificates()->count();
        $activeCerts = $this->activeCertificates()->count();
        $expiredCerts = $this->expiredCertificates()->count();
        $expiringSoon = $this->expiringSoonCertificates()->count();

        return [
            'total_certificates' => $totalCerts,
            'active_certificates' => $activeCerts,
            'expired_certificates' => $expiredCerts,
            'expiring_soon_certificates' => $expiringSoon,
            'compliance_rate' => $totalCerts > 0 ? round(($activeCerts / $totalCerts) * 100, 2) : 100,
            'requires_attention' => $expiredCerts + $expiringSoon
        ];
    }

    /**
     * NEW: Get background check files with download URLs
     */
    public function getBackgroundCheckFilesWithUrlsAttribute(): array
    {
        if (!$this->background_check_files) {
            return [];
        }

        return collect($this->background_check_files)->map(function ($file) {
            $file['url'] = route('employees.background-check.download', [
                'employee' => $this->id,
                'file' => $file['stored_name']
            ]);
            return $file;
        })->toArray();
    }

    /**
     * NEW: Add background check file
     */
    public function addBackgroundCheckFile(array $fileData): void
    {
        $files = $this->background_check_files ?? [];
        $files[] = $fileData;
        $this->background_check_files = $files;
        $this->save();
    }

    /**
     * NEW: Remove background check file
     */
    public function removeBackgroundCheckFile(string $fileName): bool
    {
        $files = $this->background_check_files ?? [];
        $filteredFiles = collect($files)->reject(function ($file) use ($fileName) {
            return $file['stored_name'] === $fileName;
        })->values()->toArray();

        $this->background_check_files = $filteredFiles;
        $this->save();

        return true;
    }

    /**
     * NEW: Check if background check is current
     */
    public function isBackgroundCheckCurrent(): bool
    {
        if (!$this->background_check_date || $this->background_check_status !== 'cleared') {
            return false;
        }

        // Background checks are typically valid for 2 years
        $expiryDate = Carbon::parse($this->background_check_date)->addYears(2);
        return Carbon::now()->lte($expiryDate);
    }

    /**
     * NEW: Get background check status label
     */
    public function getBackgroundCheckStatusLabelAttribute(): string
    {
        return match($this->background_check_status) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'cleared' => 'Cleared',
            'pending_review' => 'Pending Review',
            'requires_follow_up' => 'Requires Follow-up',
            'expired' => 'Expired',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * NEW: Get background check status color for UI
     */
    public function getBackgroundCheckStatusColorAttribute(): string
    {
        return match($this->background_check_status) {
            'not_started' => 'gray',
            'in_progress' => 'blue',
            'cleared' => 'green',
            'pending_review' => 'yellow',
            'requires_follow_up' => 'orange',
            'expired' => 'red',
            'rejected' => 'red',
            default => 'gray'
        };
    }

    /**
     * NEW: Get employee container summary (for dashboard)
     */
    public function getContainerSummary(): array
    {
        $compliance = $this->getComplianceStatistics();

        return [
            'employee' => [
                'id' => $this->id,
                'employee_id' => $this->employee_id,
                'name' => $this->name,
                'position' => $this->position,
                'department' => $this->department->name ?? 'Unknown',
                'status' => $this->status
            ],
            'compliance' => $compliance,
            'background_check' => [
                'status' => $this->background_check_status,
                'date' => $this->background_check_date?->format('Y-m-d'),
                'is_current' => $this->isBackgroundCheckCurrent(),
                'files_count' => count($this->background_check_files ?? [])
            ],
            'last_updated' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Scope to search employees
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
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
}
