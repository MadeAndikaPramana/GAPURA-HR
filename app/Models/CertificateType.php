<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'validity_months',
        'warning_days',
        'is_mandatory',
        'is_recurrent',
        'description',
        'requirements',
        'learning_objectives',
        'is_active',
        'estimated_cost',
        'estimated_duration_hours'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_recurrent' => 'boolean',
        'is_active' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2'
    ];

    // ===== RELATIONSHIPS =====

    public function employeeCertificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    public function activeCertificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'active');
    }

    // ===== TRAINING TYPE CONTAINER METHODS =====

    /**
     * Get employees who have this certificate type
     */
    public function getEmployeesWithCertificate()
    {
        return Employee::whereHas('employeeCertificates', function($query) {
            $query->where('certificate_type_id', $this->id);
        })->with(['department', 'employeeCertificates' => function($query) {
            $query->where('certificate_type_id', $this->id)
                  ->orderBy('issue_date', 'desc');
        }])->get();
    }

    /**
     * Get certificate statistics for this type
     */
    public function getCertificateStats()
    {
        $certificates = $this->employeeCertificates;

        return [
            'total_certificates' => $certificates->count(),
            'active_certificates' => $certificates->where('status', 'active')->count(),
            'expired_certificates' => $certificates->where('status', 'expired')->count(),
            'expiring_soon' => $certificates->where('status', 'expiring_soon')->count(),
            'unique_employees' => $certificates->pluck('employee_id')->unique()->count(),
            'recent_certificates' => $certificates->sortByDesc('created_at')->take(5)
        ];
    }

    /**
     * Get training type container data (reverse lookup)
     */
    public function getContainerData()
    {
        $employees = $this->getEmployeesWithCertificate();
        $stats = $this->getCertificateStats();

        // Group by status
        $employeesByStatus = [];
        foreach ($employees as $employee) {
            $latestCert = $employee->employeeCertificates->first();
            $status = $latestCert ? $latestCert->status : 'none';

            if (!isset($employeesByStatus[$status])) {
                $employeesByStatus[$status] = [];
            }

            $employeesByStatus[$status][] = [
                'employee' => $employee,
                'latest_certificate' => $latestCert,
                'certificate_count' => $employee->employeeCertificates->count()
            ];
        }

        return [
            'certificate_type' => $this,
            'statistics' => $stats,
            'employees_by_status' => $employeesByStatus,
            'total_employees' => $employees->count()
        ];
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }
}
