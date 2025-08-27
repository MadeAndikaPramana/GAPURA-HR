<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->get('department'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(15);

        // PERBAIKAN: Calculate statistics untuk frontend
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'inactive' => Employee::where('status', 'inactive')->count(),
            'by_department' => Department::withCount('employees')->get()->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'count' => $dept->employees_count
                ];
            }),
            'departments_count' => Department::count(),
            'training_records_count' => TrainingRecord::count(),
            'active_training_records' => TrainingRecord::where('status', 'active')->count(),
            'expired_training_records' => TrainingRecord::where('status', 'expired')->count(),
            'expiring_soon_records' => TrainingRecord::where('status', 'expiring_soon')->count(),
        ];

        // Calculate compliance rate
        $totalRecords = $stats['training_records_count'];
        $stats['compliance_rate'] = $totalRecords > 0
            ? round(($stats['active_training_records'] / $totalRecords) * 100, 1)
            : 0;

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'filters' => $request->only(['search', 'department', 'status']),
            'stats' => $stats // PERBAIKAN: Kirim stats ke frontend
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
     * Remove the specified employee - IMPROVED VERSION
     */
    public function destroy(Employee $employee)
    {
        // Log delete attempt
        Log::info('Employee delete attempt', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'user_id' => Auth::id(),
            'ip' => request()->ip()
        ]);

        try {
            // Start database transaction
            DB::beginTransaction();

            // Double-check: Load the employee with training records
            $employeeWithRecords = Employee::with('trainingRecords')->find($employee->id);

            if (!$employeeWithRecords) {
                Log::error('Employee not found during delete', ['employee_id' => $employee->id]);
                return redirect()->route('employees.index')
                    ->with('error', 'Employee tidak ditemukan.');
            }

            // Count training records
            $trainingRecordsCount = $employeeWithRecords->trainingRecords()->count();

            Log::info('Training records check', [
                'employee_id' => $employee->id,
                'training_records_count' => $trainingRecordsCount
            ]);

            // Check if employee has training records
            if ($trainingRecordsCount > 0) {
                Log::warning('Delete blocked - employee has training records', [
                    'employee_id' => $employee->id,
                    'training_records_count' => $trainingRecordsCount
                ]);

                DB::rollback();
                return redirect()->route('employees.index')
                    ->with('error', "Tidak dapat menghapus karyawan {$employee->name} karena memiliki {$trainingRecordsCount} training record(s). Hapus training records terlebih dahulu atau non-aktifkan karyawan.");
            }

            // Perform the delete
            $employeeName = $employee->name;
            $employeeId = $employee->employee_id;

            $deleted = $employee->delete();

            if (!$deleted) {
                throw new \Exception('Failed to delete employee from database');
            }

            // Commit transaction
            DB::commit();

            Log::info('Employee deleted successfully', [
                'employee_id' => $employee->id,
                'employee_name' => $employeeName,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('employees.index')
                ->with('success', "Karyawan {$employeeName} ({$employeeId}) berhasil dihapus.");

        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollback();

            Log::error('Employee delete failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return redirect()->route('employees.index')
                ->with('error', 'Gagal menghapus karyawan: ' . $e->getMessage());
        }
    }

    /**
     * DEBUGGING HELPER METHODS
     */

    /**
     * Check employee dependencies (for debugging)
     */
    public function checkDependencies(Employee $employee)
    {
        $dependencies = [
            'training_records' => $employee->trainingRecords()->count(),
            'created_training_records' => $employee->createdTrainingRecords()->count(),
            // Add more dependency checks here
        ];

        Log::info('Employee dependencies check', [
            'employee_id' => $employee->id,
            'dependencies' => $dependencies
        ]);

        return response()->json([
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'dependencies' => $dependencies,
            'can_delete' => array_sum($dependencies) === 0
        ]);
    }

    /**
     * Force delete employee (for admin/debugging only)
     */
    public function forceDestroy(Employee $employee)
    {
        // Only allow in development environment
        if (!app()->environment('local')) {
            abort(403, 'Force delete only available in development');
        }

        Log::warning('FORCE DELETE initiated', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'user_id' => Auth::id()
        ]);

        try {
            DB::beginTransaction();

            // Delete all training records first (cascade should handle this, but just in case)
            $employee->trainingRecords()->delete();

            // Delete employee
            $employee->delete();

            DB::commit();

            Log::warning('FORCE DELETE completed', [
                'employee_id' => $employee->id
            ]);

            return redirect()->route('employees.index')
                ->with('success', 'Employee FORCE DELETED successfully (DEV MODE)');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Force delete failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('employees.index')
                ->with('error', 'Force delete failed: ' . $e->getMessage());
        }
    }
}
