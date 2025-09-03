<?php
// app/Http/Controllers/EmployeeController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with search and filters
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department']);

        // Search functionality - NIP, Name, Position
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Order by name for better UX
        $query->orderBy('name', 'asc');

        $employees = $query->paginate(15)->appends($request->all());

        // Get departments for filter dropdown
        $departments = Department::select('id', 'name', 'code')
                                ->orderBy('name')
                                ->get();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => [
                'search' => $request->search,
                'department' => $request->department,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        $departments = Department::select('id', 'name', 'code')
                                ->orderBy('name')
                                ->get();

        return Inertia::render('Employees/Create', [
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created employee in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'employee_id'),
            ],
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ], [
            'employee_id.required' => 'NIP wajib diisi',
            'employee_id.unique' => 'NIP sudah terdaftar dalam sistem',
            'employee_id.max' => 'NIP maksimal 20 karakter',
            'name.required' => 'Nama karyawan wajib diisi',
            'name.max' => 'Nama maksimal 255 karakter',
            'position.required' => 'Jabatan wajib diisi',
            'position.max' => 'Jabatan maksimal 100 karakter',
            'department_id.exists' => 'Departemen tidak valid',
            'status.required' => 'Status karyawan wajib dipilih',
            'status.in' => 'Status harus Aktif atau Tidak Aktif',
        ]);

        try {
            $employee = Employee::create($validated);

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Data karyawan berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan data karyawan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load(['department']);

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit(Employee $employee)
    {
        $employee->load(['department']);

        $departments = Department::select('id', 'name', 'code')
                                ->orderBy('name')
                                ->get();

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => $departments,
        ]);
    }

    /**
     * Update the specified employee in storage
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'employee_id')->ignore($employee->id),
            ],
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ], [
            'employee_id.required' => 'NIP wajib diisi',
            'employee_id.unique' => 'NIP sudah terdaftar dalam sistem',
            'employee_id.max' => 'NIP maksimal 20 karakter',
            'name.required' => 'Nama karyawan wajib diisi',
            'name.max' => 'Nama maksimal 255 karakter',
            'position.required' => 'Jabatan wajib diisi',
            'position.max' => 'Jabatan maksimal 100 karakter',
            'department_id.exists' => 'Departemen tidak valid',
            'status.required' => 'Status karyawan wajib dipilih',
            'status.in' => 'Status harus Aktif atau Tidak Aktif',
        ]);

        try {
            $employee->update($validated);

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Data karyawan berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data karyawan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified employee from storage
     */
    public function destroy(Employee $employee)
    {
        try {
            $employeeName = $employee->name;
            $employee->delete();

            return redirect()
                ->route('employees.index')
                ->with('success', "Data karyawan {$employeeName} berhasil dihapus");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus data karyawan: ' . $e->getMessage());
        }
    }

    /**
     * Get employee statistics for dashboard
     */
    public function getStatistics()
    {
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'inactive' => Employee::where('status', 'inactive')->count(),
            'complete_data' => Employee::whereNotNull('position')
                                     ->whereNotNull('department_id')
                                     ->count(),
        ];

        $stats['completion_rate'] = $stats['total'] > 0
            ? round(($stats['complete_data'] / $stats['total']) * 100, 1)
            : 0;

        return response()->json($stats);
    }

    /**
     * Export employees data
     */
    public function export(Request $request)
    {
        $query = Employee::with(['department']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Export berhasil',
            'data' => $employees->map(function ($employee) {
                return [
                    'NIP' => $employee->employee_id,
                    'Nama' => $employee->name,
                    'Jabatan' => $employee->position ?? '',
                    'Departemen' => $employee->department?->name ?? '',
                    'Status' => $employee->status === 'active' ? 'Aktif' : 'Tidak Aktif',
                    'Data Lengkap' => (!empty($employee->position) && !empty($employee->department_id)) ? 'Ya' : 'Tidak',
                    'Dibuat' => $employee->created_at?->format('d/m/Y H:i'),
                    'Diupdate' => $employee->updated_at?->format('d/m/Y H:i'),
                ];
            }),
            'total' => $employees->count(),
            'exported_at' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    /**
     * Search employees for autocomplete/API
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'limit' => 'integer|min:1|max:50',
        ]);

        $searchTerm = $request->get('q');
        $limit = $request->get('limit', 10);

        $employees = Employee::search($searchTerm)
                            ->with(['department'])
                            ->limit($limit)
                            ->get()
                            ->map(function ($employee) {
                                return [
                                    'id' => $employee->id,
                                    'text' => $employee->name,
                                    'subtitle' => $employee->employee_id . ($employee->position ? " - {$employee->position}" : ''),
                                    'department' => $employee->department?->name ?? '',
                                    'status' => $employee->status,
                                ];
                            });

        return response()->json([
            'success' => true,
            'data' => $employees,
            'total' => $employees->count(),
        ]);
    }

    /**
     * Bulk operations on employees
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $action = $request->get('action');
        $employeeIds = $request->get('employee_ids');

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'activate':
                    Employee::whereIn('id', $employeeIds)->update(['status' => 'active']);
                    $message = 'Karyawan berhasil diaktifkan';
                    break;

                case 'deactivate':
                    Employee::whereIn('id', $employeeIds)->update(['status' => 'inactive']);
                    $message = 'Karyawan berhasil dinonaktifkan';
                    break;

                case 'delete':
                    Employee::whereIn('id', $employeeIds)->delete();
                    $message = 'Karyawan berhasil dihapus';
                    break;

                default:
                    throw new \InvalidArgumentException('Action tidak valid');
            }

            DB::commit();

            return redirect()
                ->route('employees.index')
                ->with('success', $message . ' (' . count($employeeIds) . ' karyawan)');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()
                ->back()
                ->with('error', 'Gagal melakukan bulk action: ' . $e->getMessage());
        }
    }
}
