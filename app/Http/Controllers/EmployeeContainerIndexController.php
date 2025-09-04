<?php
// app/Http/Controllers/EmployeeContainerIndexController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\CertificateType;
use App\Models\EmployeeCertificate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeContainerIndexController extends Controller
{
    /**
     * Display employee containers index (Digital folder system)
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department'])
            ->withCount([
                'employeeCertificates as total_certificates',
                'employeeCertificates as active_certificates' => function($q) {
                    $q->where('status', 'active');
                },
                'employeeCertificates as expired_certificates' => function($q) {
                    $q->where('status', 'expired');
                },
                'employeeCertificates as expiring_soon_certificates' => function($q) {
                    $q->where('status', 'expiring_soon');
                }
            ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Container status filter
        if ($request->filled('container_status')) {
            switch ($request->container_status) {
                case 'with_certificates':
                    $query->has('employeeCertificates');
                    break;
                case 'no_certificates':
                    $query->doesntHave('employeeCertificates');
                    break;
                case 'expired_certificates':
                    $query->whereHas('employeeCertificates', function($q) {
                        $q->where('status', 'expired');
                    });
                    break;
                case 'expiring_soon':
                    $query->whereHas('employeeCertificates', function($q) {
                        $q->where('status', 'expiring_soon');
                    });
                    break;
            }
        }

        $employees = $query->orderBy('name')->paginate(15);

        // Add container statistics to each employee
        $employees->getCollection()->transform(function ($employee) {
            $employee->container_stats = [
                'total' => $employee->total_certificates,
                'active' => $employee->active_certificates,
                'expired' => $employee->expired_certificates,
                'expiring_soon' => $employee->expiring_soon_certificates,
                'has_background_check' => !is_null($employee->background_check_date),
                'background_check_status' => $employee->background_check_status
            ];

            return $employee;
        });

        // Get overall statistics
        $overallStats = [
            'total_employees' => Employee::count(),
            'employees_with_certificates' => Employee::has('employeeCertificates')->count(),
            'employees_with_background_checks' => Employee::whereNotNull('background_check_date')->count(),
            'total_certificates' => EmployeeCertificate::count(),
            'certificates_by_status' => [
                'active' => EmployeeCertificate::where('status', 'active')->count(),
                'expired' => EmployeeCertificate::where('status', 'expired')->count(),
                'expiring_soon' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
                'pending' => EmployeeCertificate::where('status', 'pending')->count(),
            ]
        ];

        return Inertia::render('EmployeeContainers/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'certificateTypes' => CertificateType::active()->get(['id', 'name', 'code']),
            'overallStats' => $overallStats,
            'filters' => $request->only(['search', 'department', 'status', 'container_status'])
        ]);
    }

    /**
     * Get container statistics for dashboard
     */
    public function getStatistics()
    {
        return response()->json([
            'employee_containers' => [
                'total' => Employee::count(),
                'with_certificates' => Employee::has('employeeCertificates')->count(),
                'with_background_checks' => Employee::whereNotNull('background_check_date')->count(),
                'without_certificates' => Employee::doesntHave('employeeCertificates')->count()
            ],
            'certificate_distribution' => [
                'active' => EmployeeCertificate::where('status', 'active')->count(),
                'expired' => EmployeeCertificate::where('status', 'expired')->count(),
                'expiring_soon' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
                'pending' => EmployeeCertificate::where('status', 'pending')->count()
            ],
            'background_checks' => [
                'completed' => Employee::where('background_check_status', 'cleared')->count(),
                'pending' => Employee::where('background_check_status', 'in_progress')->count(),
                'expired' => Employee::where('background_check_status', 'expired')->count(),
                'not_started' => Employee::where('background_check_status', 'not_started')->count()
            ],
            'top_certificate_types' => CertificateType::withCount('employeeCertificates')
                ->orderBy('employee_certificates_count', 'desc')
                ->take(5)
                ->get(['name', 'employee_certificates_count']),
            'compliance_summary' => [
                'mandatory_certificates_compliance' => $this->getMandatoryComplianceRate(),
                'background_check_compliance' => $this->getBackgroundCheckComplianceRate()
            ]
        ]);
    }

    /**
     * Calculate mandatory certificate compliance rate
     */
    private function getMandatoryComplianceRate()
    {
        $totalEmployees = Employee::count();
        if ($totalEmployees === 0) return 100;

        $mandatoryTypes = CertificateType::where('is_mandatory', true)->pluck('id');
        if ($mandatoryTypes->isEmpty()) return 100;

        $compliantEmployees = 0;

        Employee::chunk(50, function ($employees) use ($mandatoryTypes, &$compliantEmployees) {
            foreach ($employees as $employee) {
                $isCompliant = true;

                foreach ($mandatoryTypes as $typeId) {
                    $hasValidCert = $employee->employeeCertificates()
                        ->where('certificate_type_id', $typeId)
                        ->whereIn('status', ['active', 'expiring_soon'])
                        ->exists();

                    if (!$hasValidCert) {
                        $isCompliant = false;
                        break;
                    }
                }

                if ($isCompliant) {
                    $compliantEmployees++;
                }
            }
        });

        return round(($compliantEmployees / $totalEmployees) * 100, 2);
    }

    /**
     * Calculate background check compliance rate
     */
    private function getBackgroundCheckComplianceRate()
    {
        $totalEmployees = Employee::count();
        if ($totalEmployees === 0) return 100;

        $compliantEmployees = Employee::where('background_check_status', 'cleared')->count();

        return round(($compliantEmployees / $totalEmployees) * 100, 2);
    }
}
