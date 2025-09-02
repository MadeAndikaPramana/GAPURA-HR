<?php
// app/Http/Controllers/EmployeesController.php (Updated for Container System)

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Services\FileUploadService;
use App\Exports\EmployeesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class EmployeesController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of employees with container preview
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'activeCertificates', 'expiredCertificates', 'expiringSoonCertificates']);

        // Search functionality
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

        // Compliance filter
        if ($request->filled('compliance')) {
            switch ($request->compliance) {
                case 'compliant':
                    $query->whereDoesntHave('expiredCertificates')
                          ->whereDoesntHave('expiringSoonCertificates');
                    break;
                case 'expiring_soon':
                    $query->whereHas('expiringSoonCertificates');
                    break;
                case 'expired':
                    $query->whereHas('expiredCertificates');
                    break;
            }
        }

        $employees = $query->paginate(15)->withQueryString();

        // Add compliance statistics to each employee
        $employees->getCollection()->transform(function ($employee) {
            $employee->compliance = $employee->getComplianceStatistics();
            return $employee;
        });

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => Department::active()->get(['id', 'name']),
            'filters' => $request->only(['search', 'department', 'status', 'compliance']),
            'stats' => $this->getDashboardStats()
        ]);
    }

    /**
     * Show the employee container (main employee view)
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'supervisor',
            'subordinates',
            'employeeCertificates.certificateType',
            'employeeCertificates' => function($query) {
                $query->orderBy('issue_date', 'desc');
            }
        ]);

        // Add compliance statistics
        $employee->compliance = $employee->getComplianceStatistics();

        // Group certificates by type for better display
        $employee->certificatesByType = $employee->getCurrentCertificatesByType();
        $employee->certificateHistory = $employee->getCertificateHistoryByType();

        return Inertia::render('Employees/Container', [
            'employee' => $employee
        ]);
    }

    /**
     * NEW: Show employee container (alias for show)
     */
    public function container(Employee $employee)
    {
        return $this->show($employee);
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        return Inertia::render('Employees/Create', [
            'departments' => Department::active()->get(['id', 'name']),
            'supervisors' => Employee::where('status', 'active')->get(['id', 'name', 'employee_id']),
            'backgroundCheckStatuses' => $this->getBackgroundCheckStatuses()
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string|max:255',
            'position_level' => 'nullable|string|max:100',
            'employment_type' => 'nullable|string|max:100',
            'hire_date' => 'required|date',
            'supervisor_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:active,inactive,terminated',
            'background_check_date' => 'nullable|date',
            'background_check_status' => 'nullable|string',
            'background_check_notes' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $employee = Employee::create($request->all());

            // Handle background check files if uploaded
            if ($request->hasFile('background_check_files')) {
                $uploadedFiles = $this->fileUploadService->uploadBackgroundCheckFiles(
                    $employee->id,
                    $request->file('background_check_files')
                );

                foreach ($uploadedFiles as $fileData) {
                    $employee->addBackgroundCheckFile($fileData);
                }
            }

            DB::commit();

            Log::info('Employee created', ['employee_id' => $employee->employee_id, 'name' => $employee->name]);

            return redirect()->route('employees.container', $employee)->with('success', 'Employee created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create employee', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing an employee
     */
    public function edit(Employee $employee)
    {
        $employee->load('department', 'supervisor');

        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'departments' => Department::active()->get(['id', 'name']),
            'supervisors' => Employee::where('status', 'active')
                                   ->where('id', '!=', $employee->id)
                                   ->get(['id', 'name', 'employee_id']),
            'backgroundCheckStatuses' => $this->getBackgroundCheckStatuses()
        ]);
    }

    /**
     * Update an employee
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id,' . $employee->id,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string|max:255',
            'position_level' => 'nullable|string|max:100',
            'employment_type' => 'nullable|string|max:100',
            'hire_date' => 'required|date',
            'supervisor_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:active,inactive,terminated',
            'background_check_date' => 'nullable|date',
            'background_check_status' => 'nullable|string',
            'background_check_notes' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $employee->update($request->all());

            DB::commit();

            Log::info('Employee updated', ['employee_id' => $employee->employee_id, 'name' => $employee->name]);

            return redirect()->route('employees.container', $employee)->with('success', 'Employee updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update employee', ['error' => $e->getMessage(), 'employee_id' => $employee->id]);

            return back()->withErrors(['error' => 'Failed to update employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove an employee
     */
    public function destroy(Employee $employee)
    {
        try {
            // Check if employee has certificates
            $certificateCount = $employee->employeeCertificates()->count();

            if ($certificateCount > 0) {
                return back()->withErrors(['error' => "Cannot delete employee. They have {$certificateCount} certificates associated with them."]);
            }

            $employeeId = $employee->employee_id;
            $employeeName = $employee->name;

            $employee->delete();

            Log::info('Employee deleted', ['employee_id' => $employeeId, 'name' => $employeeName]);

            return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete employee', ['error' => $e->getMessage(), 'employee_id' => $employee->id]);

            return back()->withErrors(['error' => 'Failed to delete employee: ' . $e->getMessage()]);
        }
    }

    /**
     * NEW: Upload background check files
     */
    public function uploadBackgroundCheck(Request $request, Employee $employee)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,txt'
        ]);

        try {
            $uploadedFiles = $this->fileUploadService->uploadBackgroundCheckFiles(
                $employee->id,
                $request->file('files')
            );

            foreach ($uploadedFiles as $fileData) {
                $employee->addBackgroundCheckFile($fileData);
            }

            Log::info('Background check files uploaded', [
                'employee_id' => $employee->employee_id,
                'files_count' => count($uploadedFiles)
            ]);

            return back()->with('success', count($uploadedFiles) . ' background check files uploaded successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to upload background check files', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to upload files: ' . $e->getMessage()]);
        }
    }

    /**
     * NEW: Download background check file
     */
    public function downloadBackgroundCheck(Employee $employee, string $fileName)
    {
        $files = $employee->background_check_files ?? [];
        $file = collect($files)->firstWhere('stored_name', $fileName);

        if (!$file) {
            abort(404, 'File not found');
        }

        try {
            return $this->fileUploadService->downloadFile($file['path'], $file['original_name']);
        } catch (\Exception $e) {
            Log::error('Failed to download background check file', [
                'employee_id' => $employee->id,
                'file' => $fileName,
                'error' => $e->getMessage()
            ]);

            abort(404, 'File not found or corrupted');
        }
    }

    /**
     * NEW: Delete background check file
     */
    public function deleteBackgroundCheckFile(Request $request, Employee $employee)
    {
        $fileName = $request->input('file_name');

        if (!$fileName) {
            return back()->withErrors(['error' => 'File name is required']);
        }

        try {
            $files = $employee->background_check_files ?? [];
            $file = collect($files)->firstWhere('stored_name', $fileName);

            if (!$file) {
                return back()->withErrors(['error' => 'File not found']);
            }

            // Delete file from storage
            $this->fileUploadService->deleteFile($file['path']);

            // Remove file from employee record
            $employee->removeBackgroundCheckFile($fileName);

            Log::info('Background check file deleted', [
                'employee_id' => $employee->employee_id,
                'file' => $file['original_name']
            ]);

            return back()->with('success', 'File deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete background check file', [
                'employee_id' => $employee->id,
                'file' => $fileName,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to delete file: ' . $e->getMessage()]);
        }
    }

    /**
     * Export employees to Excel
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['department', 'status', 'search']);

            return Excel::download(
                new EmployeesExport($filters),
                'employees_' . now()->format('Y-m-d') . '.xlsx'
            );

        } catch (\Exception $e) {
            Log::error('Failed to export employees', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to export employees: ' . $e->getMessage()]);
        }
    }

    /**
     * NEW: Get dashboard statistics
     */
    protected function getDashboardStats(): array
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'total_certificates' => EmployeeCertificate::count(),
            'active_certificates' => EmployeeCertificate::where('status', 'active')->count(),
            'expired_certificates' => EmployeeCertificate::where('status', 'expired')->count(),
            'expiring_soon' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
            'compliance_rate' => $this->calculateOverallComplianceRate()
        ];
    }

    /**
     * NEW: Calculate overall compliance rate
     */
    protected function calculateOverallComplianceRate(): float
    {
        $totalCertificates = EmployeeCertificate::count();
        if ($totalCertificates === 0) {
            return 100.0;
        }

        $activeCertificates = EmployeeCertificate::where('status', 'active')->count();

        return round(($activeCertificates / $totalCertificates) * 100, 2);
    }

    /**
     * NEW: Get background check status options
     */
    protected function getBackgroundCheckStatuses(): array
    {
        return [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'cleared' => 'Cleared',
            'pending_review' => 'Pending Review',
            'requires_follow_up' => 'Requires Follow-up',
            'expired' => 'Expired',
            'rejected' => 'Rejected'
        ];
    }

    /**
     * NEW: Bulk update employee statuses
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'action' => 'required|in:activate,deactivate,update_department,update_background_check',
            'department_id' => 'required_if:action,update_department|exists:departments,id',
            'background_check_status' => 'required_if:action,update_background_check|string'
        ]);

        try {
            $employeeIds = $request->employee_ids;
            $updatedCount = 0;

            switch ($request->action) {
                case 'activate':
                    $updatedCount = Employee::whereIn('id', $employeeIds)->update(['status' => 'active']);
                    break;

                case 'deactivate':
                    $updatedCount = Employee::whereIn('id', $employeeIds)->update(['status' => 'inactive']);
                    break;

                case 'update_department':
                    $updatedCount = Employee::whereIn('id', $employeeIds)
                                          ->update(['department_id' => $request->department_id]);
                    break;

                case 'update_background_check':
                    $updatedCount = Employee::whereIn('id', $employeeIds)
                                          ->update(['background_check_status' => $request->background_check_status]);
                    break;
            }

            Log::info('Bulk employee update', [
                'action' => $request->action,
                'employee_count' => count($employeeIds),
                'updated_count' => $updatedCount
            ]);

            return back()->with('success', "{$updatedCount} employees updated successfully.");

        } catch (\Exception $e) {
            Log::error('Bulk employee update failed', [
                'action' => $request->action,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Bulk update failed: ' . $e->getMessage()]);
        }
    }
}
