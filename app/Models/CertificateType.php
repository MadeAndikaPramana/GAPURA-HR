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

    // ===== TRAINING TYPE CONTAINER METHODS (Reverse Lookup) =====

    /**
     * Get complete container data for this training type
     * Shows "certificate jenis ini dimiliki siapa saja"
     */
    public function getContainerData()
    {
        return [
            'type_info' => $this->getTypeInfo(),
            'statistics' => $this->getContainerStatistics(),
            'employees' => $this->getEmployeesWithCertificate(),
            'categories' => $this->getCertificateStatusDistribution()
        ];
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
}
