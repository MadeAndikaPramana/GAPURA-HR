<?php
// app/Models/TrainingType.php - Enhanced for Phase 3

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrainingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'is_active',
        'is_mandatory',
        'validity_period_months',
        'warning_period_days',
        'default_provider_id',
        'estimated_cost',
        'estimated_duration_hours',
        'requirements',
        'learning_objectives',
        'requires_certification',
        'auto_renewal_available',
        'max_participants_per_batch',
        'priority_score',
        'compliance_target_percentage',
        'applicable_departments',
        'applicable_job_levels',
        'certificate_template',
        'custom_fields',
        'last_analytics_update',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
        'requires_certification' => 'boolean',
        'auto_renewal_available' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
        'compliance_target_percentage' => 'decimal:2',
        'applicable_departments' => 'json',
        'applicable_job_levels' => 'json',
        'custom_fields' => 'json',
        'last_analytics_update' => 'datetime'
    ];

    // =================================================================
    // RELATIONSHIPS
    // =================================================================

    /**
     * Training records for this type
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Active training records
     */
    public function activeTrainingRecords(): HasMany
    {
        return $this->trainingRecords()->where('status', 'active');
    }

    /**
     * Expiring training records
     */
    public function expiringTrainingRecords(): HasMany
    {
        return $this->trainingRecords()->where('status', 'expiring_soon');
    }

    /**
     * Expired training records
     */
    public function expiredTrainingRecords(): HasMany
    {
        return $this->trainingRecords()->where('status', 'expired');
    }

    /**
     * Default training provider
     */
    public function defaultProvider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class, 'default_provider_id');
    }

    /**
     * User who created this training type
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * User who last updated this training type
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Cached statistics for this training type
     */
    public function statistics(): HasOne
    {
        return $this->hasOne(TrainingTypeStatistic::class);
    }

    /**
     * Department requirements
     */
    public function departmentRequirements(): HasMany
    {
        return $this->hasMany(TrainingTypeDepartmentRequirement::class);
    }

    /**
     * Departments that require this training
     */
    public function requiredDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'training_type_department_requirements')
                    ->withPivot(['is_required', 'frequency_months', 'target_compliance_rate'])
                    ->withTimestamps();
    }

    // =================================================================
    // SCOPES
    // =================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority_score', '>', 50);
    }

    public function scopeWithStatistics($query)
    {
        return $query->with(['statistics', 'trainingRecords']);
    }

    // =================================================================
    // ANALYTICS METHODS
    // =================================================================

    /**
     * Calculate current compliance statistics
     */
    public function calculateComplianceStatistics(): array
    {
        $totalEmployees = Employee::count();
        $activeCount = $this->activeTrainingRecords()->count();
        $expiringCount = $this->expiringTrainingRecords()->count();
        $expiredCount = $this->expiredTrainingRecords()->count();
        $totalCertificates = $this->trainingRecords()->count();

        $complianceRate = $totalEmployees > 0
            ? round(($activeCount / $totalEmployees) * 100, 2)
            : 0;

        $riskLevel = $this->calculateRiskLevel($complianceRate);
        $priorityScore = $this->calculatePriorityScore($activeCount, $expiringCount, $expiredCount);

        return [
            'total_employees' => $totalEmployees,
            'total_certificates' => $totalCertificates,
            'active_certificates' => $activeCount,
            'expiring_certificates' => $expiringCount,
            'expired_certificates' => $expiredCount,
            'compliance_rate' => $complianceRate,
            'employees_trained' => $activeCount,
            'employees_need_training' => max(0, $totalEmployees - $activeCount),
            'risk_level' => $riskLevel,
            'priority_score' => $priorityScore,
            'compliance_status' => $this->getComplianceStatus($complianceRate)
        ];
    }

    /**
     * Calculate department-wise compliance
     */
    public function calculateDepartmentCompliance(): array
    {
        return Department::withCount([
            'employees',
            'employees as trained_employees' => function ($query) {
                $query->whereHas('trainingRecords', function ($q) {
                    $q->where('training_type_id', $this->id)
                      ->where('status', 'active');
                });
            }
        ])->get()->map(function ($dept) {
            $complianceRate = $dept->employees_count > 0
                ? round(($dept->trained_employees / $dept->employees_count) * 100, 1)
                : 0;

            return [
                'department_id' => $dept->id,
                'department_name' => $dept->name,
                'total_employees' => $dept->employees_count,
                'trained_employees' => $dept->trained_employees,
                'untrained_employees' => $dept->employees_count - $dept->trained_employees,
                'compliance_rate' => $complianceRate,
                'compliance_status' => $this->getComplianceStatus($complianceRate),
                'target_met' => $complianceRate >= $this->compliance_target_percentage
            ];
        })->toArray();
    }

    /**
     * Get upcoming expiry information
     */
    public function getUpcomingExpiries($days = 90): array
    {
        $cutoffDate = Carbon::now()->addDays($days);

        $upcomingExpiries = $this->trainingRecords()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $cutoffDate)
            ->where('status', '!=', 'expired')
            ->with(['employee.department'])
            ->orderBy('expiry_date', 'asc')
            ->get();

        return $upcomingExpiries->groupBy(function ($record) {
            $daysUntilExpiry = Carbon::parse($record->expiry_date)->diffInDays(Carbon::today(), false);

            if ($daysUntilExpiry < 0) {
                return 'expired';
            } elseif ($daysUntilExpiry <= 7) {
                return 'critical'; // 7 days or less
            } elseif ($daysUntilExpiry <= 30) {
                return 'urgent';   // 8-30 days
            } else {
                return 'warning';  // 31-90 days
            }
        })->map(function ($group) {
            return $group->map(function ($record) {
                return [
                    'employee_name' => $record->employee->name,
                    'employee_nik' => $record->employee->nik,
                    'department' => $record->employee->department->name,
                    'certificate_number' => $record->certificate_number,
                    'expiry_date' => $record->expiry_date,
                    'days_until_expiry' => Carbon::parse($record->expiry_date)->diffInDays(Carbon::today(), false)
                ];
            });
        })->toArray();
    }

    /**
     * Get training cost analytics
     */
    public function getCostAnalytics($year = null): array
    {
        $year = $year ?? Carbon::now()->year;

        $costs = $this->trainingRecords()
            ->whereYear('completion_date', $year)
            ->whereNotNull('cost')
            ->where('cost', '>', 0)
            ->select(
                DB::raw('COUNT(*) as total_certificates'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('AVG(cost) as average_cost'),
                DB::raw('MAX(cost) as max_cost'),
                DB::raw('MIN(cost) as min_cost')
            )
            ->first();

        return [
            'year' => $year,
            'total_certificates' => $costs->total_certificates ?? 0,
            'total_cost' => $costs->total_cost ?? 0,
            'average_cost' => $costs->average_cost ?? 0,
            'max_cost' => $costs->max_cost ?? 0,
            'min_cost' => $costs->min_cost ?? 0,
            'estimated_budget_needed' => $this->estimated_cost
                ? $this->estimateAnnualBudgetNeed()
                : 0
        ];
    }

    /**
     * Update cached statistics
     */
    public function updateStatistics(): void
    {
        $stats = $this->calculateComplianceStatistics();
        $costStats = $this->getCostAnalytics();

        $this->statistics()->updateOrCreate(
            ['training_type_id' => $this->id],
            [
                'total_certificates' => $stats['total_certificates'],
                'active_certificates' => $stats['active_certificates'],
                'expiring_certificates' => $stats['expiring_certificates'],
                'expired_certificates' => $stats['expired_certificates'],
                'compliance_rate' => $stats['compliance_rate'],
                'employees_trained' => $stats['employees_trained'],
                'employees_need_training' => $stats['employees_need_training'],
                'risk_level' => $stats['risk_level'],
                'calculated_priority_score' => $stats['priority_score'],
                'total_cost_ytd' => $costStats['total_cost'],
                'average_cost_per_certificate' => $costStats['average_cost'],
                'certificates_issued_this_year' => $costStats['total_certificates'],
                'calculated_at' => Carbon::now()
            ]
        );

        $this->update(['last_analytics_update' => Carbon::now()]);
    }

    // =================================================================
    // HELPER METHODS
    // =================================================================

    /**
     * Calculate risk level based on compliance rate and mandatory status
     */
    private function calculateRiskLevel(float $complianceRate): string
    {
        if (!$this->is_mandatory) {
            return 'low';
        }

        if ($complianceRate >= 95) {
            return 'low';
        } elseif ($complianceRate >= 80) {
            return 'medium';
        } elseif ($complianceRate >= 60) {
            return 'high';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculate priority score for scheduling
     */
    private function calculatePriorityScore(int $active, int $expiring, int $expired): int
    {
        $score = $this->priority_score; // Base score

        // Mandatory training gets higher priority
        if ($this->is_mandatory) {
            $score += 30;
        }

        // More expired certificates = higher priority
        $score += $expired * 5;

        // More expiring certificates = moderate priority increase
        $score += $expiring * 3;

        // Safety/Security categories get priority boost
        if (stripos($this->category, 'safety') !== false ||
            stripos($this->category, 'security') !== false) {
            $score += 20;
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Get compliance status text
     */
    private function getComplianceStatus(float $rate): string
    {
        if ($rate >= 95) return 'excellent';
        if ($rate >= 85) return 'good';
        if ($rate >= 70) return 'fair';
        if ($rate >= 50) return 'poor';
        return 'critical';
    }

    /**
     * Estimate annual budget need for this training
     */
    private function estimateAnnualBudgetNeed(): float
    {
        if (!$this->estimated_cost || !$this->validity_period_months) {
            return 0;
        }

        $totalEmployees = Employee::count();

        // Calculate how many renewals needed per year
        $renewalsPerYear = $totalEmployees / ($this->validity_period_months / 12);

        return round($renewalsPerYear * $this->estimated_cost, 2);
    }

    /**
     * Check if training is applicable to a specific department
     */
    public function isApplicableToDepartment(int $departmentId): bool
    {
        if (!$this->applicable_departments) {
            return true; // If not specified, applies to all
        }

        return in_array($departmentId, $this->applicable_departments);
    }

    /**
     * Get human-readable validity period
     */
    public function getValidityPeriodAttribute(): string
    {
        if (!$this->validity_period_months) {
            return 'No expiry';
        }

        if ($this->validity_period_months === 12) {
            return '1 year';
        } elseif ($this->validity_period_months % 12 === 0) {
            return ($this->validity_period_months / 12) . ' years';
        } else {
            return $this->validity_period_months . ' months';
        }
    }

    /**
     * Get formatted estimated cost
     */
    public function getFormattedCostAttribute(): string
    {
        return $this->estimated_cost
            ? 'Rp ' . number_format($this->estimated_cost, 0, ',', '.')
            : 'Not specified';
    }
}
