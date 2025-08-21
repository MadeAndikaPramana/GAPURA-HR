<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingRecord;
use App\Models\Certificate;
use App\Models\TrainingType;
use App\Models\TrainingCategory;
use App\Models\TrainingProvider;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard
     */
    public function index(Request $request)
    {
        $timeRange = $request->get('range', '12'); // months
        $department = $request->get('department');
        $category = $request->get('category');

        $dashboardData = $this->getDashboardData($timeRange, $department, $category);

        return Inertia::render('Analytics/Dashboard', [
            'dashboardData' => $dashboardData,
            'departments' => Department::all(['id', 'name']),
            'categories' => TrainingCategory::all(['id', 'name']),
            'filters' => compact('timeRange', 'department', 'category')
        ]);
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData($timeRange = 12, $department = null, $category = null)
    {
        $startDate = now()->subMonths($timeRange);

        return [
            'overview' => $this->getOverviewMetrics($startDate, $department, $category),
            'trends' => $this->getTrendAnalysis($timeRange, $department, $category),
            'compliance' => $this->getComplianceAnalytics($department),
            'training_effectiveness' => $this->getTrainingEffectiveness($startDate, $department, $category),
            'cost_analysis' => $this->getCostAnalysis($startDate, $department, $category),
            'provider_performance' => $this->getProviderPerformance($startDate),
            'department_comparison' => $this->getDepartmentComparison($startDate),
            'predictive_insights' => $this->getPredictiveInsights($timeRange)
        ];
    }

    /**
     * Get overview metrics
     */
    protected function getOverviewMetrics($startDate, $department = null, $category = null)
    {
        $query = TrainingRecord::where('created_at', '>=', $startDate);

        if ($department) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        if ($category) {
            $query->whereHas('trainingType', function ($q) use ($category) {
                $q->where('category_id', $category);
            });
        }

        $totalTrainings = $query->count();
        $completedTrainings = $query->where('status', 'completed')->count();
        $totalHours = $query->where('status', 'completed')->sum('training_hours');
        $totalCost = $query->where('status', 'completed')->sum('cost');
        $avgScore = $query->where('status', 'completed')->avg('score');

        // Certificates overview
        $certificateQuery = Certificate::whereHas('trainingRecord', function ($q) use ($startDate, $department, $category) {
            $q->where('created_at', '>=', $startDate);
            if ($department) {
                $q->whereHas('employee', function ($empQ) use ($department) {
                    $empQ->where('department_id', $department);
                });
            }
            if ($category) {
                $q->whereHas('trainingType', function ($typeQ) use ($category) {
                    $typeQ->where('category_id', $category);
                });
            }
        });

        $activeCertificates = $certificateQuery->active()->count();
        $expiredCertificates = $certificateQuery->expired()->count();
        $expiringSoon = $certificateQuery->expiringSoon(30)->count();

        return [
            'total_trainings' => $totalTrainings,
            'completed_trainings' => $completedTrainings,
            'completion_rate' => $totalTrainings > 0 ? round(($completedTrainings / $totalTrainings) * 100, 1) : 0,
            'total_hours' => round($totalHours, 1),
            'total_cost' => $totalCost,
            'average_score' => round($avgScore ?: 0, 1),
            'active_certificates' => $activeCertificates,
            'expired_certificates' => $expiredCertificates,
            'expiring_soon' => $expiringSoon,
            'certificate_compliance_rate' => ($activeCertificates + $expiredCertificates + $expiringSoon) > 0
                ? round(($activeCertificates / ($activeCertificates + $expiredCertificates + $expiringSoon)) * 100, 1)
                : 0
        ];
    }

    /**
     * Get trend analysis data
     */
    protected function getTrendAnalysis($months, $department = null, $category = null)
    {
        $trends = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $query = TrainingRecord::whereBetween('completion_date', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed');

            if ($department) {
                $query->whereHas('employee', function ($q) use ($department) {
                    $q->where('department_id', $department);
                });
            }

            if ($category) {
                $query->whereHas('trainingType', function ($q) use ($category) {
                    $q->where('category_id', $category);
                });
            }

            $monthData = $query->selectRaw('
                COUNT(*) as completed_count,
                SUM(training_hours) as total_hours,
                SUM(cost) as total_cost,
                AVG(score) as average_score
            ')->first();

            $trends[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('M Y'),
                'completed_trainings' => $monthData->completed_count ?: 0,
                'total_hours' => $monthData->total_hours ?: 0,
                'total_cost' => $monthData->total_cost ?: 0,
                'average_score' => round($monthData->average_score ?: 0, 1),
                'cost_per_training' => $monthData->completed_count > 0
                    ? round($monthData->total_cost / $monthData->completed_count, 0)
                    : 0
            ];
        }

        return $trends;
    }

    /**
     * Get compliance analytics
     */
    protected function getComplianceAnalytics($department = null)
    {
        $employeeQuery = Employee::where('status', 'active');

        if ($department) {
            $employeeQuery->where('department_id', $department);
        }

        $totalEmployees = $employeeQuery->count();

        // Compliance by status
        $complianceByStatus = [
            'compliant' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'missing_training' => 0
        ];

        $employees = $employeeQuery->with(['trainingRecords.trainingType'])->get();

        foreach ($employees as $employee) {
            $complianceStatus = $employee->compliance_status;

            switch ($complianceStatus) {
                case 'compliant':
                    $complianceByStatus['compliant']++;
                    break;
                case 'expiring_soon':
                    $complianceByStatus['expiring_soon']++;
                    break;
                case 'non_compliant':
                    $complianceByStatus['expired']++;
                    break;
                default:
                    $complianceByStatus['missing_training']++;
            }
        }

        // Compliance by training type
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->get();
        $complianceByTraining = [];

        foreach ($mandatoryTrainings as $training) {
            $employeesWithTraining = TrainingRecord::where('training_type_id', $training->id)
                ->where('compliance_status', 'compliant');

            if ($department) {
                $employeesWithTraining->whereHas('employee', function ($q) use ($department) {
                    $q->where('department_id', $department);
                });
            }

            $compliantCount = $employeesWithTraining->distinct('employee_id')->count();

            $complianceByTraining[] = [
                'training_name' => $training->name,
                'compliant_employees' => $compliantCount,
                'compliance_rate' => $totalEmployees > 0 ? round(($compliantCount / $totalEmployees) * 100, 1) : 0
            ];
        }

        return [
            'total_employees' => $totalEmployees,
            'compliance_by_status' => $complianceByStatus,
            'compliance_by_training' => $complianceByTraining,
            'overall_compliance_rate' => $totalEmployees > 0
                ? round(($complianceByStatus['compliant'] / $totalEmployees) * 100, 1)
                : 0
        ];
    }

    /**
     * Get training effectiveness metrics
     */
    protected function getTrainingEffectiveness($startDate, $department = null, $category = null)
    {
        $query = TrainingRecord::where('completion_date', '>=', $startDate)
            ->where('status', 'completed');

        if ($department) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        if ($category) {
            $query->whereHas('trainingType', function ($q) use ($category) {
                $q->where('category_id', $category);
            });
        }

        // Score distribution
        $scoreRanges = [
            '90-100' => $query->clone()->whereBetween('score', [90, 100])->count(),
            '80-89' => $query->clone()->whereBetween('score', [80, 89])->count(),
            '70-79' => $query->clone()->whereBetween('score', [70, 79])->count(),
            'Below 70' => $query->clone()->where('score', '<', 70)->count()
        ];

        // Performance by training type
        $performanceByType = TrainingType::withCount([
                'trainingRecords as completed_count' => function ($q) use ($startDate, $department) {
                    $q->where('status', 'completed')
                      ->where('completion_date', '>=', $startDate);
                    if ($department) {
                        $q->whereHas('employee', function ($empQ) use ($department) {
                            $empQ->where('department_id', $department);
                        });
                    }
                }
            ])
            ->with(['trainingRecords' => function ($q) use ($startDate, $department) {
                $q->where('status', 'completed')
                  ->where('completion_date', '>=', $startDate);
                if ($department) {
                    $q->whereHas('employee', function ($empQ) use ($department) {
                        $empQ->where('department_id', $department);
                    });
                }
            }])
            ->get()
            ->map(function ($type) {
                $avgScore = $type->trainingRecords->avg('score');
                return [
                    'name' => $type->name,
                    'completed_count' => $type->completed_count,
                    'average_score' => round($avgScore ?: 0, 1),
                    'effectiveness_rating' => $this->getEffectivenessRating($avgScore)
                ];
            })
            ->filter(function ($type) {
                return $type['completed_count'] > 0;
            })
            ->sortByDesc('average_score')
            ->values();

        return [
            'score_distribution' => $scoreRanges,
            'performance_by_type' => $performanceByType,
            'average_improvement' => $this->calculateAverageImprovement($startDate, $department)
        ];
    }

    /**
     * Get cost analysis
     */
    protected function getCostAnalysis($startDate, $department = null, $category = null)
    {
        $query = TrainingRecord::where('completion_date', '>=', $startDate)
            ->where('status', 'completed');

        if ($department) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        if ($category) {
            $query->whereHas('trainingType', function ($q) use ($category) {
                $q->where('category_id', $category);
            });
        }

        $totalCost = $query->sum('cost');
        $totalHours = $query->sum('training_hours');
        $avgCostPerTraining = $query->avg('cost');
        $avgCostPerHour = $totalHours > 0 ? $totalCost / $totalHours : 0;

        // Cost by category
        $costByCategory = TrainingCategory::withSum([
                'trainingRecords as total_cost' => function ($q) use ($startDate, $department) {
                    $q->where('status', 'completed')
                      ->where('completion_date', '>=', $startDate);
                    if ($department) {
                        $q->whereHas('employee', function ($empQ) use ($department) {
                            $empQ->where('department_id', $department);
                        });
                    }
                }
            ])
            ->get()
            ->filter(function ($category) {
                return $category->total_cost > 0;
            })
            ->map(function ($category) use ($totalCost) {
                return [
                    'name' => $category->name,
                    'total_cost' => $category->total_cost,
                    'percentage' => $totalCost > 0 ? round(($category->total_cost / $totalCost) * 100, 1) : 0
                ];
            });

        // Cost by provider
        $costByProvider = TrainingProvider::withSum([
                'trainingRecords as total_cost' => function ($q) use ($startDate, $department) {
                    $q->where('status', 'completed')
                      ->where('completion_date', '>=', $startDate);
                    if ($department) {
                        $q->whereHas('employee', function ($empQ) use ($department) {
                            $empQ->where('department_id', $department);
                        });
                    }
                }
            ])
            ->get()
            ->filter(function ($provider) {
                return $provider->total_cost > 0;
            })
            ->sortByDesc('total_cost')
            ->take(10)
            ->map(function ($provider) {
                return [
                    'name' => $provider->name,
                    'total_cost' => $provider->total_cost,
                    'rating' => $provider->rating
                ];
            });

        return [
            'total_cost' => $totalCost,
            'total_hours' => $totalHours,
            'average_cost_per_training' => round($avgCostPerTraining ?: 0, 0),
            'average_cost_per_hour' => round($avgCostPerHour, 0),
            'cost_by_category' => $costByCategory,
            'cost_by_provider' => $costByProvider,
            'roi_analysis' => $this->calculateROI($startDate, $department)
        ];
    }

    /**
     * Get provider performance analytics
     */
    protected function getProviderPerformance($startDate)
    {
        return TrainingProvider::withCount([
                'trainingRecords as completed_count' => function ($q) use ($startDate) {
                    $q->where('status', 'completed')
                      ->where('completion_date', '>=', $startDate);
                }
            ])
            ->with(['trainingRecords' => function ($q) use ($startDate) {
                $q->where('status', 'completed')
                  ->where('completion_date', '>=', $startDate);
            }])
            ->get()
            ->filter(function ($provider) {
                return $provider->completed_count > 0;
            })
            ->map(function ($provider) {
                $records = $provider->trainingRecords;
                $avgScore = $records->avg('score');
                $totalCost = $records->sum('cost');
                $avgCost = $records->avg('cost');

                return [
                    'name' => $provider->name,
                    'rating' => $provider->rating,
                    'completed_trainings' => $provider->completed_count,
                    'average_score' => round($avgScore ?: 0, 1),
                    'total_cost' => $totalCost,
                    'average_cost' => round($avgCost ?: 0, 0),
                    'value_score' => $this->calculateValueScore($avgScore, $avgCost),
                    'recommendation' => $this->getProviderRecommendation($provider->rating, $avgScore, $avgCost)
                ];
            })
            ->sortByDesc('value_score')
            ->values();
    }

    /**
     * Get department comparison
     */
    protected function getDepartmentComparison($startDate)
    {
        return Department::with(['employees' => function ($q) {
                $q->where('status', 'active');
            }])
            ->get()
            ->map(function ($department) use ($startDate) {
                $employeeIds = $department->employees->pluck('id');

                $trainingData = TrainingRecord::whereIn('employee_id', $employeeIds)
                    ->where('completion_date', '>=', $startDate)
                    ->where('status', 'completed')
                    ->selectRaw('
                        COUNT(*) as completed_trainings,
                        SUM(training_hours) as total_hours,
                        SUM(cost) as total_cost,
                        AVG(score) as average_score
                    ')
                    ->first();

                $certificateData = Certificate::whereHas('trainingRecord', function ($q) use ($employeeIds) {
                        $q->whereIn('employee_id', $employeeIds);
                    })
                    ->selectRaw('
                        COUNT(*) as total_certificates,
                        COUNT(CASE WHEN expiry_date IS NULL OR expiry_date >= NOW() THEN 1 END) as active_certificates
                    ')
                    ->first();

                $totalEmployees = $department->employees->count();
                $complianceRate = $totalEmployees > 0 ?
                    round(($certificateData->active_certificates / $totalEmployees) * 100, 1) : 0;

                return [
                    'name' => $department->name,
                    'total_employees' => $totalEmployees,
                    'completed_trainings' => $trainingData->completed_trainings ?: 0,
                    'total_hours' => $trainingData->total_hours ?: 0,
                    'total_cost' => $trainingData->total_cost ?: 0,
                    'average_score' => round($trainingData->average_score ?: 0, 1),
                    'compliance_rate' => $complianceRate,
                    'training_per_employee' => $totalEmployees > 0
                        ? round(($trainingData->completed_trainings ?: 0) / $totalEmployees, 1)
                        : 0,
                    'cost_per_employee' => $totalEmployees > 0
                        ? round(($trainingData->total_cost ?: 0) / $totalEmployees, 0)
                        : 0
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();
    }

    /**
     * Get predictive insights
     */
    protected function getPredictiveInsights($months)
    {
        // Expiry forecast
        $expiryForecast = [];
        for ($i = 1; $i <= 12; $i++) {
            $targetDate = now()->addMonths($i);
            $expiringCount = Certificate::whereYear('expiry_date', $targetDate->year)
                ->whereMonth('expiry_date', $targetDate->month)
                ->count();

            $expiryForecast[] = [
                'month' => $targetDate->format('Y-m'),
                'month_name' => $targetDate->format('M Y'),
                'expiring_certificates' => $expiringCount
            ];
        }

        // Training demand prediction
        $historicalData = TrainingRecord::selectRaw('
                training_type_id,
                DATE_FORMAT(completion_date, "%m") as month,
                COUNT(*) as count
            ')
            ->where('completion_date', '>=', now()->subYears(2))
            ->where('status', 'completed')
            ->groupBy('training_type_id', 'month')
            ->get();

        $demandPrediction = TrainingType::get()->map(function ($type) use ($historicalData) {
            $typeData = $historicalData->where('training_type_id', $type->id);
            $avgDemand = $typeData->avg('count') ?: 0;

            return [
                'training_name' => $type->name,
                'predicted_monthly_demand' => round($avgDemand, 0),
                'next_quarter_demand' => round($avgDemand * 3, 0)
            ];
        })->sortByDesc('predicted_monthly_demand')->take(10)->values();

        return [
            'expiry_forecast' => $expiryForecast,
            'demand_prediction' => $demandPrediction,
            'budget_recommendation' => $this->getBudgetRecommendation(),
            'risk_indicators' => $this->getRiskIndicators()
        ];
    }

    /**
     * Helper methods for calculations
     */

    protected function getEffectivenessRating($score)
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Good';
        if ($score >= 70) return 'Satisfactory';
        return 'Needs Improvement';
    }

    protected function calculateAverageImprovement($startDate, $department)
    {
        // This would calculate improvement based on before/after scores
        // Simplified version returns a mock improvement percentage
        return rand(5, 25) + (rand(0, 99) / 100);
    }

    protected function calculateROI($startDate, $department)
    {
        // Simplified ROI calculation
        $totalCost = TrainingRecord::where('completion_date', '>=', $startDate)
            ->where('status', 'completed')
            ->sum('cost');

        $avgScore = TrainingRecord::where('completion_date', '>=', $startDate)
            ->where('status', 'completed')
            ->avg('score');

        $roi = $totalCost > 0 ? (($avgScore - 70) / 30) * 100 : 0;

        return [
            'total_investment' => $totalCost,
            'roi_percentage' => round($roi, 1),
            'payback_period_months' => $roi > 0 ? round(12 / ($roi / 100), 1) : null
        ];
    }

    protected function calculateValueScore($avgScore, $avgCost)
    {
        // Simple value score: higher score and lower cost = better value
        $scoreWeight = ($avgScore ?: 0) / 100;
        $costWeight = $avgCost > 0 ? 1 / ($avgCost / 1000000) : 0; // Normalize to millions

        return round(($scoreWeight * 0.7 + $costWeight * 0.3) * 100, 1);
    }

    protected function getProviderRecommendation($rating, $avgScore, $avgCost)
    {
        if ($rating >= 4.5 && $avgScore >= 85) return 'Highly Recommended';
        if ($rating >= 4.0 && $avgScore >= 80) return 'Recommended';
        if ($rating >= 3.5 && $avgScore >= 75) return 'Acceptable';
        return 'Needs Review';
    }

    protected function getBudgetRecommendation()
    {
        $currentYearSpend = TrainingRecord::where('status', 'completed')
            ->whereYear('completion_date', date('Y'))
            ->sum('cost');

        $lastYearSpend = TrainingRecord::where('status', 'completed')
            ->whereYear('completion_date', date('Y') - 1)
            ->sum('cost');

        $growthRate = $lastYearSpend > 0 ? (($currentYearSpend - $lastYearSpend) / $lastYearSpend) * 100 : 0;

        return [
            'current_year_spend' => $currentYearSpend,
            'last_year_spend' => $lastYearSpend,
            'growth_rate' => round($growthRate, 1),
            'recommended_next_year_budget' => round($currentYearSpend * 1.1, 0) // 10% increase
        ];
    }

    protected function getRiskIndicators()
    {
        return [
            'high_cost_low_score_trainings' => TrainingRecord::where('status', 'completed')
                ->where('cost', '>', 1000000) // > 1M IDR
                ->where('score', '<', 75)
                ->count(),
            'providers_below_threshold' => TrainingProvider::where('rating', '<', 3.5)->count(),
            'overdue_renewals' => Certificate::expired()->count(),
            'budget_variance_risk' => abs($this->calculateBudgetVariance()) > 20 ? 'High' : 'Low'
        ];
    }

    protected function calculateBudgetVariance()
    {
        // Simplified budget variance calculation
        $actualSpend = TrainingRecord::where('status', 'completed')
            ->whereYear('completion_date', date('Y'))
            ->sum('cost');

        $budgetAllocated = 100000000; // 100M IDR example

        return $budgetAllocated > 0 ? (($actualSpend - $budgetAllocated) / $budgetAllocated) * 100 : 0;
    }
}
