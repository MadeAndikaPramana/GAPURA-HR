<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with analytics
     */
    public function index()
    {
        // Main statistics
        $statistics = $this->getStatistics();

        // Chart data
        $charts = $this->getChartsData();

        // Recent activities (expiring/expired certificates)
        $recentActivities = $this->getRecentActivities();

        return Inertia::render('Dashboard/Index', [
            'statistics' => $statistics,
            'charts' => $charts,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Get main statistics for dashboard cards
     */
    private function getStatistics()
    {
        $totalEmployees = Employee::count();
        $totalCertificates = EmployeeCertificate::count();
        $activeCertificates = EmployeeCertificate::where('status', 'active')->count();
        $expiredCertificates = EmployeeCertificate::where('status', 'expired')->count();
        $expiringSoon = EmployeeCertificate::where('status', 'expiring_soon')->count();

        // Calculate compliance rate
        $complianceRate = 0;
        if ($totalEmployees > 0) {
            $employeesWithValidCerts = Employee::whereHas('employeeCertificates', function($query) {
                $query->where('status', 'active');
            })->count();

            $complianceRate = round(($employeesWithValidCerts / $totalEmployees) * 100, 1);
        }

        return [
            'total_employees' => $totalEmployees,
            'total_certificates' => $totalCertificates,
            'active_certificates' => $activeCertificates,
            'expired_certificates' => $expiredCertificates,
            'expiring_soon' => $expiringSoon,
            'compliance_rate' => $complianceRate,
            'total_departments' => Department::count(),
            'total_training_types' => CertificateType::where('is_active', true)->count(),
        ];
    }

    /**
     * Get chart data for visualizations
     */
    private function getChartsData()
    {
        return [
            'statusData' => $this->getStatusChartData(),
            'departmentData' => $this->getDepartmentChartData(),
            'trendData' => $this->getTrendChartData(),
        ];
    }

    /**
     * Get certificate status distribution data
     */
    private function getStatusChartData()
    {
        $active = EmployeeCertificate::where('status', 'active')->count();
        $expiring = EmployeeCertificate::where('status', 'expiring_soon')->count();
        $expired = EmployeeCertificate::where('status', 'expired')->count();

        return [
            ['name' => 'Active', 'value' => $active, 'color' => '#10b981'],
            ['name' => 'Expiring Soon', 'value' => $expiring, 'color' => '#f59e0b'],
            ['name' => 'Expired', 'value' => $expired, 'color' => '#ef4444'],
        ];
    }

    /**
     * Get certificates by department data
     */
    private function getDepartmentChartData()
    {
        $departments = Department::with(['employees.employeeCertificates'])
            ->get()
            ->map(function ($dept) {
                $certificates = $dept->employees->pluck('employeeCertificates')->flatten();

                return [
                    'department' => $dept->name,
                    'total' => $certificates->count(),
                    'active' => $certificates->where('status', 'active')->count(),
                    'expired' => $certificates->where('status', 'expired')->count(),
                    'expiring' => $certificates->where('status', 'expiring_soon')->count(),
                ];
            })
            ->filter(function ($dept) {
                return $dept['total'] > 0; // Only show departments with certificates
            })
            ->values()
            ->take(10); // Limit to top 10 departments

        return $departments;
    }

    /**
     * Get training completion trend for last 6 months
     */
    private function getTrendChartData()
    {
        $months = [];
        $data = [];

        // Get last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            $startOfMonth = $date->startOfMonth()->toDateString();
            $endOfMonth = $date->endOfMonth()->toDateString();

            $completed = EmployeeCertificate::whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                ->count();

            $data[] = [
                'month' => $monthName,
                'completed' => $completed,
            ];
        }

        return $data;
    }

    /**
     * Get recent activities and alerts
     */
    private function getRecentActivities()
    {
        $activities = [];

        // Get expiring soon certificates
        $expiring = EmployeeCertificate::with(['employee', 'certificateType'])
            ->where('status', 'expiring_soon')
            ->orderBy('expiry_date', 'asc')
            ->limit(5)
            ->get();

        foreach ($expiring as $cert) {
            $daysLeft = $cert->expiry_date ? $cert->expiry_date->diffInDays(now()) : 0;

            $activities[] = [
                'type' => 'expiring',
                'title' => 'Certificate Expiring Soon',
                'description' => "{$cert->employee->name} - {$cert->certificateType->name} expires in {$daysLeft} days",
                'time' => "Expires on " . $cert->expiry_date->format('d M Y'),
                'url' => route('employee-containers.show', $cert->employee_id),
            ];
        }

        // Get recently expired certificates
        $expired = EmployeeCertificate::with(['employee', 'certificateType'])
            ->where('status', 'expired')
            ->where('expiry_date', '>=', Carbon::now()->subDays(30))
            ->orderBy('expiry_date', 'desc')
            ->limit(3)
            ->get();

        foreach ($expired as $cert) {
            $daysAgo = $cert->expiry_date ? $cert->expiry_date->diffInDays(now()) : 0;

            $activities[] = [
                'type' => 'expired',
                'title' => 'Certificate Expired',
                'description' => "{$cert->employee->name} - {$cert->certificateType->name}",
                'time' => "Expired {$daysAgo} days ago",
                'url' => route('employee-containers.show', $cert->employee_id),
            ];
        }

        // Get recently issued certificates
        $recent = EmployeeCertificate::with(['employee', 'certificateType'])
            ->where('issue_date', '>=', Carbon::now()->subDays(7))
            ->orderBy('issue_date', 'desc')
            ->limit(3)
            ->get();

        foreach ($recent as $cert) {
            $activities[] = [
                'type' => 'issued',
                'title' => 'New Certificate Issued',
                'description' => "{$cert->employee->name} - {$cert->certificateType->name}",
                'time' => $cert->issue_date->diffForHumans(),
                'url' => route('employee-containers.show', $cert->employee_id),
            ];
        }

        // Sort by most urgent (expired first, then expiring, then new)
        usort($activities, function ($a, $b) {
            $order = ['expired' => 0, 'expiring' => 1, 'issued' => 2];
            return $order[$a['type']] <=> $order[$b['type']];
        });

        return array_slice($activities, 0, 8); // Return top 8 activities
    }

    /**
     * Get dashboard statistics (API endpoint)
     */
    public function getStats()
    {
        return response()->json([
            'statistics' => $this->getStatistics(),
            'charts' => $this->getChartsData(),
        ]);
    }
}
