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
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load(['department', 'trainingRecords.trainingType']);

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'trainingStats' => [
                'total' => $employee->trainingRecords->count(),
                'active' => $employee->trainingRecords->where('status', 'active')->count(),
                'expiring_soon' => $employee->trainingRecords->where('status', 'expiring_soon')->count(),
                'expired' => $employee->trainingRecords->where('status', 'expired')->count(),
            ]
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
        $fileName = 'employees_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new EmployeesExport, $fileName);
    }

    /**
     * Bulk operations for employees
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        switch ($request->action) {
            case 'activate':
                Employee::whereIn('id', $request->employee_ids)
                    ->update(['status' => 'active']);
                $message = 'Employees berhasil diaktifkan.';
                break;

            case 'deactivate':
                Employee::whereIn('id', $request->employee_ids)
                    ->update(['status' => 'inactive']);
                $message = 'Employees berhasil dinonaktifkan.';
                break;

            case 'delete':
                // Check for training records before deleting
                $employeesWithTraining = Employee::whereIn('id', $request->employee_ids)
                    ->whereHas('trainingRecords')
                    ->count();

                if ($employeesWithTraining > 0) {
                    return redirect()->route('employees.index')
                        ->with('error', 'Tidak dapat menghapus employees yang memiliki training records.');
                }

                Employee::whereIn('id', $request->employee_ids)->delete();
                $message = 'Employees berhasil dihapus.';
                break;
        }

        return redirect()->route('employees.index')
            ->with('success', $message);
    }
}
