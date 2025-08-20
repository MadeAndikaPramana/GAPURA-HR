<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Department;
use App\Services\TrainingStatusService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $statusService;

    public function __construct(TrainingStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Display the main dashboard
     */
    public function index(Request $request)
    {
        // Get date range filter (default: last 30 days)
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        // Basic statistics
        $stats = $this->statusService->getDashboardStats();

        // Recent activities (new certifications, renewals, etc.)
        $recentActivities = $this->getRecentActivities($dateFrom, $dateTo);

        // Trending data for charts
        $trendsData = $this->getTrendsData();

        // Compliance alerts
        $alerts = $this->getComplianceAlerts();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'trendsData' => $trendsData,
            'alerts' => $alerts,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'chartData' => [
                'complianceByDepartment' => $this->formatDepartmentChartData($stats['compliance_by_department']),
                'complianceByType' => $this->formatTypeChartData($stats['compliance_by_type']),
                'statusDistribution' => [
                    'active' => $stats['active_certificates'],
                    'expiring_soon' => $stats['expiring_soon'],
                    'expired' => $stats['expired'],
                ],
                'monthlyTrends' => $this->getMonthlyTrends(),
            ]
        ]);
    }

    /**
     * Get recent training activities
     */
    protected function getRecentActivities($dateFrom, $dateTo)
    {
        return TrainingRecord::with(['employee', 'trainingType'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'type' => 'training_added',
                    'description' => "{$record->employee->name} completed {$record->trainingType->name}",
                    'employee' => $record->employee->name,
                    'training_type' => $record->trainingType->name,
                    'certificate_number' => $record->certificate_number,
                    'status' => $record->status,
                    'date' => $record->created_at->format('Y-m-d H:i'),
                    'relative_time' => $record->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get compliance alerts and warnings
     */
    protected function getComplianceAlerts()
    {
        $alerts = [];

        // Expiring soon alerts
        $expiringSoon = $this->statusService->getExpiringSoon(7); // 7 days
        if ($expiringSoon->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Certificates Expiring Soon',
                'message' => "{$expiringSoon->count()} certificates expire within 7 days",
                'count' => $expiringSoon->count(),
                'action_url' => route('training-records.expiring'),
                'priority' => 'high'
            ];
        }

        // Expired certificates
        $expired = TrainingRecord::where('status', 'expired')->count();
        if ($expired > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Expired Certificates',
                'message' => "{$expired} certificates have expired",
                'count' => $expired,
                'action_url' => route('training-records.index', ['status' => 'expired']),
                'priority' => 'critical'
            ];
        }

        // Low compliance departments
        $lowComplianceDepts = collect($this->statusService->getComplianceByDepartment())
            ->where('compliance_rate', '<', 70);

        if ($lowComplianceDepts->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Compliance Departments',
                'message' => "{$lowComplianceDepts->count()} departments below 70% compliance",
                'count' => $lowComplianceDepts->count(),
                'details' => $lowComplianceDepts->pluck('department_name')->toArray(),
                'priority' => 'medium'
            ];
        }

        // Sort by priority
        $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        usort($alerts, function($a, $b) use ($priorityOrder) {
            return $priorityOrder[$a['priority']] - $priorityOrder[$b['priority']];
        });

        return $alerts;
    }

    /**
     * Format department data for charts
     */
    protected function formatDepartmentChartData($departmentData)
    {
        return collect($departmentData)->map(function ($dept) {
            return [
                'name' => $dept->department_name,
                'employees' => $dept->total_employees,
                'certificates' => $dept->active_certificates,
                'compliance_rate' => round($dept->compliance_rate, 1),
                'status' => $dept->compliance_rate >= 90 ? 'excellent' :
                           ($dept->compliance_rate >= 70 ? 'good' : 'needs_improvement')
            ];
        })->values();
    }

    /**
     * Format training type data for charts
     */
    protected function formatTypeChartData($typeData)
    {
        return collect($typeData)->map(function ($type) {
            return [
                'name' => $type->training_name,
                'category' => $type->category,
                'active' => $type->active_count,
                'expiring' => $type->expiring_count,
                'expired' => $type->expired_count,
                'total' => $type->total_records,
                'compliance_rate' => $type->total_records > 0 ?
                    round(($type->active_count / $type->total_records) * 100, 1) : 0
            ];
        })->values();
    }

    /**
     * Get monthly trends data
     */
    protected function getMonthlyTrends()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->startOfMonth()->format('Y-m-d');
            $endOfMonth = $date->endOfMonth()->format('Y-m-d');

            $trainingCount = TrainingRecord::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $activeCount = TrainingRecord::where('status', 'active')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $months[] = [
                'month' => $date->format('M Y'),
                'short_month' => $date->format('M'),
                'trainings_added' => $trainingCount,
                'active_certificates' => $activeCount,
                'date' => $date->format('Y-m-d'),
            ];
        }

        return $months;
    }

    /**
     * Get trending statistics for comparison
     */
    protected function getTrendsData()
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // This month vs last month
        $thisMonthCount = TrainingRecord::where('created_at', '>=', $thisMonth)->count();
        $lastMonthCount = TrainingRecord::whereBetween('created_at', [
            $lastMonth, $thisMonth
        ])->count();

        $growthRate = $lastMonthCount > 0 ?
            round((($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100, 1) : 0;

        return [
            'this_month' => $thisMonthCount,
            'last_month' => $lastMonthCount,
            'growth_rate' => $growthRate,
            'trend' => $growthRate > 0 ? 'up' : ($growthRate < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Export dashboard data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'pdf'); // pdf, excel, csv

        switch ($format) {
            case 'excel':
                return $this->exportToExcel();
            case 'csv':
                return $this->exportToCsv();
            case 'pdf':
            default:
                return $this->exportToPdf();
        }
    }

    /**
     * Get dashboard data for API
     */
    public function apiData(Request $request)
    {
        $stats = $this->statusService->getDashboardStats();

        return response()->json([
            'stats' => $stats,
            'alerts' => $this->getComplianceAlerts(),
            'timestamp' => Carbon::now()->toISOString(),
        ]);
    }

    /**
     * Refresh dashboard data (AJAX endpoint)
     */
    public function refresh(Request $request)
    {
        // Update statuses first
        $this->statusService->updateAllStatuses();

        // Return updated stats
        return $this->apiData($request);
    }

    // Export methods would be implemented here
    protected function exportToExcel() { /* Implementation */ }
    protected function exportToCsv() { /* Implementation */ }
    protected function exportToPdf() { /* Implementation */ }
}
