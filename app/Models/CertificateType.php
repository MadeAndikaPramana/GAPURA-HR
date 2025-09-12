<?php
// app/Models/CertificateType.php - Fixed with all missing methods

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'validity_months', 'warning_days',
        'is_mandatory', 'is_recurrent', 'description', 'requirements',
        'learning_objectives', 'is_active', 'estimated_cost', 'estimated_duration_hours'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_recurrent' => 'boolean',
        'is_active' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
    ];

    // ===== RELATIONSHIPS =====

    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    // ===== SCOPES =====

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

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // ===== MISSING METHODS - FIX FOR ERROR =====

    /**
     * Get active certificates count for this type
     */
    public function getActiveCertificatesCount()
    {
        return $this->employeeCertificates()->where('status', 'active')->count();
    }

    /**
     * Get expired certificates count for this type
     */
    public function getExpiredCertificatesCount()
    {
        return $this->employeeCertificates()->where('status', 'expired')->count();
    }

    /**
     * Get expiring soon certificates count for this type
     */
    public function getExpiringSoonCertificatesCount()
    {
        return $this->employeeCertificates()->where('status', 'expiring_soon')->count();
    }

    /**
     * Get total certificates count for this type
     */
    public function getTotalCertificatesCount()
    {
        return $this->employeeCertificates()->count();
    }

    // ===== THESE METHODS WERE MISSING - CAUSING THE ERROR =====

    /**
     * Get active certificates relationship
     */
    public function activeCertificates()
    {
        return $this->employeeCertificates()->where('status', 'active');
    }

    /**
     * Get expired certificates relationship
     */
    public function expiredCertificates()
    {
        return $this->employeeCertificates()->where('status', 'expired');
    }

    /**
     * Get expiring soon certificates relationship
     */
    public function expiringSoonCertificates()
    {
        return $this->employeeCertificates()->where('status', 'expiring_soon');
    }

    // ===== UTILITY METHODS =====

    /**
     * Check if certificate type is required
     */
    public function isRequired()
    {
        return $this->is_mandatory;
    }

    /**
     * Check if certificate type can be renewed
     */
    public function canBeRenewed()
    {
        return $this->is_recurrent;
    }

    /**
     * Get validity period in days
     */
    public function getValidityInDays()
    {
        return $this->validity_months ? $this->validity_months * 30 : null;
    }

    /**
     * Get training type information
     */
    public function getTypeInfo()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'description' => $this->description,
            'validity_months' => $this->validity_months,
            'warning_days' => $this->warning_days,
            'is_mandatory' => $this->is_mandatory,
            'is_recurrent' => $this->is_recurrent,
            'estimated_cost' => $this->estimated_cost,
            'estimated_duration_hours' => $this->estimated_duration_hours,
            'is_active' => $this->is_active
        ];
    }

    /**
     * Get employees who have this certificate type
     * Returns collection with latest certificate info
     */
    public function getEmployeesWithCertificate()
    {
        return Employee::whereHas('employeeCertificates', function($query) {
            $query->where('certificate_type_id', $this->id);
        })
        ->with([
            'employeeCertificates' => function($query) {
                $query->where('certificate_type_id', $this->id)
                      ->orderBy('issue_date', 'desc');
            },
            'department'
        ])
        ->get()
        ->map(function($employee) {
            $latestCert = $employee->employeeCertificates->first();
            $allCerts = $employee->employeeCertificates;

            return [
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'department' => $employee->department?->name,
                    'position' => $employee->position,
                    'status' => $employee->status
                ],
                'latest_certificate' => $latestCert ? [
                    'id' => $latestCert->id,
                    'certificate_number' => $latestCert->certificate_number,
                    'issue_date' => $latestCert->issue_date?->format('d M Y'),
                    'expiry_date' => $latestCert->expiry_date?->format('d M Y'),
                    'status' => $latestCert->status,
                    'issuer' => $latestCert->issuer,
                    'training_provider' => $latestCert->training_provider,
                    'score' => $latestCert->score,
                    'files_count' => count($latestCert->certificate_files ?? [])
                ] : null,
                'certificates_history' => [
                    'total_count' => $allCerts->count(),
                    'active_count' => $allCerts->where('status', 'active')->count(),
                    'expired_count' => $allCerts->where('status', 'expired')->count(),
                    'expiring_soon_count' => $allCerts->where('status', 'expiring_soon')->count()
                ],
                'container_link' => route('employee-containers.show', $employee->id)
            ];
        });
    }

    /**
     * Get container statistics for this training type
     */
    public function getContainerStatistics()
    {
        $totalCertificates = $this->employeeCertificates()->count();
        $activeCertificates = $this->employeeCertificates()->where('status', 'active')->count();
        $expiredCertificates = $this->employeeCertificates()->where('status', 'expired')->count();
        $expiringSoonCertificates = $this->employeeCertificates()->where('status', 'expiring_soon')->count();

        // Get unique employees with this certificate
        $uniqueEmployees = $this->employeeCertificates()
                                ->distinct('employee_id')
                                ->count();

        // Calculate compliance rate if mandatory
        $complianceRate = null;
        if ($this->is_mandatory) {
            $totalActiveEmployees = Employee::where('status', 'active')->count();
            $employeesWithValidCert = $this->employeeCertificates()
                                           ->where('status', 'active')
                                           ->distinct('employee_id')
                                           ->count();

            $complianceRate = $totalActiveEmployees > 0
                ? round(($employeesWithValidCert / $totalActiveEmployees) * 100, 1)
                : 0;
        }

        return [
            'total_certificates' => $totalCertificates,
            'active_certificates' => $activeCertificates,
            'expired_certificates' => $expiredCertificates,
            'expiring_soon_certificates' => $expiringSoonCertificates,
            'unique_employees' => $uniqueEmployees,
            'compliance_rate' => $complianceRate,
            'recency_score' => $this->calculateRecencyScore(),
            'average_score' => $this->calculateAverageScore()
        ];
    }

    /**
     * Get certificate status distribution
     */
    public function getCertificateStatusDistribution()
    {
        return $this->employeeCertificates()
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();
    }

    /**
     * Calculate recency score (percentage of certificates issued in last 6 months)
     */
    private function calculateRecencyScore()
    {
        $totalCertificates = $this->employeeCertificates()->count();

        if ($totalCertificates === 0) {
            return 0;
        }

        $recentCertificates = $this->employeeCertificates()
                                   ->where('created_at', '>=', now()->subMonths(6))
                                   ->count();

        return round(($recentCertificates / $totalCertificates) * 100, 1);
    }

    /**
     * Calculate average score for certificates of this type
     */
    private function calculateAverageScore()
    {
        return $this->employeeCertificates()
                    ->whereNotNull('score')
                    ->avg('score');
    }

    /**
     * Get employees filtered by status and department
     */
    public function getFilteredEmployees($status = null, $departmentId = null)
    {
        $query = $this->getEmployeesWithCertificate();

        if ($status) {
            $query = $query->filter(function($employee) use ($status) {
                return $employee['latest_certificate']
                    && $employee['latest_certificate']['status'] === $status;
            });
        }

        if ($departmentId) {
            $query = $query->filter(function($employee) use ($departmentId) {
                return $employee['employee']['department_id'] == $departmentId;
            });
        }

        return $query->values();
    }

    public function certificateFiles(): HasMany
{
    return $this->hasMany(FileStorage::class)->where('status', 'stored');
}

/**
 * Latest version files for each employee with this certificate type
 */
public function latestCertificateFiles(): HasMany
{
    return $this->hasMany(FileStorage::class)
        ->where('status', 'stored')
        ->whereIn('id', function($query) {
            $query->selectRaw('MAX(id)')
                ->from('file_storage')
                ->where('status', 'stored')
                ->where('certificate_type_id', $this->id)
                ->groupBy(['employee_id', 'certificate_type_id']);
        });
}

/**
 * Valid (not expired) certificate files for this type
 */
public function validCertificateFiles(): HasMany
{
    return $this->certificateFiles()
        ->where('expiry_date', '>=', now())
        ->where('issue_date', '<=', now());
}

/**
 * Expired certificate files for this type
 */
public function expiredCertificateFiles(): HasMany
{
    return $this->certificateFiles()
        ->where('expiry_date', '<', now());
}

/**
 * Files expiring soon (within 30 days) for this type
 */
public function expiringSoonCertificateFiles(): HasMany
{
    return $this->certificateFiles()
        ->whereBetween('expiry_date', [now(), now()->addDays(30)]);
}

/**
 * Get employees who have this certificate type
 */
public function employeesWithCertificate()
{
    return Employee::whereHas('certificateFiles', function($query) {
        $query->where('certificate_type_id', $this->id);
    })->with(['certificateFiles' => function($query) {
        $query->where('certificate_type_id', $this->id)
              ->orderBy('version_number', 'desc');
    }]);
}

/**
 * Get employees who have valid certificates for this type
 */
public function employeesWithValidCertificate()
{
    return Employee::whereHas('validCertificateFiles', function($query) {
        $query->where('certificate_type_id', $this->id);
    })->with(['validCertificateFiles' => function($query) {
        $query->where('certificate_type_id', $this->id);
    }]);
}

/**
 * Get employees missing this mandatory certificate
 */
public function employeesMissingCertificate()
{
    if (!$this->is_mandatory) {
        return collect();
    }

    return Employee::where('status', 'active')
        ->whereDoesntHave('validCertificateFiles', function($query) {
            $query->where('certificate_type_id', $this->id);
        })->get();
}

/**
 * Get certificate statistics for this certificate type
 */
public function getCertificateStats(): array
{
    $allFiles = $this->certificateFiles;
    $latestFiles = $this->latestCertificateFiles;

    return [
        'total_files' => $allFiles->count(),
        'total_versions' => $allFiles->sum('version_number'),
        'unique_employees' => $latestFiles->count(),
        'valid_certificates' => $latestFiles->where('validity_status', 'valid')->count(),
        'expiring_soon' => $latestFiles->where('validity_status', 'expiring_soon')->count(),
        'expired_certificates' => $latestFiles->where('validity_status', 'expired')->count(),
        'average_version' => $allFiles->count() > 0 ? round($allFiles->avg('version_number'), 1) : 0,
        'total_file_size' => $allFiles->sum('file_size'),
        'compliance_rate' => $this->calculateComplianceRate()
    ];
}

/**
 * Calculate compliance rate for mandatory certificates
 */
public function calculateComplianceRate(): ?float
{
    if (!$this->is_mandatory) {
        return null;
    }

    $totalActiveEmployees = Employee::where('status', 'active')->count();
    $employeesWithValidCert = $this->validCertificateFiles()
        ->distinct('employee_id')
        ->count();

    if ($totalActiveEmployees === 0) {
        return 0;
    }

    return round(($employeesWithValidCert / $totalActiveEmployees) * 100, 2);
}

/**
 * Get container data for this certificate type (for Training Type Container view)
 */
public function getContainerData(): array
{
    $employees = $this->employeesWithCertificate()->get();

    $employeesData = $employees->map(function($employee) {
        $certificates = $employee->certificateFiles->where('certificate_type_id', $this->id);
        $latestCert = $certificates->sortByDesc('version_number')->first();

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id ?? $employee->nip,
                'name' => $employee->name,
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'No Department',
                'department_id' => $employee->department_id,
                'status' => $employee->status,
            ],
            'latest_certificate' => $latestCert ? [
                'id' => $latestCert->id,
                'version_number' => $latestCert->version_number,
                'status' => $latestCert->validity_status,
                'issue_date' => $latestCert->issue_date,
                'expiry_date' => $latestCert->expiry_date,
                'drive_file_id' => $latestCert->drive_file_id,
                'filename' => $latestCert->original_filename,
                'file_size' => $latestCert->formatted_file_size,
            ] : null,
            'certificates_history' => [
                'total_count' => $certificates->count(),
                'active_count' => $certificates->where('validity_status', 'valid')->count(),
                'expired_count' => $certificates->where('validity_status', 'expired')->count(),
                'expiring_soon_count' => $certificates->where('validity_status', 'expiring_soon')->count(),
                'versions' => $certificates->sortByDesc('version_number')->take(5)->map(function($cert) {
                    return [
                        'version' => $cert->version_number,
                        'status' => $cert->validity_status,
                        'issue_date' => $cert->issue_date,
                        'expiry_date' => $cert->expiry_date,
                        'file_id' => $cert->id
                    ];
                })->values()->toArray()
            ]
        ];
    });

    return [
        'statistics' => $this->getCertificateStats(),
        'employees' => $employeesData->toArray()
    ];
}

/**
 * Get renewal timeline for this certificate type
 */
public function getRenewalTimeline($months = 12): array
{
    $startDate = now();
    $endDate = now()->addMonths($months);

    $expiringFiles = $this->latestCertificateFiles()
        ->whereBetween('expiry_date', [$startDate, $endDate])
        ->with('employee')
        ->orderBy('expiry_date', 'asc')
        ->get();

    return $expiringFiles->map(function($file) {
        return [
            'employee_name' => $file->employee->name,
            'employee_id' => $file->employee->employee_id ?? $file->employee->nip,
            'current_version' => $file->version_number,
            'expiry_date' => $file->expiry_date,
            'days_until_expiry' => $file->days_until_expiry,
            'status' => $file->validity_status
        ];
    })->toArray();
}

// Add to existing scopes

/**
 * Scope to get certificate types with files
 */
public function scopeWithFiles($query)
{
    return $query->has('certificateFiles');
}

/**
 * Scope to get certificate types with valid files
 */
public function scopeWithValidFiles($query)
{
    return $query->has('validCertificateFiles');
}

/**
 * Scope to get certificate types needing renewal
 */
public function scopeNeedingRenewal($query, $days = 30)
{
    return $query->has('expiringSoonCertificateFiles');
}
}
