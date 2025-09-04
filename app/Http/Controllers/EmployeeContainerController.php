<?php
// app/Http/Controllers/EmployeeContainerController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmployeeContainerController extends Controller
{
    /**
     * Display employee container (digital folder)
     */
    public function show(Employee $employee)
    {
        // Load relationships needed for container
        $employee->load(['department', 'employeeCertificates.certificateType']);

        // Get complete container data
        $containerData = $employee->getContainerData();

        return Inertia::render('Employees/Container', [
            'employee' => $employee,
            'containerData' => $containerData,
            'certificateTypes' => CertificateType::active()->get(['id', 'name', 'code', 'validity_months'])
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
                'background-check'
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
                    "certificates/{$certificate->certificateType->code}"
                );

                $uploadedFiles[] = $uploadedFile;
            }

            $certificate->update(['certificate_files' => $uploadedFiles]);
        }

        // Update certificate status based on dates
        $certificate->updateStatusBasedOnDates();

        // Check if this makes previous certificates of same type expired/historical
        $this->updatePreviousCertificatesStatus($employee, $certificate);

        return back()->with('success', 'Certificate added successfully to employee container.');
    }

    /**
     * Download certificate file
     */
    public function downloadCertificateFile(EmployeeCertificate $certificate, $fileIndex)
    {
        // Security check - ensure user has access to this employee's data
        $this->authorize('view', $certificate->employee);

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

    /**
     * Export employee container as PDF
     */
    public function exportContainer(Employee $employee)
    {
        $employee->load(['department', 'employeeCertificates.certificateType']);
        $containerData = $employee->getContainerData();

        // Generate PDF using a service or library like DomPDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('exports.employee-container', [
            'employee' => $employee,
            'containerData' => $containerData
        ]);

        return $pdf->download("employee_container_{$employee->employee_id}.pdf");
    }

    /**
     * Get container statistics for dashboard
     */
    public function getContainerStatistics()
    {
        $stats = [
            'total_employees' => Employee::count(),
            'employees_with_certificates' => Employee::has('employeeCertificates')->count(),
            'employees_with_background_checks' => Employee::whereNotNull('background_check_date')->count(),
            'total_certificates' => EmployeeCertificate::count(),
            'active_certificates' => EmployeeCertificate::where('status', 'active')->count(),
            'expired_certificates' => EmployeeCertificate::where('status', 'expired')->count(),
            'expiring_soon_certificates' => EmployeeCertificate::where('status', 'expiring_soon')->count(),
        ];

        return response()->json($stats);
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
            'uploaded_by' => auth()->user()->name ?? 'System'
        ];
    }

    /**
     * Update status of previous certificates when new one is added
     */
    private function updatePreviousCertificatesStatus(Employee $employee, EmployeeCertificate $newCertificate)
    {
        // For recurrent certificates, mark older ones of same type as historical
        $previousCertificates = EmployeeCertificate::where('employee_id', $employee->id)
            ->where('certificate_type_id', $newCertificate->certificate_type_id)
            ->where('id', '!=', $newCertificate->id)
            ->where('issue_date', '<', $newCertificate->issue_date)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->get();

        foreach ($previousCertificates as $cert) {
            // Check if it's actually expired based on dates
            if ($cert->expiry_date && $cert->expiry_date->lt(now())) {
                $cert->update(['status' => 'expired']);
            }
        }
    }
}
