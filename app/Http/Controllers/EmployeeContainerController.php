<?php
// app/Http/Controllers/EmployeeContainerController.php - Fixed Index Method

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeContainerController extends Controller
{
    /**
     * Display employees index page - Container view
     * FIXED: Render to correct Inertia page
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
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
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
                'background_check_status' => $employee->background_check_status ?? 'not_checked'
            ];

            return $employee;
        });

        // Get overall statistics
        $overallStats = [
            'total_employees' => Employee::count(),
            'employees_with_certificates' => Employee::has('employeeCertificates')->count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'inactive_employees' => Employee::where('status', 'inactive')->count(),
            'employees_with_background_check' => Employee::whereNotNull('background_check_date')->count(),
        ];

        // ✅ FIXED: Render to Employees/Index.jsx (not EmployeeContainer/Index.jsx)
        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'statistics' => $overallStats,
            'filters' => $request->only(['search', 'department', 'status', 'container_status', 'sort', 'direction'])
        ]);
    }

    /**
     * Show specific employee container
     */
    public function show(Employee $employee)
{
    // Load employee with relationships
    $employee->load([
        'department',
        'employeeCertificates' => function($query) {
            $query->with('certificateType')
                  ->orderBy('created_at', 'desc');
        }
    ]);

    // Calculate container statistics
    $statistics = [
        'total' => $employee->employeeCertificates->count(),
        'active' => $employee->employeeCertificates->where('status', 'active')->count(),
        'expired' => $employee->employeeCertificates->where('status', 'expired')->count(),
        'expiring_soon' => $employee->employeeCertificates->where('status', 'expiring_soon')->count(),
        'has_background_check' => !is_null($employee->background_check_date),
    ];

    // Profile data
    $profile = [
        'position' => $employee->position ?? 'No Position',
        'department' => $employee->department->name ?? 'No Department',
        'hire_date' => $employee->hire_date ? $employee->hire_date->format('Y-m-d') : null,
        'status' => $employee->status,
    ];

    // ✅ FIXED: Return the correct component path
    return Inertia::render('Employees/Container', [
        'employee' => $employee,
        'statistics' => $statistics,
        'profile' => $profile,
    ]);
}
    // Add other container methods as needed...
    public function storeCertificate(Request $request, Employee $employee)
    {
        // Implementation for storing certificates
    }

    public function uploadBackgroundCheckFiles(Request $request, Employee $employee)
    {
        // Implementation for background check upload
    }

    // Other methods...
}
