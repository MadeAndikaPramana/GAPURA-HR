<?php
// app/Services/TrainingTypeAnalyticsService.php

namespace App\Services;

use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrainingTypeAnalyticsService
{
    /**
     * Get comprehensive analytics for all training types
     */
    public function getTrainingTypeAnalytics(): array
    {
        $trainingTypes = TrainingType::withCount([
            'trainingRecords',
            'trainingRecords as active_certificates' => function ($query) {
                $query->where('status', 'active');
            },
            'trainingRecords as expiring_certificates' => function ($query) {
                $query->where('status', 'expiring_soon');
            },
            'trainingRecords as expired_certificates' => function ($query) {
                $query->where('status', 'expired');
            }
        ])->get();

        $analytics = [];
        $totalEmployees = Employee::count();

        foreach ($trainingTypes as $trainingType) {
            $complianceRate = $totalEmployees > 0
                ? round(($trainingType->active_certificates / $totalEmployees) * 100, 1)
                : 0;

            $analytics[] = [
                'id' => $trainingType->id,
                'name' => $trainingType->name,
                'category' => $trainingType->category,
                'is_mandatory' => $trainingType->is_mandatory,
                'validity_period_months' => $trainingType->validity_period_months,

                // Certificate counts
                'total_certificates' => $trainingType->training_records_count,
                'active_certificates' => $trainingType->active_certificates,
                'expiring_certificates' => $trainingType->expiring_certificates,
                'expired_certificates' => $trainingType->expired_certificates,

                // Compliance metrics
                'compliance_rate' => $complianceRate,
                'employees_trained' => $trainingType->active_certificates,
                'employees_need_training' => max(0, $totalEmployees - $trainingType->active_certificates),

                // Risk assessment
                'risk_level' => $this->calculateRiskLevel($complianceRate, $trainingType->is_mandatory),
                'priority_score' => $this->calculatePriorityScore($trainingType)
            ];
        }

        // Sort by priority score (highest first)
        usort($analytics, function ($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });

        return $analytics;
    }

    /**
     * Get training type statistics by department
     */
    public function getTrainingTypeByDepartment(int $trainingTypeId): array
    {
        $departments = Department::withCount([
            'employees',
            'employees as trained_employees' => function ($query) use ($trainingTypeId) {
                $query->whereHas('trainingRecords', function ($q) use ($trainingTypeId) {
                    $q->where('training_type_id', $trainingTypeId)
                      ->where('status', 'active');
                });
            }
        ])->get();

        return $departments->map(function ($dept) {
            $complianceRate = $dept->employees_count > 0
                ? round(($dept->trained_employees / $dept->employees_count) * 100, 1)
                : 0;

            return [
                'department_name' => $dept->name,
                'total_employees' => $dept->employees_count,
                'trained_employees' => $dept->trained_employees,
                'untrained_employees' => $dept->employees_count - $dept->trained_employees,
                'compliance_rate' => $complianceRate,
                'compliance_status' => $this->getComplianceStatus($complianceRate)
            ];
        })->toArray();
    }

    /**
     * Get monthly training trends
     */
    public function getMonthlyTrainingTrends(int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $trends = TrainingRecord::select(
            DB::raw('DATE_FORMAT(completion_date, "%Y-%m") as month'),
            DB::raw('training_type_id'),
            DB::raw('COUNT(*) as certificates_issued')
        )
        ->where('completion_date', '>=', $startDate)
        ->whereNotNull('completion_date')
        ->groupBy('month', 'training_type_id')
        ->orderBy('month')
        ->with('trainingType:id,name')
        ->get();

        // Organize data by month
        $monthlyData = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $monthlyData[$month] = [
                'month' => $month,
                'month_name' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                'training_types' => [],
                'total_certificates' => 0
            ];
        }

        // Fill in the data
        foreach ($trends as $trend) {
            if (isset($monthlyData[$trend->month])) {
                $monthlyData[$trend->month]['training_types'][] = [
                    'training_type' => $trend->trainingType->name,
                    'certificates_issued' => $trend->certificates_issued
                ];
                $monthlyData[$trend->month]['total_certificates'] += $trend->certificates_issued;
            }
        }

        return array_values($monthlyData);
    }

    /**
     * Get training compliance overview
     */
    public function getComplianceOverview(): array
    {
        $totalEmployees = Employee::count();
        $totalTrainingTypes = TrainingType::where('is_mandatory', true)->count();

        // Get mandatory training compliance
        $mandatoryCompliance = TrainingType::where('is_mandatory', true)
            ->withCount([
                'trainingRecords as compliant_employees' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
            ->get();

        $overallCompliance = 0;
        if ($totalTrainingTypes > 0) {
            $totalCompliant = $mandatoryCompliance->sum('compliant_employees');
            $maxPossible = $totalEmployees * $totalTrainingTypes;
            $overallCompliance = $maxPossible > 0 ? round(($totalCompliant / $maxPossible) * 100, 1) : 0;
        }

        // Get expiry alerts
        $expiringAlerts = TrainingRecord::where('status', 'expiring_soon')
            ->whereHas('trainingType', function ($query) {
                $query->where('is_mandatory', true);
            })
            ->count();

        $expiredAlerts = TrainingRecord::where('status', 'expired')
            ->whereHas('trainingType', function ($query) {
                $query->where('is_mandatory', true);
            })
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'mandatory_training_types' => $totalTrainingTypes,
            'overall_compliance_rate' => $overallCompliance,
            'expiring_certificates' => $expiringAlerts,
            'expired_certificates' => $expiredAlerts,
            'total_risk_alerts' => $expiringAlerts + $expiredAlerts,
            'compliance_grade' => $this->getComplianceGrade($overallCompliance)
        ];
    }

    /**
     * Get training cost analytics
     */
    public function getTrainingCostAnalytics(): array
    {
        $costByType = TrainingRecord::select(
            'training_type_id',
            DB::raw('COUNT(*) as total_certificates'),
            DB::raw('SUM(COALESCE(cost, 0)) as total_cost'),
            DB::raw('AVG(COALESCE(cost, 0)) as average_cost'),
            DB::raw('MAX(COALESCE(cost, 0)) as max_cost'),
            DB::raw('MIN(CASE WHEN cost > 0 THEN cost END) as min_cost')
        )
        ->whereYear('completion_date', Carbon::now()->year)
        ->groupBy('training_type_id')
        ->with('trainingType:id,name,category')
        ->get();

        $totalCostThisYear = $costByType->sum('total_cost');
        $totalCertificatesThisYear = $costByType->sum('total_certificates');
        $averageCostPerCertificate = $totalCertificatesThisYear > 0
            ? round($totalCostThisYear / $totalCertificatesThisYear, 2)
            : 0;

        return [
            'summary' => [
                'total_cost_this_year' => $totalCostThisYear,
                'total_certificates_this_year' => $totalCertificatesThisYear,
                'average_cost_per_certificate' => $averageCostPerCertificate
            ],
            'by_training_type' => $costByType->map(function ($item) {
                return [
                    'training_type' => $item->trainingType->name,
                    'category' => $item->trainingType->category,
                    'total_certificates' => $item->total_certificates,
                    'total_cost' => $item->total_cost,
                    'average_cost' => round($item->average_cost, 2),
                    'cost_per_certificate' => $item->total_certificates > 0
                        ? round($item->total_cost / $item->total_certificates, 2)
                        : 0
                ];
            })->sortByDesc('total_cost')->values()->toArray()
        ];
    }

    /**
     * Calculate risk level for a training type
     */
    private function calculateRiskLevel(float $complianceRate, bool $isMandatory): string
    {
        if (!$isMandatory) {
            return 'low';
        }

        if ($complianceRate >= 90) {
            return 'low';
        } elseif ($complianceRate >= 75) {
            return 'medium';
        } elseif ($complianceRate >= 50) {
            return 'high';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculate priority score for training type
     */
    private function calculatePriorityScore(TrainingType $trainingType): int
    {
        $score = 0;

        // Mandatory training gets higher priority
        if ($trainingType->is_mandatory) {
            $score += 50;
        }

        // More expired certificates = higher priority
        $score += $trainingType->expired_certificates * 5;

        // More expiring certificates = moderate priority increase
        $score += $trainingType->expiring_certificates * 3;

        // Safety-related training gets priority boost
        if (stripos($trainingType->name, 'safety') !== false ||
            stripos($trainingType->category, 'safety') !== false) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Get compliance status text
     */
    private function getComplianceStatus(float $rate): string
    {
        if ($rate >= 90) return 'excellent';
        if ($rate >= 75) return 'good';
        if ($rate >= 60) return 'fair';
        if ($rate >= 40) return 'poor';
        return 'critical';
    }

    /**
     * Get compliance grade
     */
    private function getComplianceGrade(float $rate): string
    {
        if ($rate >= 95) return 'A+';
        if ($rate >= 90) return 'A';
        if ($rate >= 85) return 'A-';
        if ($rate >= 80) return 'B+';
        if ($rate >= 75) return 'B';
        if ($rate >= 70) return 'B-';
        if ($rate >= 65) return 'C+';
        if ($rate >= 60) return 'C';
        if ($rate >= 55) return 'C-';
        if ($rate >= 50) return 'D';
        return 'F';
    }
}
