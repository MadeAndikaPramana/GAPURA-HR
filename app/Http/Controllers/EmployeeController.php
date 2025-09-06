<?php
// app/Http/Controllers/EmployeeController.php - Fixed Index Method

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display employees index page - CORRECTED
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

        // Sort
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $employees = $query->paginate(15);

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
            'latest_additions' => Employee::latest()->take(5)->get(['id', 'name', 'employee_id', 'created_at'])
        ];

        // FIXED: Return the correct Inertia render path for Employees
        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'statistics' => $overallStats,
            'filters' => $request->only(['search', 'department', 'status', 'container_status', 'sort', 'direction'])
        ]);
    }

    /**
     * Show specific employee - redirect to container view if needed
     */
    public function show(Employee $employee)
    {
        // For now, redirect to container view
        return redirect()->route('employee-containers.show', $employee);
    }

    /**
     * Show create employee form
     */
    public function create()
    {
        return Inertia::render('Employees/Create', [
            'departments' => Department::all(['id', 'name'])
        ]);
    }

    /**
     * Store new employee
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'status' => 'required|in:active,inactive'
        ]);

        $employee = Employee::create($request->all());

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->name} created successfully!");
    }

    /**
     * Show edit employee form
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('Employees/Edit', [
            'employee' => $employee->load('department'),
            'departments' => Department::all(['id', 'name'])
        ]);
    }

    /**
     * Update employee
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees,employee_id,' . $employee->id,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'status' => 'required|in:active,inactive'
        ]);

        $employee->update($request->all());

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->name} updated successfully.");
    }

    /**
     * Delete employee
     */
    public function destroy(Employee $employee)
    {
        // Check if employee has certificates or files
        $certificateCount = $employee->employeeCertificates()->count();
        $hasFiles = !empty($employee->background_check_files);

        if ($certificateCount > 0 || $hasFiles) {
            return back()->with('error',
                "Cannot delete {$employee->name}. Employee has {$certificateCount} certificates or uploaded files. Please remove them first."
            );
        }

        $name = $employee->name;
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "Employee {$name} deleted successfully.");
    }

    /**
     * Export employees to Excel
     */
    public function export()
    {
        return Excel::download(new EmployeesExport, 'employees.xlsx');
    }

    /**
     * Search employees (AJAX endpoint)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $employees = Employee::with('department')
            ->where('name', 'like', "%{$query}%")
            ->orWhere('employee_id', 'like', "%{$query}%")
            ->orWhere('nip', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'employee_id', 'nip', 'department_id']);

        return response()->json($employees);
    }

    /**
     * Bulk actions on employees
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,export',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:employees,id'
        ]);

        $employees = Employee::whereIn('id', $request->selected);
        $count = count($request->selected);

        switch ($request->action) {
            case 'delete':
                $employees->delete();
                return back()->with('success', "{$count} employees deleted successfully.");

            case 'activate':
                $employees->update(['status' => 'active']);
                return back()->with('success', "{$count} employees activated successfully.");

            case 'deactivate':
                $employees->update(['status' => 'inactive']);
                return back()->with('success', "{$count} employees deactivated successfully.");

            case 'export':
                return Excel::download(new EmployeesExport($request->selected), 'selected_employees.xlsx');
        }

        return back();
    }
}
