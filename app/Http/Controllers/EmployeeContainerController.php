<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeContainerController extends Controller
{
    /**
     * Display employee containers in Google Drive style
     */
    public function index(Request $request)
    {
        $query = Employee::withContainerData()
            ->withCount([
                'employeeCertificates as total_certificates',
                'activeCertificates as active_certificates'
            ]);

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Container status filter
        if ($request->filled('container_status')) {
            switch ($request->container_status) {
                case 'with_files':
                    $query->where('total_files_count', '>', 0);
                    break;
                case 'without_files':
                    $query->where('total_files_count', '=', 0);
                    break;
                case 'with_certificates':
                    $query->has('employeeCertificates');
                    break;
                case 'with_background_check':
                    $query->whereNotNull('background_check_files');
                    break;
            }
        }

        $employees = $query->orderBy('name')->paginate(24); // 24 for nice grid layout

        // Transform data for container grid view
        $employees->getCollection()->transform(function ($employee) {
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'nip' => $employee->nip,
                'name' => $employee->name,
                'department' => $employee->department?->name,
                'position' => $employee->position,
                'status' => $employee->status,

                // Container data
                'container_created_at' => $employee->container_created_at,
                'has_container' => !is_null($employee->container_created_at),
                'total_files' => $employee->total_files_count,
                'total_certificates' => $employee->total_certificates,
                'active_certificates' => $employee->active_certificates,

                // Background check status
                'background_check_status' => $employee->background_check_status,
                'has_background_check' => !empty($employee->background_check_files),
                'background_check_files_count' => count($employee->background_check_files ?? []),

                // Container status indicators
                'container_status' => $this->getContainerStatus($employee),
                'status_color' => $this->getContainerStatusColor($employee),
            ];
        });

        return Inertia::render('EmployeeContainers/Index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'department', 'status', 'container_status']),
            'stats' => $this->getContainerStats(),
        ]);
    }

    /**
     * Show individual employee container (Digital folder view)
     */
    public function show(Employee $employee)
    {
        // Ensure container exists
        if (!$employee->container_created_at) {
            $employee->createDigitalContainer();
        }

        // Get complete container data
        $containerData = $employee->getContainerData();

        // Get certificate types for dropdown
        $certificateTypes = CertificateType::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'category']);

        return Inertia::render('EmployeeContainers/Show', [
            'employee' => $employee->load('department'),
            'containerData' => $containerData,
            'certificateTypes' => $certificateTypes,
            'breadcrumb' => [
                ['name' => 'Employee Containers', 'url' => route('employee-containers.index')],
                ['name' => $employee->name, 'url' => null]
            ]
        ]);
    }

    // ===== BACKGROUND CHECK OPERATIONS =====

    /**
     * Upload background check files
     */
    public function uploadBackgroundCheck(Request $request, Employee $employee)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:pdf,jpg,jpeg|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:not_started,in_progress,completed,expired'
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $filePath = $this->storeEmployeeFile($employee, $file, 'background-checks');

            $employee->addBackgroundCheckFile(
                $filePath,
                $file->getClientOriginalName(),
                $file->getSize(),
                $file->getMimeType()
            );

            $uploadedFiles[] = $file->getClientOriginalName();
        }

        // Update background check metadata
        if ($request->filled('notes') || $request->filled('status')) {
            $employee->update([
                'background_check_notes' => $request->notes ?? $employee->background_check_notes,
                'background_check_status' => $request->status ?? $employee->background_check_status,
                'background_check_date' => now()
            ]);
        }

        return back()->with('success', 'Background check files uploaded: ' . implode(', ', $uploadedFiles));
    }

    /**
     * Update background check metadata
     */
    public function updateBackgroundCheck(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,completed,expired',
            'notes' => 'nullable|string|max:1000',
            'date' => 'nullable|date'
        ]);

        $employee->update([
            'background_check_status' => $validated['status'],
            'background_check_notes' => $validated['notes'],
            'background_check_date' => $validated['date'] ?? $employee->background_check_date ?? now()
        ]);

        return back()->with('success', 'Background check information updated.');
    }

    /**
     * Download background check file
     */
    public function downloadBackgroundCheck(Employee $employee, $fileIndex)
    {
        $files = $employee->background_check_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        if (!Storage::disk('private')->exists($file['path'])) {
            abort(404, 'File not found on disk');
        }

        return Storage::disk('private')->download($file['path'], $file['original_name']);
    }

    /**
     * Remove background check file
     */
    public function removeBackgroundCheck(Employee $employee, $fileIndex)
    {
        $files = $employee->background_check_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        // Delete from storage
        if (Storage::disk('private')->exists($file['path'])) {
            Storage::disk('private')->delete($file['path']);
        }

        // Remove from employee record
        $employee->removeBackgroundCheckFile($fileIndex);

        return back()->with('success', 'Background check file removed successfully.');
    }

    // ===== CERTIFICATE OPERATIONS (RECURRENT SUPPORT) =====

    /**
     * Add new certificate to container (supports recurrent)
     */
    public function addCertificate(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'certificate_number' => 'nullable|string|max:100',
            'issuer' => 'nullable|string|max:100',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg|max:5120'
        ]);

        // Handle file uploads
        $certificateFiles = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filePath = $this->storeEmployeeFile($employee, $file, 'certificates');

                $certificateFiles[] = [
                    'path' => $filePath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString(),
                    'uploaded_by' => auth()->user()->name ?? 'System'
                ];
            }
        }

        // Determine status based on expiry date
        $status = $this->calculateCertificateStatus($validated['expiry_date']);

        // Create certificate (recurrent - always add new, keep old)
        $certificate = $employee->employeeCertificates()->create([
            'certificate_type_id' => $validated['certificate_type_id'],
            'certificate_number' => $validated['certificate_number'],
            'issuer' => $validated['issuer'] ?? 'Unknown',
            'training_provider' => $validated['training_provider'],
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'completion_date' => $validated['completion_date'],
            'training_date' => $validated['training_date'],
            'status' => $status,
            'certificate_files' => $certificateFiles,
            'notes' => $validated['notes'],
            'created_by_id' => auth()->id()
        ]);

        // Update employee files count
        $employee->calculateTotalFiles();

        return back()->with('success', 'Certificate added to container successfully.');
    }

    /**
     * Update existing certificate
     */
    public function updateCertificate(Request $request, Employee $employee, EmployeeCertificate $certificate)
    {
        // Ensure certificate belongs to this employee
        if ($certificate->employee_id !== $employee->id) {
            abort(403, 'Certificate does not belong to this employee');
        }

        $validated = $request->validate([
            'certificate_number' => 'nullable|string|max:100',
            'issuer' => 'nullable|string|max:100',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg|max:5120'
        ]);

        // Handle new file uploads
        $existingFiles = $certificate->certificate_files ?? [];
        $newFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filePath = $this->storeEmployeeFile($employee, $file, 'certificates');

                $newFiles[] = [
                    'path' => $filePath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString(),
                    'uploaded_by' => auth()->user()->name ?? 'System'
                ];
            }
        }

        $allFiles = array_merge($existingFiles, $newFiles);
        $status = $this->calculateCertificateStatus($validated['expiry_date']);

        // Update certificate
        $certificate->update([
            'certificate_number' => $validated['certificate_number'],
            'issuer' => $validated['issuer'],
            'training_provider' => $validated['training_provider'],
            'issue_date' => $validated['issue_date'],
            'expiry_date' => $validated['expiry_date'],
            'completion_date' => $validated['completion_date'],
            'training_date' => $validated['training_date'],
            'status' => $status,
            'certificate_files' => $allFiles,
            'notes' => $validated['notes'],
            'updated_by_id' => auth()->id()
        ]);

        // Update employee files count
        $employee->calculateTotalFiles();

        return back()->with('success', 'Certificate updated successfully.');
    }

    /**
     * Remove certificate from container
     */
    public function removeCertificate(Employee $employee, EmployeeCertificate $certificate)
    {
        // Ensure certificate belongs to this employee
        if ($certificate->employee_id !== $employee->id) {
            abort(403, 'Certificate does not belong to this employee');
        }

        // Delete certificate files from storage
        $files = $certificate->certificate_files ?? [];
        foreach ($files as $file) {
            if (Storage::disk('private')->exists($file['path'])) {
                Storage::disk('private')->delete($file['path']);
            }
        }

        $certificate->delete();

        // Update employee files count
        $employee->calculateTotalFiles();

        return back()->with('success', 'Certificate removed from container successfully.');
    }

    /**
     * Download certificate file
     */
    public function downloadCertificate(EmployeeCertificate $certificate, $fileIndex)
    {
        $files = $certificate->certificate_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        if (!Storage::disk('private')->exists($file['path'])) {
            abort(404, 'File not found on disk');
        }

        return Storage::disk('private')->download($file['path'], $file['original_name']);
    }

    // ===== CONTAINER UTILITIES =====

    /**
     * Get files count for container
     */
    public function getFilesCount(Employee $employee)
    {
        $totalFiles = $employee->calculateTotalFiles();

        return response()->json([
            'total_files' => $totalFiles,
            'background_check_files' => count($employee->background_check_files ?? []),
            'certificate_files' => $employee->employeeCertificates->sum(function($cert) {
                return count($cert->certificate_files ?? []);
            })
        ]);
    }

    /**
     * Refresh container data
     */
    public function refreshContainer(Employee $employee)
    {
        $employee->calculateTotalFiles();

        return response()->json([
            'success' => true,
            'container_data' => $employee->getContainerData()
        ]);
    }

    /**
     * Get container statistics via API
     */
    public function getContainerStats(Employee $employee)
    {
        return response()->json($employee->getContainerData()['container_stats']);
    }

    // ===== HELPER METHODS =====

    /**
     * Store employee file in organized structure
     */
    private function storeEmployeeFile(Employee $employee, $file, $subfolder)
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = $timestamp . '_' . $file->getClientOriginalName();

        $path = "containers/employee-{$employee->id}/{$subfolder}/{$filename}";

        return $file->storeAs($path, $filename, 'private');
    }

    /**
     * Calculate certificate status based on expiry date
     */
    private function calculateCertificateStatus($expiryDate)
    {
        if (!$expiryDate) {
            return 'active';
        }

        $expiry = Carbon::parse($expiryDate);
        $now = Carbon::now();

        if ($expiry->isPast()) {
            return 'expired';
        } elseif ($expiry->diffInDays($now) <= 30) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }

    /**
     * Get container status for grid view
     */
    private function getContainerStatus(Employee $employee)
    {
        if ($employee->total_files_count === 0) {
            return 'empty';
        } elseif ($employee->active_certificates > 0 && $employee->background_check_status === 'completed') {
            return 'complete';
        } elseif ($employee->total_certificates > 0 || !empty($employee->background_check_files)) {
            return 'partial';
        } else {
            return 'empty';
        }
    }

    /**
     * Get container status color for UI
     */
    private function getContainerStatusColor(Employee $employee)
    {
        switch ($this->getContainerStatus($employee)) {
            case 'complete':
                return 'green';
            case 'partial':
                return 'yellow';
            case 'empty':
                return 'gray';
            default:
                return 'gray';
        }
    }

    /**
     * Get overall container statistics
     */
    private function getContainerStats()
    {
        return [
            'total_containers' => Employee::whereNotNull('container_created_at')->count(),
            'containers_with_files' => Employee::where('total_files_count', '>', 0)->count(),
            'total_certificates' => EmployeeCertificate::count(),
            'total_files' => Employee::sum('total_files_count'),
            'active_certificates' => EmployeeCertificate::where('status', 'active')->count(),
            'employees_with_bg_check' => Employee::whereNotNull('background_check_files')->count(),
        ];
    }
}
