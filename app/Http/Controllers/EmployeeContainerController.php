<?php
// app/Http/Controllers/EmployeeContainerController.php - FINAL CLEAN VERSION

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmployeeContainerController extends Controller
{
    /**
     * Display all employee containers (main index page)
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
            'active_certificates' => EmployeeCertificate::where('status', 'active')->count(),
            'expired_certificates' => EmployeeCertificate::where('status', 'expired')->count(),
            'expiring_soon' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
        ];

        return Inertia::render('EmployeeContainer/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'certificateTypes' => CertificateType::where('is_active', true)->get(['id', 'name', 'code']),
            'overallStats' => $overallStats,
            'filters' => $request->only(['search', 'department', 'status', 'container_status'])
        ]);
    }

    /**
     * Display individual employee container (digital folder)
     */
    public function show(Employee $employee)
    {
        // Load relationships needed for container
        $employee->load(['department', 'employeeCertificates.certificateType']);

        // Get complete container data
        $containerData = $employee->getContainerData();

        return Inertia::render('EmployeeContainer/Show', [
            'employee' => $employee,
            'containerData' => $containerData,
            'certificateTypes' => CertificateType::where('is_active', true)->get(['id', 'name', 'code', 'validity_months'])
        ]);
    }

    /**
     * Upload background check files
     */
    public function uploadBackgroundCheckFiles(Request $request, Employee $employee)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:not_started,in_progress,cleared,pending_review,requires_follow_up,expired,rejected'
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $uploadedFile = $this->storeEmployeeFile(
                $employee,
                $file,
                'background-checks'
            );

            $uploadedFiles[] = $uploadedFile;
        }

        // Update employee background check data
        $existingFiles = $employee->background_check_files ?? [];
        $allFiles = array_merge($existingFiles, $uploadedFiles);

        $employee->update([
            'background_check_files' => $allFiles,
            'background_check_date' => now(),
            'background_check_status' => $request->status ?? $employee->background_check_status,
            'background_check_notes' => $request->notes ?? $employee->background_check_notes
        ]);

        return back()->with('success', 'Background check files uploaded successfully.');
    }

    /**
     * Download background check file
     */
    public function downloadBackgroundCheckFile(Employee $employee, $fileIndex)
    {
        $files = $employee->background_check_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];
        $filePath = $file['path'];

        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'File not found on disk');
        }

        return Storage::disk('private')->download($filePath, $file['original_name']);
    }

    /**
     * Update background check status and notes
     */
    public function updateBackgroundCheck(Request $request, Employee $employee)
    {
        $request->validate([
            'status' => 'required|in:not_started,in_progress,cleared,pending_review,requires_follow_up,expired,rejected',
            'notes' => 'nullable|string|max:1000'
        ]);

        $employee->update([
            'background_check_status' => $request->status,
            'background_check_notes' => $request->notes,
            'background_check_date' => $request->status !== 'not_started' ? ($employee->background_check_date ?? now()) : null
        ]);

        return back()->with('success', 'Background check updated successfully.');
    }

    /**
     * Store certificate with files
     */
    public function storeCertificate(Request $request, Employee $employee)
    {
        $request->validate([
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'certificate_number' => 'required|string|max:100|unique:employee_certificates',
            'issuer' => 'required|string|max:100',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'training_hours' => 'nullable|numeric|min:0|max:999.99',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'score' => 'nullable|string|max:10',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Get certificate type for folder naming
        $certificateType = CertificateType::findOrFail($request->certificate_type_id);

        // Create certificate record
        $certificate = EmployeeCertificate::create([
            'employee_id' => $employee->id,
            'certificate_type_id' => $request->certificate_type_id,
            'certificate_number' => $request->certificate_number,
            'issuer' => $request->issuer,
            'training_provider' => $request->training_provider,
            'issue_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
            'completion_date' => $request->completion_date,
            'training_date' => $request->training_date,
            'status' => $request->completion_date ? 'completed' : 'pending',
            'training_hours' => $request->training_hours,
            'cost' => $request->cost,
            'score' => $request->score,
            'location' => $request->location,
            'instructor_name' => $request->instructor_name,
            'notes' => $request->notes,
            'created_by_id' => auth()->id()
        ]);

        // Handle file uploads
        if ($request->hasFile('files')) {
            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                $uploadedFile = $this->storeEmployeeFile(
                    $employee,
                    $file,
                    "certificates/{$certificateType->code}"
                );

                $uploadedFiles[] = $uploadedFile;
            }

            $certificate->update(['certificate_files' => $uploadedFiles]);
        }

        // Update certificate status based on dates
        if (method_exists($certificate, 'updateStatusBasedOnDates')) {
            $certificate->updateStatusBasedOnDates();
        }

        return back()->with('success', 'Certificate added successfully to employee container.');
    }

    /**
     * Update certificate
     */
    public function updateCertificate(Request $request, Employee $employee, EmployeeCertificate $certificate)
    {
        $request->validate([
            'certificate_number' => 'required|string|max:100|unique:employee_certificates,certificate_number,' . $certificate->id,
            'issuer' => 'required|string|max:100',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'training_hours' => 'nullable|numeric|min:0|max:999.99',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'score' => 'nullable|string|max:10',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ]);

        $certificate->update($request->only([
            'certificate_number', 'issuer', 'training_provider',
            'issue_date', 'expiry_date', 'completion_date', 'training_date',
            'training_hours', 'cost', 'score', 'location', 'instructor_name', 'notes'
        ]));

        // Update status based on new dates
        if (method_exists($certificate, 'updateStatusBasedOnDates')) {
            $certificate->updateStatusBasedOnDates();
        }

        return back()->with('success', 'Certificate updated successfully.');
    }

    /**
     * Delete certificate
     */
    public function deleteCertificate(Employee $employee, EmployeeCertificate $certificate)
    {
        // Delete associated files
        if ($certificate->certificate_files) {
            foreach ($certificate->certificate_files as $file) {
                if (Storage::disk('private')->exists($file['path'])) {
                    Storage::disk('private')->delete($file['path']);
                }
            }
        }

        $certificate->delete();

        return back()->with('success', 'Certificate deleted successfully.');
    }

    /**
     * Download certificate file
     */
    public function downloadCertificateFile(EmployeeCertificate $certificate, $fileIndex)
    {
        $files = $certificate->certificate_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];
        $filePath = $file['path'];

        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'File not found on disk');
        }

        return Storage::disk('private')->download($filePath, $file['original_name']);
    }

    // ===== PRIVATE HELPER METHODS =====

    /**
     * Store file in employee's organized folder structure
     */
    private function storeEmployeeFile(Employee $employee, $file, string $subfolder): array
    {
        $employeeFolder = "employees/{$employee->employee_id}";
        $fullPath = "{$employeeFolder}/{$subfolder}";

        // Generate unique filename with timestamp
        $timestamp = now()->format('Y-m-d_His');
        $extension = $file->getClientOriginalExtension();
        $filename = "{$timestamp}_{$file->getClientOriginalName()}";

        // Store file in private disk
        $path = $file->storeAs($fullPath, $filename, 'private');

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => auth()->user()->name ?? 'system'
        ];
    }
}
