<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ComplianceReportExport;
use App\Exports\ExpiryReportExport;
use App\Exports\BackgroundCheckReportExport;

class ReportController extends Controller
{
    /**
     * Display compliance report
     */
    public function complianceReport(Request $request)
    {
        $filters = $request->only(['department_id', 'certificate_type_id', 'status']);

        // Get compliance data
        $employeesQuery = Employee::with(['department', 'employeeCertificates.certificateType']);

        if ($filters['department_id']) {
            $employeesQuery->where('department_id', $filters['department_id']);
        }

        $employees = $employeesQuery->get();

        // Calculate compliance metrics
        $complianceData = $this->calculateComplianceMetrics($employees, $filters);

        return Inertia::render('Reports/Compliance', [
            'complianceData' => $complianceData,
            'filters' => $filters,
            'departments' => Department::all(['id', 'name']),
            'certificateTypes' => CertificateType::all(['id', 'name']),
        ]);
    }

    /**
     * Display certificate expiry report
     */
    public function expiryReport(Request $request)
    {
        $timeframe = $request->get('timeframe', '30'); // Default 30 days
        $department_id = $request->get('department_id');
        $certificate_type_id = $request->get('certificate_type_id');

        $query = EmployeeCertificate::with(['employee.department', 'certificateType'])
            ->where('status', '!=', 'expired');

        // Filter by timeframe
        if ($timeframe === 'expired') {
            $query->where('status', 'expired');
        } else {
            $days = (int) $timeframe;
            $query->where('expiry_date', '<=', Carbon::now()->addDays($days));
        }

        // Filter by department
        if ($department_id) {
            $query->whereHas('employee', function ($q) use ($department_id) {
                $q->where('department_id', $department_id);
            });
        }

        // Filter by certificate type
        if ($certificate_type_id) {
            $query->where('certificate_type_id', $certificate_type_id);
        }

        $expiringCertificates = $query->orderBy('expiry_date', 'asc')->paginate(50);

        // Add urgency level to each certificate
        $expiringCertificates->getCollection()->transform(function ($certificate) {
            $daysUntilExpiry = Carbon::now()->diffInDays($certificate->expiry_date, false);
            $certificate->days_until_expiry = $daysUntilExpiry;
            $certificate->urgency_level = $this->getUrgencyLevel($daysUntilExpiry);
            return $certificate;
        });

        // Summary statistics
        $summary = [
            'total_expiring' => $expiringCertificates->total(),
            'critical' => $expiringCertificates->getCollection()->where('urgency_level', 'critical')->count(),
            'warning' => $expiringCertificates->getCollection()->where('urgency_level', 'warning')->count(),
            'expired' => $expiringCertificates->getCollection()->where('urgency_level', 'expired')->count(),
        ];

        return Inertia::render('Reports/Expiry', [
            'expiringCertificates' => $expiringCertificates,
            'summary' => $summary,
            'filters' => compact('timeframe', 'department_id', 'certificate_type_id'),
            'departments' => Department::all(['id', 'name']),
            'certificateTypes' => CertificateType::all(['id', 'name']),
        ]);
    }

    /**
     * Display background check report
     */
    public function backgroundCheckReport(Request $request)
    {
        $status_filter = $request->get('status');
        $department_id = $request->get('department_id');
        $overdue_only = $request->boolean('overdue_only');

        $query = Employee::with(['department']);

        // Filter by status
        if ($status_filter) {
            $query->where('background_check_status', $status_filter);
        }

        // Filter by department
        if ($department_id) {
            $query->where('department_id', $department_id);
        }

        // Filter overdue background checks (>12 months or never done)
        if ($overdue_only) {
            $query->where(function ($q) {
                $q->where('background_check_date', '<', Carbon::now()->subMonths(12))
                  ->orWhere(function ($subq) {
                      $subq->whereNull('background_check_date')
                           ->where('hire_date', '<', Carbon::now()->subMonths(1));
                  });
            });
        }

        $employees = $query->paginate(50);

        // Add additional computed fields
        $employees->getCollection()->transform(function ($employee) {
            $employee->background_check_overdue = $this->isBackgroundCheckOverdue($employee);
            $employee->days_since_last_check = $employee->background_check_date ?
                Carbon::now()->diffInDays($employee->background_check_date) : null;
            return $employee;
        });

        // Summary statistics
        $summary = [
            'total_employees' => Employee::count(),
            'cleared' => Employee::where('background_check_status', 'cleared')->count(),
            'pending_review' => Employee::where('background_check_status', 'pending_review')->count(),
            'in_progress' => Employee::where('background_check_status', 'in_progress')->count(),
            'not_started' => Employee::where('background_check_status', 'not_started')
                ->orWhereNull('background_check_status')->count(),
            'overdue' => Employee::where('background_check_date', '<', Carbon::now()->subMonths(12))
                ->orWhere(function ($query) {
                    $query->whereNull('background_check_date')
                          ->where('hire_date', '<', Carbon::now()->subMonths(1));
                })->count(),
        ];

        return Inertia::render('Reports/BackgroundCheck', [
            'employees' => $employees,
            'summary' => $summary,
            'filters' => compact('status_filter', 'department_id', 'overdue_only'),
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Display certificate overview report
     */
    public function certificateOverview(Request $request)
    {
        // Certificate statistics by type
        $certificateStats = CertificateType::withCount([
            'employeeCertificates',
            'employeeCertificates as active_count' => function ($query) {
                $query->where('status', 'active');
            },
            'employeeCertificates as expired_count' => function ($query) {
                $query->where('status', 'expired');
            },
            'employeeCertificates as expiring_soon_count' => function ($query) {
                $query->where('status', 'expiring_soon');
            }
        ])->get();

        // Department certificate compliance
        $departmentCompliance = Department::withCount([
            'employees',
            'employees as employees_with_certificates' => function ($query) {
                $query->has('employeeCertificates');
            }
        ])->get()->map(function ($department) {
            $compliance_rate = $department->employees_count > 0 ?
                round(($department->employees_with_certificates / $department->employees_count) * 100, 1) : 0;

            return [
                'department_name' => $department->name,
                'total_employees' => $department->employees_count,
                'employees_with_certificates' => $department->employees_with_certificates,
                'compliance_rate' => $compliance_rate,
            ];
        });

        // Recent certificate additions (last 30 days)
        $recentCertificates = EmployeeCertificate::with(['employee', 'certificateType'])
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->latest()
            ->limit(20)
            ->get();

        // Certificate expiry timeline (next 90 days)
        $expiryTimeline = [];
        for ($i = 0; $i < 12; $i++) { // 12 weeks
            $weekStart = Carbon::now()->addWeeks($i);
            $weekEnd = $weekStart->copy()->addWeek();

            $expiring = EmployeeCertificate::whereBetween('expiry_date', [$weekStart, $weekEnd])->count();

            $expiryTimeline[] = [
                'week' => $weekStart->format('M d'),
                'expiring_count' => $expiring,
            ];
        }

        return Inertia::render('Reports/CertificateOverview', [
            'certificateStats' => $certificateStats,
            'departmentCompliance' => $departmentCompliance,
            'recentCertificates' => $recentCertificates,
            'expiryTimeline' => $expiryTimeline,
        ]);
    }

    /**
     * Generate daily compliance report (for cron job)
     */
    public function generateDailyComplianceReport()
    {
        $reportData = [
            'date' => Carbon::now()->toDateString(),
            'total_employees' => Employee::count(),
            'employees_with_certificates' => Employee::has('employeeCertificates')->count(),
            'certificates_expiring_soon' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
            'overdue_background_checks' => $this->getOverdueBackgroundChecksCount(),
            'certificates_by_status' => [
                'active' => EmployeeCertificate::where('status', 'active')->count(),
                'expired' => EmployeeCertificate::where('status', 'expired')->count(),
                'expiring_soon' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
                'pending' => EmployeeCertificate::where('status', 'pending')->count(),
            ],
        ];

        // Store report or send via email
        // Implementation depends on requirements

        return response()->json(['message' => 'Daily compliance report generated successfully']);
    }

    /**
     * Export compliance data to Excel
     */
    public function exportComplianceData(Request $request)
    {
        $filters = $request->only(['department_id', 'certificate_type_id', 'status']);

        return Excel::download(
            new ComplianceReportExport($filters),
            'compliance_report_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export expiry report to Excel
     */
    public function exportExpiryReport(Request $request)
    {
        $filters = $request->only(['timeframe', 'department_id', 'certificate_type_id']);

        return Excel::download(
            new ExpiryReportExport($filters),
            'expiry_report_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export background check report to Excel
     */
    public function exportBackgroundCheckReport(Request $request)
    {
        $filters = $request->only(['status', 'department_id', 'overdue_only']);

        return Excel::download(
            new BackgroundCheckReportExport($filters),
            'background_check_report_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Calculate compliance metrics
     */
    private function calculateComplianceMetrics($employees, $filters = [])
    {
        $metrics = [
            'total_employees' => $employees->count(),
            'employees_with_certificates' => $employees->filter(function ($employee) {
                return $employee->employeeCertificates->count() > 0;
            })->count(),
            'employees_with_background_checks' => $employees->filter(function ($employee) {
                return $employee->background_check_status === 'cleared';
            })->count(),
            'certificate_compliance_issues' => [],
            'background_check_compliance_issues' => [],
        ];

        // Calculate compliance rate
        $metrics['certificate_compliance_rate'] = $metrics['total_employees'] > 0 ?
            round(($metrics['employees_with_certificates'] / $metrics['total_employees']) * 100, 1) : 0;

        $metrics['background_check_compliance_rate'] = $metrics['total_employees'] > 0 ?
            round(($metrics['employees_with_background_checks'] / $metrics['total_employees']) * 100, 1) : 0;

        // Identify compliance issues
        foreach ($employees as $employee) {
            // Certificate compliance issues
            if ($employee->employeeCertificates->count() === 0) {
                $metrics['certificate_compliance_issues'][] = [
                    'employee' => $employee,
                    'issue' => 'No certificates',
                ];
            }

            // Background check compliance issues
            if (!$employee->background_check_date || $employee->background_check_status !== 'cleared') {
                $metrics['background_check_compliance_issues'][] = [
                    'employee' => $employee,
                    'issue' => $this->getBackgroundCheckIssue($employee),
                ];
            }
        }

        return $metrics;
    }

    /**
     * Get urgency level based on days until expiry
     */
    private function getUrgencyLevel($days)
    {
        if ($days < 0) {
            return 'expired';
        } elseif ($days <= 7) {
            return 'critical';
        } elseif ($days <= 30) {
            return 'warning';
        } else {
            return 'normal';
        }
    }

    /**
     * Check if background check is overdue
     */
    private function isBackgroundCheckOverdue($employee)
    {
        if (!$employee->background_check_date) {
            return $employee->hire_date < Carbon::now()->subMonths(1);
        }

        return $employee->background_check_date < Carbon::now()->subMonths(12);
    }

    /**
     * Get background check issue description
     */
    private function getBackgroundCheckIssue($employee)
    {
        if (!$employee->background_check_date) {
            return 'No background check performed';
        }

        if ($employee->background_check_date < Carbon::now()->subMonths(12)) {
            return 'Background check overdue (>12 months)';
        }

        if ($employee->background_check_status !== 'cleared') {
            return 'Background check not cleared';
        }

        return 'Unknown issue';
    }

    /**
     * Get count of overdue background checks
     */
    private function getOverdueBackgroundChecksCount()
    {
        return Employee::where('background_check_date', '<', Carbon::now()->subMonths(12))
            ->orWhere(function ($query) {
                $query->whereNull('background_check_date')
                      ->where('hire_date', '<', Carbon::now()->subMonths(1));
            })
            ->count();
    }
}
