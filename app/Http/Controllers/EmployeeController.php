<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with enhanced features
     */
    public function index(Request $request)
    {
        $query = Employee::with('department');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('employee_id', 'like', '%' . $request->search . '%')
                  ->orWhere('position', 'like', '%' . $request->search . '%');
            });
        }

        // Department filter
        if ($request->has('department') && $request->department) {
            $query->where('department_id', $request->department);
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $employees = $query->paginate(15)->withQueryString();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'filters' => $request->only(['search', 'department', 'status']),
            'stats' => [
                'total' => Employee::count(),
                'active' => Employee::where('status', 'active')->count(),
                'by_department' => Employee::with('department')
                    ->selectRaw('department_id, count(*) as count')
                    ->groupBy('department_id')
                    ->get()
            ]
        ]);
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        return Inertia::render('Employees/Create', [
            'departments' => Department::all(['id', 'name'])
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees',
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'background_check_date' => 'nullable|date',
            'background_check_notes' => 'nullable|string'
        ]);

        Employee::create($request->all());

        return redirect()->route('employees.index')
            ->with('success', 'Employee berhasil ditambahkan.');
    }

    /**
     * Display the specified employee with training records
     */
    public function show(Employee $employee)
    {
        // Load employee with department and training records
        $employee->load([
            'department',
            'trainingRecords' => function($query) {
                $query->with('trainingType')->orderBy('expiry_date', 'desc');
            }
        ]);

        // Calculate training statistics
        $trainingStats = [
            'total' => $employee->trainingRecords->count(),
            'active' => $employee->trainingRecords->where('status', 'active')->count(),
            'expiring_soon' => $employee->trainingRecords->where('status', 'expiring_soon')->count(),
            'expired' => $employee->trainingRecords->where('status', 'expired')->count(),
        ];

        // Calculate compliance rate
        $trainingStats['compliance_rate'] = $trainingStats['total'] > 0
            ? round(($trainingStats['active'] / $trainingStats['total']) * 100, 2)
            : 0;

        // Get recent activities (last 5 training records)
        $recentActivities = $employee->trainingRecords()
            ->with('trainingType')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'trainingStats' => $trainingStats,
            'recentActivities' => $recentActivities
        ]);
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => Department::all(['id', 'name'])
        ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees,employee_id,' . $employee->id,
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'background_check_date' => 'nullable|date',
            'background_check_notes' => 'nullable|string'
        ]);

        $employee->update($request->all());

        return redirect()->route('employees.index')
            ->with('success', 'Employee berhasil diupdate.');
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        // Check if employee has training records
        if ($employee->trainingRecords()->count() > 0) {
            return redirect()->route('employees.index')
                ->with('error', 'Tidak dapat menghapus employee yang memiliki training records.');
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee berhasil dihapus.');
    }

    /**
     * Import employees from Excel
     */
    public function handleImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            Excel::import(new EmployeesImport, $request->file('file'));

            return redirect()->route('employees.index')
                ->with('success', 'Data employees berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    /**
     * Export employees to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'department', 'status']);

        return Excel::download(new EmployeesExport($filters), 'employees_export.xlsx');
    }

    /**
     * Handle bulk actions on employees
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,export',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        $employees = Employee::whereIn('id', $request->employee_ids);

        switch ($request->action) {
            case 'activate':
                $employees->update(['status' => 'active']);
                $message = 'Selected employees have been activated.';
                break;

            case 'deactivate':
                $employees->update(['status' => 'inactive']);
                $message = 'Selected employees have been deactivated.';
                break;

            case 'delete':
                // Check if any employee has training records
                $employeesWithRecords = $employees->has('trainingRecords')->count();
                if ($employeesWithRecords > 0) {
                    return redirect()->back()
                        ->with('error', 'Cannot delete employees who have training records.');
                }

                $employees->delete();
                $message = 'Selected employees have been deleted.';
                break;

            case 'export':
                return Excel::download(
                    new EmployeesExport(['employee_ids' => $request->employee_ids]),
                    'selected_employees.xlsx'
                );
        }

        return redirect()->route('employees.index')
            ->with('success', $message);
    }

    /**
     * Get employee statistics for dashboard
     */
    public function getStatistics()
    {
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'inactive_employees' => Employee::where('status', 'inactive')->count(),
            'by_department' => Employee::with('department')
                ->selectRaw('department_id, departments.name as department_name, count(*) as count')
                ->join('departments', 'employees.department_id', '=', 'departments.id')
                ->groupBy('department_id', 'departments.name')
                ->get(),
            'recent_additions' => Employee::with('department')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'employees_with_trainings' => Employee::has('trainingRecords')->count(),
            'employees_without_trainings' => Employee::doesntHave('trainingRecords')->count()
        ];

        return response()->json($stats);
    }

    /**
     * Search employees for autocomplete/select
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $employees = Employee::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('employee_id', 'like', "%{$query}%");
            })
            ->with('department')
            ->limit(10)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'department' => $employee->department?->name,
                    'position' => $employee->position,
                    'status' => $employee->status
                ];
            });

        return response()->json($employees);
    }

    /**
     * Get employee training compliance summary
     */
    public function getComplianceSummary(Employee $employee)
    {
        $trainingRecords = $employee->trainingRecords()->with('trainingType')->get();

        $summary = [
            'employee' => $employee,
            'total_trainings' => $trainingRecords->count(),
            'active_trainings' => $trainingRecords->where('status', 'active')->count(),
            'expiring_trainings' => $trainingRecords->where('status', 'expiring_soon')->count(),
            'expired_trainings' => $trainingRecords->where('status', 'expired')->count(),
            'by_category' => $trainingRecords->groupBy('trainingType.category')->map(function($records, $category) {
                return [
                    'category' => $category,
                    'total' => $records->count(),
                    'active' => $records->where('status', 'active')->count(),
                    'expiring' => $records->where('status', 'expiring_soon')->count(),
                    'expired' => $records->where('status', 'expired')->count(),
                ];
            })
        ];

        return response()->json($summary);
    }

    /**
     * Get employees requiring training renewal
     */
    public function getEmployeesRequiringRenewal($days = 30)
    {
        $employees = Employee::with(['trainingRecords' => function($query) use ($days) {
            $query->where('expiry_date', '<=', now()->addDays($days))
                  ->where('expiry_date', '>=', now())
                  ->with('trainingType');
        }])
        ->has('trainingRecords')
        ->get()
        ->filter(function($employee) {
            return $employee->trainingRecords->count() > 0;
        });

        return response()->json($employees);
    }
}
