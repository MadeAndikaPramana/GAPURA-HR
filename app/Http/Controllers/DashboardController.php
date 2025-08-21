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
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the training dashboard
     */
    public function index(Request $request)
    {
        // Get current user
        $user = auth()->user();

        // Determine dashboard type based on user role/permissions
        $dashboardType = $this->determineDashboardType($user);

        switch ($dashboardType) {
            case 'executive':
                return $this->executiveDashboard($request);
            case 'manager':
                return $this->managerDashboard($request, $user);
            case 'hr':
                return $this->hrDashboard($request);
            case 'employee':
                return $this->employeeDashboard($request, $user);
            default:
                return $this->hrDashboard($request); // Default to HR dashboard
        }
    }

    /**
     * Executive Dashboard - High-level overview
     */
    protected function executiveDashboard(Request $request)
    {
        $timeRange = $request->get('range', '30'); // days
        $startDate = now()->subDays($timeRange);

        // Key Performance Indicators
        $kpis = [
            'overall_compliance' => $this->calculateOverallComplianceRate(),
            'total_employees' => Employee::where('status', 'active')->count(),
            'total_training_hours' => TrainingRecord::where('status', 'completed')
                ->where('completion_date', '>=', $startDate)
                ->sum('training_hours'),
            'training_investment' => TrainingRecord::where('status', 'completed')
                ->where('completion_date', '>=', $startDate)
                ->sum('cost'),
            'active_certificates' => Certificate::active()->count(),
            'expiring_certificates' => Certificate::expiringSoon(30)->count(),
            'critical_gaps' => $this->getCriticalComplianceGaps()
        ];

        // Compliance trends by month
        $complianceTrends = $this->getComplianceTrends(12);

        // Department performance comparison
        $departmentPerformance = $this->getDepartmentPerformanceComparison();

        // Training ROI analysis
        $trainingROI = $this->calculateTrainingROI($timeRange);

        // Risk indicators
        $riskIndicators = $this->getRiskIndicators();

        // Recent achievements
        $achievements = $this->getRecentAchievements($timeRange);

        return Inertia::render('Dashboard/Executive', [
            'kpis' => $kpis,
            'complianceTrends' => $complianceTrends,
            'departmentPerformance' => $departmentPerformance,
            'trainingROI' => $trainingROI,
            'riskIndicators' => $riskIndicators,
            'achievements' => $achievements,
            'timeRange' => $timeRange
        ]);
    }

    /**
     * Manager Dashboard - Department-focused view
     */
    protected function managerDashboard(Request $request, $user)
    {
        $managedDepartments = Department::where('manager_id', $user->id)->get();
        $departmentIds = $managedDepartments->pluck('id');

        // Team compliance overview
        $teamOverview = [
            'total_team_members' => Employee::whereIn('department_id', $departmentIds)
                ->where('status', 'active')->count(),
            'compliant_members' => Employee::whereIn('department_id', $departmentIds)
                ->where('status', 'active')
                ->whereDoesntHave('trainingRecords', function ($query) {
                    $query->where('compliance_status', 'expired')
                          ->whereHas('trainingType', function ($typeQuery) {
                              $typeQuery->where('is_mandatory', true);
                          });
                })->count(),
            'members_with_expiring_certs' => Employee::whereIn('department_id', $departmentIds)
                ->whereHas('certificates', function ($query) {
                    $query->expiringSoon(30);
                })->count(),
            'pending_training_requests' => TrainingRecord::whereHas('employee', function ($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->where('status', 'registered')->count()
        ];

        // Individual team member compliance
        $teamCompliance = Employee::whereIn('department_id', $departmentIds)
            ->where('status', 'active')
            ->with(['department', 'trainingRecords.trainingType'])
            ->get()
            ->map(function ($employee) {
                return [
                    'employee' => $employee,
                    'compliance_summary' => $employee->getTrainingComplianceSummary(),
                    'upcoming_trainings' => $employee->getUpcomingTrainings(),
                    'expiring_certificates' => $employee->certificates()
                        ->expiringSoon(60)
                        ->with('trainingRecord.trainingType')
                        ->get()
                ];
            });

        // Department training schedule
        $upcomingTrainings = TrainingRecord::whereHas('employee', function ($query) use ($departmentIds) {
                $query->whereIn('department_id', $departmentIds);
            })
            ->where('training_date', '>=', now())
            ->where('training_date', '<=', now()->addDays(30))
            ->with(['employee', 'trainingType', 'trainingProvider'])
            ->orderBy('training_date')
            ->get();

        // Training budget utilization
        $budgetUtilization = $this->getDepartmentBudgetUtilization($departmentIds);

        return Inertia::render('Dashboard/Manager', [
            'managedDepartments' => $managedDepartments,
            'teamOverview' => $teamOverview,
            'teamCompliance' => $teamCompliance,
            'upcomingTrainings' => $upcomingTrainings,
            'budgetUtilization' => $budgetUtilization
        ]);
    }

    /**
     * HR Dashboard - Operational focus
     */
    protected function hrDashboard(Request $request)
    {
        // Quick stats
        $quickStats = [
            'total_employees' => Employee::where('status', 'active')->count(),
            'total_trainings' => TrainingRecord::count(),
            'valid_certificates' => Certificate::active()->count(),
            'expiring_soon' => Certificate::expiringSoon(30)->count(),
            'expired_certificates' => Certificate::expired()->count(),
            'pending_registrations' => TrainingRecord::where('status', 'registered')->count()
        ];

        // Compliance overview by department
        $departmentCompliance = Department::with('employees')->get()->map(function ($department) {
            $totalEmployees = $department->employees->where('status', 'active')->count();
            $compliantEmployees = $department->employees->where('status', 'active')->filter(function ($employee) {
                return $employee->compliance_status === 'compliant';
            })->count();

            return [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'total_employees' => $totalEmployees,
                'compliant_employees' => $compliantEmployees,
                'compliance_rate' => $totalEmployees > 0 ? round(($compliantEmployees / $totalEmployees) * 100, 1) : 0,
                'expiring_soon' => Certificate::expiringSoon(30)
                    ->whereHas('trainingRecord.employee', function ($query) use ($department) {
                        $query->where('department_id', $department->id);
                    })->count(),
                'expired' => Certificate::expired()
                    ->whereHas('trainingRecord.employee', function ($query) use ($department) {
                        $query->where('department_id', $department->id);
                    })->count()
            ];
        });

        // Training completion trends
        $completionTrends = TrainingRecord::selectRaw('
                DATE_FORMAT(completion_date, "%Y-%m") as month,
                COUNT(*) as completed_count,
                AVG(score) as average_score
            ')
            ->where('status', 'completed')
            ->where('completion_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Training by category
        $trainingByCategory = TrainingCategory::withCount([
                'trainingRecords as completed_count' => function ($query) {
                    $query->where('status', 'completed');
                },
                'trainingRecords as active_count' => function ($query) {
                    $query->where('compliance_status', 'compliant');
                }
            ])
            ->get();

        // Urgent actions required
        $urgentActions = [
            'expired_certificates' => Certificate::expired()
                ->with(['trainingRecord.employee.department', 'trainingRecord.trainingType'])
                ->limit(10)
                ->get(),
            'expiring_this_week' => Certificate::expiringSoon(7)
                ->with(['trainingRecord.employee.department', 'trainingRecord.trainingType'])
                ->limit(10)
                ->get(),
            'overdue_training' => TrainingRecord::where('status', 'registered')
                ->where('training_date', '<', now())
                ->with(['employee.department', 'trainingType'])
                ->limit(10)
                ->get(),
            'missing_mandatory' => Employee::missingMandatoryTraining()
                ->with('department')
                ->limit(10)
                ->get()
        ];

        // Recent activities
        $recentActivities = $this->getRecentActivities();

        // Provider performance
        $providerPerformance = $this->getProviderPerformance();

        return Inertia::render('Dashboard/HR', [
            'quickStats' => $quickStats,
            'departmentCompliance' => $departmentCompliance,
            'completionTrends' => $completionTrends,
            'trainingByCategory' => $trainingByCategory,
            'urgentActions' => $urgentActions,
            'recentActivities' => $recentActivities,
            'providerPerformance' => $providerPerformance
        ]);
    }

    /**
     * Employee Dashboard - Personal view
     */
    protected function employeeDashboard(Request $request, $user)
    {
        $employee = Employee::find($user->id);
        if (!$employee) {
            return redirect()->route('hr.dashboard');
        }

        return Inertia::render('Dashboard/Employee', $employee->getDashboardData());
    }

    /**
     * Determine dashboard type based on user
     */
    protected function determineDashboardType($user)
    {
        // This would typically be based on roles/permissions
        // For now, using simple logic based on position or department

        if ($user->position_level === 'executive') {
            return 'executive';
        }

        if ($user->position_level === 'manager' || Department::where('manager_id', $user->id)->exists()) {
            return 'manager';
        }

        if ($user->department?->code === 'HR') {
            return 'hr';
        }

        return 'employee';
    }

    /**
     * Calculate overall compliance rate
     */
    protected function calculateOverallComplianceRate()
    {
        $totalEmployees = Employee::where('status', 'active')->count();
        if ($totalEmployees === 0) return 100;

        $compliantEmployees = Employee::where('status', 'active')
            ->whereDoesntHave('trainingRecords', function ($query) {
                $query->where('compliance_status', 'expired')
                      ->whereHas('trainingType', function ($typeQuery) {
                          $typeQuery->where('is_mandatory', true);
                      });
            })->count();

        return round(($compliantEmployees / $totalEmployees) * 100, 1);
    }

    /**
     * Get critical compliance gaps
     */
    protected function getCriticalComplianceGaps()
    {
        return Employee::where('status', 'active')
            ->whereHas('trainingRecords', function ($query) {
                $query->where('compliance_status', 'expired')
                      ->whereHas('trainingType', function ($typeQuery) {
                          $typeQuery->where('is_mandatory', true);
                      });
            })->count();
    }

    /**
     * Get compliance trends over time
     */
    protected function getComplianceTrends($months = 12)
    {
        $trends = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            // This is a simplified calculation - in reality, you'd want to track historical compliance
            $totalEmployees = Employee::where('status', 'active')
                ->where('created_at', '<=', $date->endOfMonth())
                ->count();

            $completedTrainings = TrainingRecord::where('status', 'completed')
                ->whereYear('completion_date', $date->year)
                ->whereMonth('completion_date', $date->month)
                ->count();

            $trends[] = [
                'month' => $monthKey,
                'month_name' => $date->format('M Y'),
                'total_employees' => $totalEmployees,
                'completed_trainings' => $completedTrainings,
                'compliance_rate' => $totalEmployees > 0 ? round(($completedTrainings / $totalEmployees) * 100, 1) : 0
            ];
        }

        return $trends;
    }

    /**
     * Get department performance comparison
     */
    protected function getDepartmentPerformanceComparison()
    {
        return Department::with('employees')->get()->map(function ($department) {
            $employees = $department->employees->where('status', 'active');
            $totalEmployees = $employees->count();

            if ($totalEmployees === 0) {
                return [
                    'department' => $department->name,
                    'compliance_rate' => 0,
                    'total_employees' => 0,
                    'avg_training_hours' => 0,
                    'training_cost' => 0
                ];
            }

            $compliantEmployees = $employees->filter(function ($employee) {
                return $employee->compliance_status === 'compliant';
            })->count();

            $trainingHours = TrainingRecord::whereHas('employee', function ($query) use ($department) {
                    $query->where('department_id', $department->id);
                })
                ->where('status', 'completed')
                ->where('completion_date', '>=', now()->subYear())
                ->sum('training_hours');

            $trainingCost = TrainingRecord::whereHas('employee', function ($query) use ($department) {
                    $query->where('department_id', $department->id);
                })
                ->where('status', 'completed')
                ->where('completion_date', '>=', now()->subYear())
                ->sum('cost');

            return [
                'department' => $department->name,
                'compliance_rate' => round(($compliantEmployees / $totalEmployees) * 100, 1),
                'total_employees' => $totalEmployees,
                'avg_training_hours' => round($trainingHours / $totalEmployees, 1),
                'training_cost' => $trainingCost
            ];
        });
    }

    /**
     * Calculate training ROI
     */
    protected function calculateTrainingROI($days = 30)
    {
        $trainingCost = TrainingRecord::where('status', 'completed')
            ->where('completion_date', '>=', now()->subDays($days))
            ->sum('cost');

        $avgScore = TrainingRecord::where('status', 'completed')
            ->where('completion_date', '>=', now()->subDays($days))
            ->avg('score');

        // Simplified ROI calculation
        $roi = $trainingCost > 0 ? round((($avgScore - 70) / 30) * 100, 1) : 0;

        return [
            'total_investment' => $trainingCost,
            'average_score' => round($avgScore ?: 0, 1),
            'roi_percentage' => $roi,
            'total_hours' => TrainingRecord::where('status', 'completed')
                ->where('completion_date', '>=', now()->subDays($days))
                ->sum('training_hours')
        ];
    }

    /**
     * Get risk indicators
     */
    protected function getRiskIndicators()
    {
        return [
            'expired_mandatory' => Employee::where('status', 'active')
                ->whereHas('trainingRecords', function ($query) {
                    $query->where('compliance_status', 'expired')
                          ->whereHas('trainingType', function ($typeQuery) {
                              $typeQuery->where('is_mandatory', true);
                          });
                })->count(),
            'high_risk_departments' => Department::whereHas('employees.trainingRecords', function ($query) {
                    $query->where('compliance_status', 'expired')
                          ->whereHas('trainingType', function ($typeQuery) {
                              $typeQuery->where('is_mandatory', true);
                          });
                })->count(),
            'provider_issues' => \App\Models\TrainingProvider::where('rating', '<', 3.0)->count(),
            'budget_variance' => $this->calculateBudgetVariance()
        ];
    }

    /**
     * Get recent achievements
     */
    protected function getRecentAchievements($days = 30)
    {
        return [
            'completed_trainings' => TrainingRecord::where('status', 'completed')
                ->where('completion_date', '>=', now()->subDays($days))
                ->count(),
            'high_scores' => TrainingRecord::where('status', 'completed')
                ->where('completion_date', '>=', now()->subDays($days))
                ->where('score', '>=', 90)
                ->count(),
            'perfect_scores' => TrainingRecord::where('status', 'completed')
                ->where('completion_date', '>=', now()->subDays($days))
                ->where('score', 100)
                ->count(),
            'certificates_issued' => Certificate::where('issue_date', '>=', now()->subDays($days))
                ->count()
        ];
    }

    /**
     * Get department budget utilization
     */
    protected function getDepartmentBudgetUtilization($departmentIds)
    {
        $spent = TrainingRecord::whereHas('employee', function ($query) use ($departmentIds) {
                $query->whereIn('department_id', $departmentIds);
            })
            ->where('status', 'completed')
            ->whereYear('completion_date', date('Y'))
            ->sum('cost');

        // This would typically come from a budget table
        $budgetAllocated = 50000000; // 50M IDR example

        return [
            'allocated' => $budgetAllocated,
            'spent' => $spent,
            'remaining' => $budgetAllocated - $spent,
            'utilization_rate' => $budgetAllocated > 0 ? round(($spent / $budgetAllocated) * 100, 1) : 0
        ];
    }

    /**
     * Get recent activities
     */
    protected function getRecentActivities()
    {
        return TrainingRecord::with(['employee.department', 'trainingType'])
            ->whereIn('status', ['completed', 'registered'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'type' => $record->status === 'completed' ? 'training_completed' : 'training_registered',
                    'employee_name' => $record->employee->name,
                    'department' => $record->employee->department->name,
                    'training_name' => $record->trainingType->name,
                    'date' => $record->updated_at,
                    'score' => $record->score
                ];
            });
    }

    /**
     * Get provider performance summary
     */
    protected function getProviderPerformance()
    {
        return \App\Models\TrainingProvider::withCount([
                'trainingRecords as completed_trainings' => function ($query) {
                    $query->where('status', 'completed')
                          ->where('completion_date', '>=', now()->subMonths(6));
                }
            ])
            ->with(['trainingRecords' => function ($query) {
                $query->where('status', 'completed')
                      ->where('completion_date', '>=', now()->subMonths(6));
            }])
            ->get()
            ->map(function ($provider) {
                $avgScore = $provider->trainingRecords->avg('score');
                return [
                    'name' => $provider->name,
                    'rating' => $provider->rating,
                    'completed_trainings' => $provider->completed_trainings,
                    'average_score' => round($avgScore ?: 0, 1),
                    'performance_trend' => $avgScore >= 85 ? 'excellent' : ($avgScore >= 75 ? 'good' : 'needs_improvement')
                ];
            });
    }

    /**
     * Calculate budget variance
     */
    protected function calculateBudgetVariance()
    {
        // Simplified budget variance calculation
        $currentMonthSpend = TrainingRecord::where('status', 'completed')
            ->whereMonth('completion_date', date('m'))
            ->whereYear('completion_date', date('Y'))
            ->sum('cost');

        $monthlyBudget = 5000000; // 5M IDR example monthly budget

        return $monthlyBudget > 0 ? round((($currentMonthSpend - $monthlyBudget) / $monthlyBudget) * 100, 1) : 0;
    }
}
