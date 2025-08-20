<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Department;
use App\Services\TrainingStatusService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    protected $trainingStatusService;

    public function __construct(TrainingStatusService $trainingStatusService)
    {
        $this->trainingStatusService = $trainingStatusService;
    }

    /**
     * Display the main dashboard with comprehensive statistics
     */
    public function index(Request $request)
    {
        // Get main statistics
        $stats = $this->getMainStatistics();

        // Get compliance by department
        $complianceByDepartment = $this->getComplianceByDepartment();

        // Get upcoming expirations
        $upcomingExpirations = $this->getUpcomingExpirations();

        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        // Get monthly trends
        $monthlyTrends = $this->getMonthlyTrends();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'complianceByDepartment' => $complianceByDepartment,
            'upcomingExpirations' => $upcomingExpirations,
            'recentActivities' => $recentActivities,
            'monthlyTrends' => $monthlyTrends
        ]);
    }

    /**
     * Get main dashboard statistics
     */
    private function getMainStatistics()
    {
        // Current counts
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $totalCertificates = TrainingRecord::count();
        $activeCertificates = TrainingRecord::where('status', 'active')->count();
        $expiringSoon = TrainingRecord::where('status', 'expiring_soon')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();

        // Previous period for trends (30 days ago)
        $previousPeriodStart = Carbon::now()->subDays(60);
        $previousPeriodEnd = Carbon::now()->subDays(30);
        $currentPeriodStart = Carbon::now()->subDays(30);

        // Calculate trends
        $previousEmployees = Employee::where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<', $previousPeriodEnd)
            ->count();
        $currentEmployees = Employee::where('created_at', '>=', $currentPeriodStart)
            ->count();
        $employeeTrend = $currentEmployees - $previousEmployees;

        $previousCertificates = TrainingRecord::where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<', $previousPeriodEnd)
            ->count();
        $currentCertificates = TrainingRecord::where('created_at', '>=', $currentPeriodStart)
            ->count();
        $certificateTrend = $currentCertificates - $previousCertificates;

        $previousActive = TrainingRecord::where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<', $previousPeriodEnd)
            ->where('status', 'active')
            ->count();
        $currentActive = TrainingRecord::where('created_at', '>=', $currentPeriodStart)
            ->where('status', 'active')
            ->count();
        $activeTrend = $currentActive - $previousActive;

        return [
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'total_certificates' => $totalCertificates,
            'active_certificates' => $activeCertificates,
            'expiring_certificates' => $expiringSoon,
            'expired_certificates' => $expired,
            'employee_trend' => $employeeTrend,
            'certificate_trend' => $certificateTrend,
            'active_trend' => $activeTrend,
            'overall_compliance_rate' => $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 1) : 0
        ];
    }

    /**
     * Get compliance statistics by department
     */
    private function getComplianceByDepartment()
    {
        return DB::table('departments')
            ->leftJoin('employees', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('training_records', function($join) {
                $join->on('employees.id', '=', 'training_records.employee_id');
            })
            ->selectRaw('
                departments.id as department_id,
                departments.name as department_name,
                departments.code as department_code,
                COUNT(DISTINCT employees.id) as total_employees,
                COUNT(DISTINCT CASE WHEN employees.status = "active" THEN employees.id END) as active_employees,
                COUNT(training_records.id) as total_certificates,
                COUNT(CASE WHEN training_records.status = "active" THEN training_records.id END) as active_certificates,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN training_records.id END) as expiring_certificates,
                COUNT(CASE WHEN training_records.status = "expired" THEN training_records.id END) as expired_certificates,
                ROUND(
                    CASE
                        WHEN COUNT(training_records.id) > 0
                        THEN (COUNT(CASE WHEN training_records.status = "active" THEN training_records.id END) / COUNT(training_records.id)) * 100
                        ELSE 0
                    END, 1
                ) as compliance_rate
            ')
            ->where('employees.status', 'active')
            ->groupBy('departments.id', 'departments.name', 'departments.code')
            ->orderBy('compliance_rate', 'desc')
            ->get();
    }

    /**
     * Get upcoming certificate expirations
     */
    private function getUpcomingExpirations($days = 30)
    {
        return TrainingRecord::with(['employee', 'trainingType'])
            ->join('employees', 'training_records.employee_id', '=', 'employees.id')
            ->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
            ->select(
                'training_records.*',
                'employees.name as employee_name',
                'employees.employee_id as employee_code',
                'training_types.name as training_type_name'
            )
            ->where('training_records.expiry_date', '<=', Carbon::now()->addDays($days))
            ->where('training_records.expiry_date', '>=', Carbon::now())
            ->whereIn('training_records.status', ['active', 'expiring_soon'])
            ->orderBy('training_records.expiry_date', 'asc')
            ->limit(10)
            ->get();
    }

    /**
     * Get recent training activities
     */
    private function getRecentActivities($limit = 10)
    {
        return TrainingRecord::with(['employee', 'trainingType'])
            ->join('employees', 'training_records.employee_id', '=', 'employees.id')
            ->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
            ->select(
                'training_records.*',
                'employees.name as employee_name',
                'employees.employee_id as employee_code',
                'training_types.name as training_type_name'
            )
            ->orderBy('training_records.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get monthly training trends for the current year
     */
    private function getMonthlyTrends()
    {
        return TrainingRecord::selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as certificates_issued,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active_issued
            ')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * Refresh dashboard data (force update training statuses)
     */
    public function refresh()
    {
        try {
            $updated = $this->trainingStatusService->updateAllStatuses();

            return redirect()->route('dashboard')
                ->with('success', "Dashboard refreshed successfully. {$updated} training statuses updated.");
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Error refreshing dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Get dashboard data via API (for AJAX updates)
     */
    public function apiData(Request $request)
    {
        $data = [
            'stats' => $this->getMainStatistics(),
            'compliance_by_department' => $this->getComplianceByDepartment(),
            'upcoming_expirations' => $this->getUpcomingExpirations(),
            'recent_activities' => $this->getRecentActivities(),
            'monthly_trends' => $this->getMonthlyTrends(),
            'timestamp' => now()->toISOString()
        ];

        return response()->json($data);
    }

    /**
     * Export comprehensive dashboard report
     */
    public function export(Request $request)
    {
        $stats = $this->getMainStatistics();
        $complianceByDepartment = $this->getComplianceByDepartment();
        $upcomingExpirations = $this->getUpcomingExpirations(60); // Extended for report
        $monthlyTrends = $this->getMonthlyTrends();

        // Prepare data for multiple sheets
        $exportData = [
            'summary' => [
                [
                    'Metric' => 'Total Employees',
                    'Value' => $stats['total_employees'],
                    'Active' => $stats['active_employees'],
                    'Trend' => $stats['employee_trend'] > 0 ? '+' . $stats['employee_trend'] : $stats['employee_trend']
                ],
                [
                    'Metric' => 'Total Certificates',
                    'Value' => $stats['total_certificates'],
                    'Active' => $stats['active_certificates'],
                    'Trend' => $stats['certificate_trend'] > 0 ? '+' . $stats['certificate_trend'] : $stats['certificate_trend']
                ],
                [
                    'Metric' => 'Expiring Soon',
                    'Value' => $stats['expiring_certificates'],
                    'Active' => '-',
                    'Trend' => 'Next 30 days'
                ],
                [
                    'Metric' => 'Expired',
                    'Value' => $stats['expired_certificates'],
                    'Active' => '-',
                    'Trend' => 'Action Required'
                ],
                [
                    'Metric' => 'Overall Compliance Rate',
                    'Value' => $stats['overall_compliance_rate'] . '%',
                    'Active' => '-',
                    'Trend' => $stats['overall_compliance_rate'] >= 90 ? 'Excellent' : ($stats['overall_compliance_rate'] >= 80 ? 'Good' : 'Needs Improvement')
                ]
            ],
            'department_compliance' => $complianceByDepartment->map(function($dept) {
                return [
                    'Department' => $dept->department_name,
                    'Code' => $dept->department_code,
                    'Total Employees' => $dept->total_employees,
                    'Active Employees' => $dept->active_employees,
                    'Total Certificates' => $dept->total_certificates,
                    'Active Certificates' => $dept->active_certificates,
                    'Expiring Certificates' => $dept->expiring_certificates,
                    'Expired Certificates' => $dept->expired_certificates,
                    'Compliance Rate %' => $dept->compliance_rate
                ];
            })->toArray(),
            'upcoming_expirations' => $upcomingExpirations->map(function($exp) {
                return [
                    'Employee' => $exp->employee_name,
                    'Employee ID' => $exp->employee_code,
                    'Training Type' => $exp->training_type_name,
                    'Certificate Number' => $exp->certificate_number,
                    'Issue Date' => $exp->issue_date,
                    'Expiry Date' => $exp->expiry_date,
                    'Status' => ucfirst(str_replace('_', ' ', $exp->status)),
                    'Days Until Expiry' => Carbon::parse($exp->expiry_date)->diffInDays(Carbon::now()),
                    'Issuer' => $exp->issuer
                ];
            })->toArray(),
            'monthly_trends' => $monthlyTrends->map(function($trend) {
                return [
                    'Year' => $trend->year,
                    'Month' => $trend->month,
                    'Month Name' => Carbon::create($trend->year, $trend->month, 1)->format('F'),
                    'Certificates Issued' => $trend->certificates_issued,
                    'Active Certificates' => $trend->active_issued
                ];
            })->toArray()
        ];

        return Excel::download(new class($exportData) implements
            \Maatwebsite\Excel\Concerns\WithMultipleSheets
        {
            private $data;

            public function __construct($data) {
                $this->data = $data;
            }

            public function sheets(): array {
                return [
                    'Summary' => new class($this->data['summary']) implements
                        \Maatwebsite\Excel\Concerns\FromArray,
                        \Maatwebsite\Excel\Concerns\WithHeadings,
                        \Maatwebsite\Excel\Concerns\WithStyles,
                        \Maatwebsite\Excel\Concerns\WithTitle
                    {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array { return ['Metric', 'Value', 'Active', 'Trend']; }
                        public function title(): string { return 'Summary'; }
                        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                            return [1 => ['font' => ['bold' => true]]];
                        }
                    },
                    'Department Compliance' => new class($this->data['department_compliance']) implements
                        \Maatwebsite\Excel\Concerns\FromArray,
                        \Maatwebsite\Excel\Concerns\WithHeadings,
                        \Maatwebsite\Excel\Concerns\WithStyles,
                        \Maatwebsite\Excel\Concerns\WithTitle
                    {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array {
                            return ['Department', 'Code', 'Total Employees', 'Active Employees',
                                   'Total Certificates', 'Active Certificates', 'Expiring Certificates',
                                   'Expired Certificates', 'Compliance Rate %'];
                        }
                        public function title(): string { return 'Department Compliance'; }
                        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                            return [1 => ['font' => ['bold' => true]]];
                        }
                    },
                    'Upcoming Expirations' => new class($this->data['upcoming_expirations']) implements
                        \Maatwebsite\Excel\Concerns\FromArray,
                        \Maatwebsite\Excel\Concerns\WithHeadings,
                        \Maatwebsite\Excel\Concerns\WithStyles,
                        \Maatwebsite\Excel\Concerns\WithTitle
                    {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array {
                            return ['Employee', 'Employee ID', 'Training Type', 'Certificate Number',
                                   'Issue Date', 'Expiry Date', 'Status', 'Days Until Expiry', 'Issuer'];
                        }
                        public function title(): string { return 'Upcoming Expirations'; }
                        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                            return [1 => ['font' => ['bold' => true]]];
                        }
                    },
                    'Monthly Trends' => new class($this->data['monthly_trends']) implements
                        \Maatwebsite\Excel\Concerns\FromArray,
                        \Maatwebsite\Excel\Concerns\WithHeadings,
                        \Maatwebsite\Excel\Concerns\WithStyles,
                        \Maatwebsite\Excel\Concerns\WithTitle
                    {
                        private $data;
                        public function __construct($data) { $this->data = $data; }
                        public function array(): array { return $this->data; }
                        public function headings(): array {
                            return ['Year', 'Month', 'Month Name', 'Certificates Issued', 'Active Certificates'];
                        }
                        public function title(): string { return 'Monthly Trends'; }
                        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                            return [1 => ['font' => ['bold' => true]]];
                        }
                    }
                ];
            }
        }, 'gapura_training_dashboard_' . Carbon::now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Get specific department dashboard
     */
    public function departmentDashboard(Department $department)
    {
        $departmentStats = $this->trainingStatusService->getComplianceByDepartment()
            ->where('department_id', $department->id)
            ->first();

        $employees = Employee::where('department_id', $department->id)
            ->with(['trainingRecords.trainingType'])
            ->get();

        $upcomingExpirations = TrainingRecord::with(['employee', 'trainingType'])
            ->whereHas('employee', function($q) use ($department) {
                $q->where('department_id', $department->id);
            })
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>=', Carbon::now())
            ->whereIn('status', ['active', 'expiring_soon'])
            ->orderBy('expiry_date', 'asc')
            ->get();

        return Inertia::render('Departments/Dashboard', [
            'department' => $department,
            'departmentStats' => $departmentStats,
            'employees' => $employees,
            'upcomingExpirations' => $upcomingExpirations
        ]);
    }

    /**
     * Get system health check
     */
    public function healthCheck()
    {
        $health = [
            'status' => 'healthy',
            'checks' => [
                'database' => $this->checkDatabase(),
                'training_statuses' => $this->checkTrainingStatuses(),
                'data_integrity' => $this->checkDataIntegrity()
            ],
            'timestamp' => now()->toISOString()
        ];

        $overallStatus = collect($health['checks'])->every(fn($check) => $check['status'] === 'ok');
        $health['status'] = $overallStatus ? 'healthy' : 'warning';

        return response()->json($health);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    private function checkTrainingStatuses()
    {
        $outdatedStatuses = TrainingRecord::where('updated_at', '<', Carbon::now()->subHours(24))
            ->whereIn('status', ['active', 'expiring_soon'])
            ->count();

        return [
            'status' => $outdatedStatuses > 0 ? 'warning' : 'ok',
            'message' => $outdatedStatuses > 0
                ? "{$outdatedStatuses} training statuses may need updating"
                : 'All training statuses are current'
        ];
    }

    private function checkDataIntegrity()
    {
        $orphanedRecords = TrainingRecord::whereNotExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('employees')
                  ->whereRaw('employees.id = training_records.employee_id');
        })->count();

        return [
            'status' => $orphanedRecords > 0 ? 'warning' : 'ok',
            'message' => $orphanedRecords > 0
                ? "{$orphanedRecords} orphaned training records found"
                : 'Data integrity is good'
        ];
    }
}
