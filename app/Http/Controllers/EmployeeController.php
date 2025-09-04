<?php
// app/Http/Controllers/EmployeeController.php - Simple Show Method

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display the specified employee
     *
     * @param Employee $employee
     * @return \Inertia\Response
     */
    public function show(Employee $employee)
    {
        try {
            // Load basic relationships if they exist
            $relations = ['department'];

            // Only load relationships that exist
            if (method_exists($employee, 'employeeCertificates')) {
                $relations[] = 'employeeCertificates';
            }

            $employee->load($relations);

            return Inertia::render('Employees/Show', [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'nip' => $employee->nip ?? $employee->employee_id,
                    'position' => $employee->position,
                    'status' => $employee->status ?? 'active',
                    'hire_date' => $employee->hire_date?->format('Y-m-d'),
                    'department' => $employee->department ? [
                        'id' => $employee->department->id,
                        'name' => $employee->department->name,
                        'code' => $employee->department->code ?? null,
                    ] : null,
                    'certificates_count' => $employee->employeeCertificates?->count() ?? 0,
                    'background_check_status' => $employee->background_check_status ?? 'not_started',
                    'background_check_date' => $employee->background_check_date?->format('Y-m-d'),
                    'created_at' => $employee->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $employee->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            // Fallback untuk debugging
            return Inertia::render('Employees/Show', [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name ?? 'Unknown',
                    'nip' => $employee->nip ?? $employee->employee_id ?? 'N/A',
                    'position' => $employee->position ?? 'Unknown',
                    'status' => $employee->status ?? 'active',
                    'hire_date' => null,
                    'department' => null,
                    'certificates_count' => 0,
                    'background_check_status' => 'not_started',
                    'background_check_date' => null,
                    'created_at' => $employee->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $employee->updated_at->format('Y-m-d H:i:s'),
                ],
                'error' => 'Some data could not be loaded: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        try {
            $query = Employee::query();

            // Add search if provided
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('nip', 'LIKE', "%{$search}%")
                      ->orWhere('employee_id', 'LIKE', "%{$search}%");
                });
            }

            // Add status filter if provided
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Load relationships if they exist
            $relations = [];
            if (method_exists(Employee::class, 'department')) {
                $relations[] = 'department';
            }

            if (!empty($relations)) {
                $query->with($relations);
            }

            $employees = $query->latest()->paginate(15);

            return Inertia::render('Employees/Index', [
                'employees' => $employees,
                'filters' => $request->only(['search', 'status']),
                'departments' => $this->getDepartments(),
            ]);

        } catch (\Exception $e) {
            return Inertia::render('Employees/Index', [
                'employees' => ['data' => [], 'total' => 0],
                'filters' => [],
                'departments' => [],
                'error' => 'Could not load employees: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        return Inertia::render('Employees/Create', [
            'departments' => $this->getDepartments(),
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|unique:employees,nip',
            'employee_id' => 'nullable|string|unique:employees,employee_id',
            'position' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        try {
            $employee = Employee::create([
                'name' => $request->name,
                'nip' => $request->nip ?? $request->employee_id,
                'employee_id' => $request->employee_id ?? $request->nip,
                'position' => $request->position,
                'department_id' => $request->department_id,
                'status' => $request->status ?? 'active',
                'hire_date' => $request->hire_date ? now()->parse($request->hire_date) : now(),
            ]);

            return redirect()->route('employees.show', $employee)
                           ->with('success', 'Employee created successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'error' => 'Could not create employee: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for editing the employee
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => $this->getDepartments(),
        ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|unique:employees,nip,' . $employee->id,
            'employee_id' => 'nullable|string|unique:employees,employee_id,' . $employee->id,
            'position' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        try {
            $employee->update([
                'name' => $request->name,
                'nip' => $request->nip ?? $request->employee_id,
                'employee_id' => $request->employee_id ?? $request->nip,
                'position' => $request->position,
                'department_id' => $request->department_id,
                'status' => $request->status ?? 'active',
            ]);

            return redirect()->route('employees.show', $employee)
                           ->with('success', 'Employee updated successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'error' => 'Could not update employee: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();

            return redirect()->route('employees.index')
                           ->with('success', 'Employee deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Could not delete employee: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $employees = Employee::whereIn('id', $request->employee_ids);

            switch ($request->action) {
                case 'delete':
                    $employees->delete();
                    $message = 'Selected employees deleted successfully.';
                    break;
                case 'activate':
                    $employees->update(['status' => 'active']);
                    $message = 'Selected employees activated successfully.';
                    break;
                case 'deactivate':
                    $employees->update(['status' => 'inactive']);
                    $message = 'Selected employees deactivated successfully.';
                    break;
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Could not perform bulk action: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export employees to Excel
     */
    public function export(Request $request)
    {
        try {
            // Simple CSV export
            $employees = Employee::all();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="employees_' . date('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($employees) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Name', 'NIP', 'Position', 'Status', 'Created At']);

                foreach ($employees as $employee) {
                    fputcsv($handle, [
                        $employee->id,
                        $employee->name,
                        $employee->nip ?? $employee->employee_id,
                        $employee->position,
                        $employee->status,
                        $employee->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Could not export employees: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search employees
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');

            if (empty($query)) {
                return response()->json([]);
            }

            $employees = Employee::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('nip', 'LIKE', "%{$query}%")
                  ->orWhere('employee_id', 'LIKE', "%{$query}%");
            })
            ->select(['id', 'name', 'nip', 'employee_id', 'position'])
            ->limit(10)
            ->get();

            return response()->json($employees);

        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Get departments for dropdowns
     */
    private function getDepartments()
    {
        try {
            if (class_exists('App\Models\Department')) {
                return \App\Models\Department::orderBy('name')->get(['id', 'name', 'code']);
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
