<?php
// app/Models/Employee.php - Enhanced for Container System

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Employee extends Model
{
    protected $fillable = [
        'employee_id', 'name', 'email', 'phone',
        'department_id', 'position', 'hire_date', 'status',
        'background_check_date', 'background_check_status',
        'background_check_notes', 'background_check_files',
        'notes', 'profile_photo_path'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'background_check_files' => 'array'
    ];

    // ===== RELATIONSHIPS =====

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->orderBy('issue_date', 'desc');
    }

    // ===== CONTAINER METHODS =====

    /**
     * Get complete employee container data
     */
    public function getContainerData()
    {
        return [
            'profile' => $this->getProfileData(),
            'background_check' => $this->getBackgroundCheckData(),
            'certificates' => $this->getCertificatesByType(),
            'statistics' => $this->getCertificateStatistics()
        ];
    }

    /**
     * Get profile information for container
     */
    public function getProfileData()
    {
        return [
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'position' => $this->position,
            'department' => $this->department?->name,
            'hire_date' => $this->hire_date?->format('d M Y'),
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status
        ];
    }

    /**
     * Get background check data with files
     */
    public function getBackgroundCheckData()
    {
        return [
            'status' => $this->background_check_status,
            'date' => $this->background_check_date?->format('d M Y'),
            'notes' => $this->background_check_notes,
            'files' => $this->background_check_files ?? [],
            'files_count' => count($this->background_check_files ?? [])
        ];
    }

    // ===== RECURRENT CERTIFICATE METHODS =====

    /**
     * Get certificates grouped by type with recurrent handling
     */
    public function getCertificatesByType()
    {
        $certificates = $this->employeeCertificates()
            ->with('certificateType')
            ->get()
            ->groupBy('certificate_type_id');

        $result = [];

        foreach ($certificates as $typeId => $certs) {
            $type = $certs->first()->certificateType;
            $current = $this->getCurrentCertificate($typeId);
            $history = $this->getCertificateHistory($typeId);

            $result[] = [
                'type_id' => $typeId,
                'type_name' => $type->name,
                'type_code' => $type->code,
                'current' => $current,
                'history' => $history,
                'total_count' => count($certs),
                'status_summary' => $this->getCertificateStatusSummary($certs)
            ];
        }

        return $result;
    }

    /**
     * Get current active certificate for a specific type
     */
    public function getCurrentCertificate($certificateTypeId)
    {
        return $this->employeeCertificates()
            ->where('certificate_type_id', $certificateTypeId)
            ->whereIn('status', ['active', 'expiring_soon', 'completed'])
            ->orderBy('issue_date', 'desc')
            ->first();
    }

    /**
     * Get certificate history for a specific type (excluding current)
     */
    public function getCertificateHistory($certificateTypeId)
    {
        $current = $this->getCurrentCertificate($certificateTypeId);

        return $this->employeeCertificates()
            ->where('certificate_type_id', $certificateTypeId)
            ->when($current, function($query, $current) {
                return $query->where('id', '!=', $current->id);
            })
            ->orderBy('issue_date', 'desc')
            ->get();
    }

    // ===== CERTIFICATE STATISTICS =====

    /**
     * Get certificate statistics for container dashboard
     */
    public function getCertificateStatistics()
    {
        $certificates = $this->employeeCertificates;

        return [
            'total' => $certificates->count(),
            'active' => $certificates->where('status', 'active')->count(),
            'expired' => $certificates->where('status', 'expired')->count(),
            'expiring_soon' => $certificates->where('status', 'expiring_soon')->count(),
            'pending' => $certificates->where('status', 'pending')->count(),
            'compliance_rate' => $this->calculateComplianceRate()
        ];
    }

    /**
     * Calculate compliance rate for container
     */
    public function calculateComplianceRate()
    {
        $mandatoryTypes = CertificateType::where('is_mandatory', true)->pluck('id');
        $totalMandatory = $mandatoryTypes->count();

        if ($totalMandatory === 0) return 100;

        $compliantCount = 0;
        foreach ($mandatoryTypes as $typeId) {
            $current = $this->getCurrentCertificate($typeId);
            if ($current && in_array($current->status, ['active', 'expiring_soon'])) {
                $compliantCount++;
            }
        }

        return round(($compliantCount / $totalMandatory) * 100, 2);
    }

    /**
     * Get certificates requiring attention
     */
    public function getCertificatesRequiringAttention()
    {
        return $this->employeeCertificates()
            ->whereIn('status', ['expired', 'expiring_soon', 'pending'])
            ->with('certificateType')
            ->orderBy('expiry_date')
            ->get();
    }

    // ===== HELPER METHODS =====

    private function getCertificateStatusSummary($certificates)
    {
        return [
            'active' => $certificates->where('status', 'active')->count(),
            'expired' => $certificates->where('status', 'expired')->count(),
            'expiring_soon' => $certificates->where('status', 'expiring_soon')->count(),
            'pending' => $certificates->where('status', 'pending')->count(),
        ];
    }
}

// app/Models/EmployeeCertificate.php - Certificate with Recurrent Logic

class EmployeeCertificate extends Model
{
    protected $fillable = [
        'employee_id', 'certificate_type_id', 'certificate_number',
        'issuer', 'training_provider', 'issue_date', 'expiry_date',
        'completion_date', 'training_date', 'status', 'certificate_files',
        'training_hours', 'cost', 'score', 'location', 'instructor_name',
        'notes', 'created_by_id', 'updated_by_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'completion_date' => 'date',
        'training_date' => 'date',
        'certificate_files' => 'array',
        'cost' => 'decimal:2',
        'training_hours' => 'decimal:2'
    ];

    // ===== RELATIONSHIPS =====

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }

    // ===== RECURRENT CERTIFICATE METHODS =====

    /**
     * Check if this is the current active certificate for its type
     */
    public function isCurrent()
    {
        $latest = self::where('employee_id', $this->employee_id)
            ->where('certificate_type_id', $this->certificate_type_id)
            ->whereIn('status', ['active', 'expiring_soon', 'completed'])
            ->orderBy('issue_date', 'desc')
            ->first();

        return $latest && $latest->id === $this->id;
    }

    /**
     * Get all previous certificates of the same type
     */
    public function getPreviousCertificates()
    {
        return self::where('employee_id', $this->employee_id)
            ->where('certificate_type_id', $this->certificate_type_id)
            ->where('issue_date', '<', $this->issue_date)
            ->orderBy('issue_date', 'desc')
            ->get();
    }

    /**
     * Auto-update certificate status based on dates
     */
    public function updateStatusBasedOnDates()
    {
        if (!$this->expiry_date) {
            return;
        }

        $today = Carbon::today();
        $expiryDate = Carbon::parse($this->expiry_date);
        $warningDate = $expiryDate->copy()->subDays($this->certificateType->warning_days ?? 90);

        if ($expiryDate->lt($today)) {
            // Expired
            $this->update(['status' => 'expired']);
        } elseif ($warningDate->lte($today)) {
            // Expiring soon
            $this->update(['status' => 'expiring_soon']);
        } elseif ($this->status === 'completed') {
            // Should be active
            $this->update(['status' => 'active']);
        }
    }

    // ===== FILE MANAGEMENT =====

    /**
     * Add file to certificate
     */
    public function addFile($filePath, $originalName, $size, $type)
    {
        $files = $this->certificate_files ?? [];

        $files[] = [
            'path' => $filePath,
            'original_name' => $originalName,
            'size' => $size,
            'type' => $type,
            'uploaded_at' => now()->toISOString()
        ];

        $this->update(['certificate_files' => $files]);
    }

    /**
     * Remove file from certificate
     */
    public function removeFile($index)
    {
        $files = $this->certificate_files ?? [];

        if (isset($files[$index])) {
            // Delete physical file
            Storage::disk('private')->delete($files[$index]['path']);

            // Remove from array
            unset($files[$index]);
            $files = array_values($files); // Re-index array

            $this->update(['certificate_files' => $files]);
        }
    }

    // ===== SCOPES FOR RECURRENT QUERIES =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('status', 'expiring_soon');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeOfType($query, $certificateTypeId)
    {
        return $query->where('certificate_type_id', $certificateTypeId);
    }

    /**
     * Scope to get current certificates only (latest for each type per employee)
     */
    public function scopeCurrent($query)
    {
        return $query->whereIn('status', ['active', 'expiring_soon', 'completed']);
    }
}

// app/Models/CertificateType.php - Master Certificate Data

class CertificateType extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'validity_months',
        'warning_days', 'is_mandatory', 'is_recurrent',
        'description', 'requirements', 'is_active'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_recurrent' => 'boolean',
        'is_active' => 'boolean'
    ];

    // ===== RELATIONSHIPS =====

    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    // ===== HELPER METHODS =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeRecurrent($query)
    {
        return $query->where('is_recurrent', true);
    }
}
