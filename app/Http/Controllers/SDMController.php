<?php
// app/Http/Controllers/SDMController.php - Clean SDM Controller

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Imports\EmployeesImport;
use App\Imports\MPGATrainingImport;
use App\Exports\EmployeesExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Services\EmployeeContainerService;

class SDMController extends Controller
{
    /**
     * Display SDM main page (employee management)
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
                }
            ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
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

        // Sort
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $employees = $query->paginate(20);

        // Get statistics
        $statistics = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'inactive_employees' => Employee::where('status', 'inactive')->count(),
            'employees_with_containers' => Employee::has('employeeCertificates')->count(),
            'latest_additions' => Employee::latest()->take(5)->get(['name', 'employee_id', 'created_at'])
        ];

        return Inertia::render('SDM/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'statistics' => $statistics,
            'filters' => $request->only(['search', 'department', 'status', 'sort', 'direction'])
        ]);
    }

    /**
     * Show create employee form
     */
    public function create()
    {
        return Inertia::render('SDM/Create', [
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

        return redirect()->route('sdm.index')
            ->with('success', "Employee {$employee->name} created successfully. Container ready!");
    }

    /**
     * Show edit employee form
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('SDM/Edit', [
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

        return redirect()->route('sdm.index')
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

        return redirect()->route('sdm.index')
            ->with('success', "Employee {$name} deleted successfully.");
    }

    /**
     * Show Excel import page
     */
    public function showImport()
    {
        return Inertia::render('SDM/Import', [
            'departments' => Department::all(['id', 'name', 'code']),
            'importHistory' => $this->getRecentImports(),
            'mpgaImportHistory' => $this->getRecentMPGAImports()
        ]);
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        return $this->createExcelTemplate();
    }

    /**
     * Import employees from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB
            'update_existing' => 'boolean',
            'create_departments' => 'boolean'
        ]);

        try {
            $file = $request->file('excel_file');
            $updateExisting = $request->boolean('update_existing', false);
            $createDepartments = $request->boolean('create_departments', false);

            // Store file temporarily
            $filePath = $file->store('temp/imports');
            $fullPath = Storage::path($filePath);

            // Import using our custom import class
            $import = new EmployeesImport($updateExisting, $createDepartments);
            Excel::import($import, $fullPath);

            // Get import results
            $results = $import->getImportResults();

            // Clean up temp file
            Storage::delete($filePath);

            // Log import activity
            $this->logImportActivity($request, $results);

            $message = "Import completed! Created: {$results['created']}, Updated: {$results['updated']}, Skipped: {$results['skipped']}";
            
            if (isset($results['containers_created']) && $results['containers_created'] > 0) {
                $message .= ", Containers created: {$results['containers_created']}";
            }
            
            if (isset($results['container_errors']) && $results['container_errors'] > 0) {
                $message .= ", Container errors: {$results['container_errors']}";
            }
            
            return back()->with('success', $message)->with('import_results', $results);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import MPGA training data from Excel
     */
    public function importMPGA(Request $request)
    {
        $request->validate([
            'mpga_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'update_existing' => 'boolean',
            'create_types' => 'boolean'
        ]);

        try {
            $file = $request->file('mpga_file');
            $updateExisting = $request->boolean('update_existing', false);
            $createTypes = $request->boolean('create_types', false);

            // Store file temporarily
            $filePath = $file->store('temp/mpga_imports');
            $fullPath = Storage::path($filePath);

            // Import using MPGA import class
            $import = new MPGATrainingImport($updateExisting, $createTypes);
            Excel::import($import, $fullPath);

            // Get import results
            $results = $import->getImportResults();

            // Clean up temp file
            Storage::delete($filePath);

            // Log import activity
            $this->logMPGAImportActivity($request, $results);

            return back()->with('success',
                "MPGA import completed! Processed: {$results['processed']}, Created: {$results['created']}, Updated: {$results['updated']}, Errors: {$results['errors']}"
            )->with('mpga_import_results', $results);

        } catch (\Exception $e) {
            return back()->with('error', 'MPGA import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download MPGA import template
     */
    public function downloadMPGATemplate()
    {
        return $this->createMPGAExcelTemplate();
    }

    /**
     * Export employees to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['department', 'status', 'search']);
        $includeCertificates = $request->boolean('include_certificates', false);

        $filename = 'employees_export_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new EmployeesExport($filters, $includeCertificates),
            $filename
        );
    }

    /**
     * Bulk actions for multiple employees
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,export,assign_department',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $employees = Employee::whereIn('id', $request->employee_ids);
        $count = $employees->count();

        switch ($request->action) {
            case 'activate':
                $employees->update(['status' => 'active']);
                return back()->with('success', "{$count} employees activated successfully.");

            case 'deactivate':
                $employees->update(['status' => 'inactive']);
                return back()->with('success', "{$count} employees deactivated successfully.");

            case 'assign_department':
                if (!$request->department_id) {
                    return back()->with('error', 'Please select a department.');
                }
                $employees->update(['department_id' => $request->department_id]);
                return back()->with('success', "{$count} employees moved to new department.");

            case 'delete':
                // Check if any employees have certificates
                $employeesWithCerts = $employees->has('employeeCertificates')->count();
                if ($employeesWithCerts > 0) {
                    return back()->with('error',
                        "{$employeesWithCerts} employees have certificates and cannot be deleted."
                    );
                }
                $employees->delete();
                return back()->with('success', "{$count} employees deleted successfully.");

            case 'export':
                $filters = ['employee_ids' => $request->employee_ids];
                return Excel::download(
                    new EmployeesExport($filters),
                    'selected_employees_' . date('Y-m-d_His') . '.xlsx'
                );
        }
    }

    /**
     * Quick search for AJAX autocomplete
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = Employee::where('name', 'like', "%{$query}%")
            ->orWhere('employee_id', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'employee_id', 'name', 'position', 'department_id'])
            ->load('department:id,name');

        return response()->json($employees);
    }

    /**
     * Get statistics API
     */
    public function getStatistics()
    {
        $statistics = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'inactive_employees' => Employee::where('status', 'inactive')->count(),
            'employees_with_containers' => Employee::has('employeeCertificates')->count(),
            'departments_count' => Department::count(),
            'latest_additions' => Employee::latest()->take(5)->get(['name', 'employee_id', 'created_at'])
        ];

        return response()->json($statistics);
    }

    // ===== PRIVATE HELPER METHODS =====

    /**
     * Create basic Excel template
     */
    private function createExcelTemplate()
    {
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array
            {
                return [
                    ['EMP001', 'John Doe', 'john@example.com', '081234567890', 'IT', 'Senior Developer', '2024-01-15', 'active'],
                    ['EMP002', 'Jane Smith', 'jane@example.com', '081234567891', 'HR', 'HR Manager', '2024-02-01', 'active'],
                ];
            }

            public function headings(): array
            {
                return ['employee_id', 'name', 'email', 'phone', 'department', 'position', 'hire_date', 'status'];
            }
        }, 'employee_import_template.xlsx');
    }

    /**
     * Get recent import history
     */
    private function getRecentImports()
    {
        // This could be stored in a separate imports log table
        // For now, return mock data
        return [
            [
                'date' => now()->subDays(1)->format('Y-m-d H:i'),
                'file' => 'employees_jan_2024.xlsx',
                'created' => 25,
                'updated' => 5,
                'errors' => 0
            ],
            [
                'date' => now()->subDays(7)->format('Y-m-d H:i'),
                'file' => 'mpga_training_data.xlsx',
                'created' => 156,
                'updated' => 23,
                'errors' => 2
            ]
        ];
    }

    /**
     * Log import activity for audit trail
     */
    private function logImportActivity($request, $results)
    {
        \Illuminate\Support\Facades\Log::info('SDM Employee Excel Import', [
            'user_id' => auth()->id(),
            'filename' => $request->file('excel_file')->getClientOriginalName(),
            'results' => $results,
            'timestamp' => now()
        ]);
    }

    /**
     * Create MPGA Excel template
     */
    private function createMPGAExcelTemplate()
    {
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array
            {
                return [
                    ['EMP001', 'John Doe', 'Fire Safety Basic', '2024-01-15', '2026-01-15', 'Valid', 'CERT001', 'Initial'],
                    ['EMP002', 'Jane Smith', 'First Aid CPR', '2024-02-01', '2025-02-01', 'Valid', 'CERT002', 'Recurrent'],
                ];
            }

            public function headings(): array
            {
                return [
                    'NIP/Employee ID', 'Name', 'Training Type', 'Issue Date', 
                    'Expiry Date', 'Status', 'Certificate Number', 'Training Version'
                ];
            }
        }, 'mpga_training_import_template.xlsx');
    }

    /**
     * Get recent MPGA import history
     */
    private function getRecentMPGAImports()
    {
        // This could be stored in a separate imports log table
        // For now, return mock data
        return [
            [
                'date' => now()->subDays(2)->format('Y-m-d H:i'),
                'file' => 'mpga_training_q1_2024.xlsx',
                'processed' => 89,
                'created' => 45,
                'updated' => 32,
                'errors' => 2
            ],
            [
                'date' => now()->subWeeks(1)->format('Y-m-d H:i'),
                'file' => 'mpga_recurrent_training.xlsx',
                'processed' => 156,
                'created' => 78,
                'updated' => 73,
                'errors' => 5
            ]
        ];
    }

    /**
     * Log MPGA import activity for audit trail
     */
    private function logMPGAImportActivity($request, $results)
    {
        \Illuminate\Support\Facades\Log::info('SDM MPGA Training Import', [
            'user_id' => auth()->id(),
            'filename' => $request->file('mpga_file')->getClientOriginalName(),
            'results' => $results,
            'timestamp' => now()
        ]);
    }

    /**
     * Initialize containers for all employees without containers
     */
    public function initializeContainers()
    {
        try {
            $containerService = app(EmployeeContainerService::class);
            $results = $containerService->initializeAllMissingContainers();

            $message = "Container initialization completed! ";
            $message .= "Processed: {$results['total_processed']}, ";
            $message .= "Success: {$results['success']}, ";
            $message .= "Failed: {$results['failed']}";

            if (!empty($results['errors'])) {
                $message .= ". Some errors occurred - check logs for details.";
            }

            return redirect()->route('sdm.index')
                ->with($results['failed'] > 0 ? 'warning' : 'success', $message);

        } catch (\Exception $e) {
            return redirect()->route('sdm.index')
                ->with('error', 'Failed to initialize containers: ' . $e->getMessage());
        }
    }

    /**
     * Get container statistics for the SDM dashboard
     */
    public function getContainerStatistics()
    {
        $containerService = app(EmployeeContainerService::class);
        return response()->json($containerService->getContainerStatistics());
    }

    /**
     * Repair container for specific employee
     */
    public function repairEmployeeContainer(Employee $employee)
    {
        try {
            $containerService = app(EmployeeContainerService::class);
            
            if ($containerService->repairContainer($employee)) {
                return redirect()->route('sdm.index')
                    ->with('success', "Container for {$employee->name} has been repaired successfully.");
            } else {
                return redirect()->route('sdm.index')
                    ->with('error', "Failed to repair container for {$employee->name}.");
            }
        } catch (\Exception $e) {
            return redirect()->route('sdm.index')
                ->with('error', "Error repairing container: " . $e->getMessage());
        }
    }
}
