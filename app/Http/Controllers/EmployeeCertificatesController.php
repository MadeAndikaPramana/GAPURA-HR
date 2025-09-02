<?php
// app/Http/Controllers/EmployeeCertificatesController.php

namespace App\Http\Controllers;

use App\Models\EmployeeCertificate;
use App\Models\Employee;
use App\Models\CertificateType;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmployeeCertificatesController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of employee certificates
     */
    public function index(Request $request)
    {
        $query = EmployeeCertificate::with(['employee', 'certificateType']);

        // Search functionality
        if ($request->filled('search')) {
            $query->where('certificate_number', 'like', "%{$request->search}%")
                  ->orWhereHas('employee', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Certificate type filter
        if ($request->filled('certificate_type')) {
            $query->where('certificate_type_id', $request->certificate_type);
        }

        // Employee filter
        if ($request->filled('employee')) {
            $query->where('employee_id', $request->employee);
        }

        $certificates = $query->orderBy('issue_date', 'desc')->paginate(15);

        return Inertia::render('EmployeeCertificates/Index', [
            'certificates' => $certificates,
            'certificateTypes' => CertificateType::active()->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'certificate_type', 'employee'])
        ]);
    }

    /**
     * Show the form for creating a new employee certificate
     */
    public function create()
    {
        return Inertia::render('EmployeeCertificates/Create', [
            'employees' => Employee::active()->get(['id', 'name', 'employee_id']),
            'certificateTypes' => CertificateType::active()->get(['id', 'name', 'code', 'validity_months'])
        ]);
    }

    /**
     * Store a newly created employee certificate
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'certificate_number' => 'required|string|unique:employee_certificates,certificate_number',
            'issuer' => 'required|string|max:100',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'score' => 'nullable|numeric|min:0|max:100',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $certificate = EmployeeCertificate::create($request->all());

            // Handle file uploads if provided
            if ($request->hasFile('certificate_files')) {
                $uploadedFiles = $this->fileUploadService->uploadCertificateFiles(
                    $certificate->employee_id,
                    $certificate->id,
                    $request->file('certificate_files')
                );

                foreach ($uploadedFiles as $fileData) {
                    $certificate->addFile($fileData);
                }
            }

            DB::commit();

            Log::info('Employee certificate created', [
                'certificate_id' => $certificate->id,
                'employee_id' => $certificate->employee_id,
                'certificate_number' => $certificate->certificate_number
            ]);

            return redirect()->route('employee-certificates.show', $certificate)
                           ->with('success', 'Certificate created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create certificate', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified employee certificate
     */
    public function show(EmployeeCertificate $certificate)
    {
        $certificate->load(['employee', 'certificateType', 'createdBy', 'updatedBy']);

        return Inertia::render('EmployeeCertificates/Show', [
            'certificate' => $certificate
        ]);
    }

    /**
     * Show the form for editing the specified employee certificate
     */
    public function edit(EmployeeCertificate $certificate)
    {
        $certificate->load(['employee', 'certificateType']);

        return Inertia::render('EmployeeCertificates/Edit', [
            'certificate' => $certificate,
            'employees' => Employee::active()->get(['id', 'name', 'employee_id']),
            'certificateTypes' => CertificateType::active()->get(['id', 'name', 'code', 'validity_months'])
        ]);
    }

    /**
     * Update the specified employee certificate
     */
    public function update(Request $request, EmployeeCertificate $certificate)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'certificate_number' => 'required|string|unique:employee_certificates,certificate_number,' . $certificate->id,
            'issuer' => 'required|string|max:100',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'score' => 'nullable|numeric|min:0|max:100',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        try {
            $certificate->update($request->all());

            Log::info('Employee certificate updated', [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number
            ]);

            return redirect()->route('employee-certificates.show', $certificate)
                           ->with('success', 'Certificate updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update certificate', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified employee certificate
     */
    public function destroy(EmployeeCertificate $certificate)
    {
        try {
            $certificateNumber = $certificate->certificate_number;

            // Delete associated files
            if ($certificate->certificate_files) {
                foreach ($certificate->certificate_files as $file) {
                    $this->fileUploadService->deleteFile($file['path']);
                }
            }

            $certificate->delete();

            Log::info('Employee certificate deleted', [
                'certificate_number' => $certificateNumber
            ]);

            return redirect()->route('employee-certificates.index')
                           ->with('success', 'Certificate deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete certificate', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to delete certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload files for a certificate
     */
    public function uploadFiles(Request $request, EmployeeCertificate $certificate)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,txt'
        ]);

        try {
            $uploadedFiles = $this->fileUploadService->uploadCertificateFiles(
                $certificate->employee_id,
                $certificate->id,
                $request->file('files')
            );

            foreach ($uploadedFiles as $fileData) {
                $certificate->addFile($fileData);
            }

            Log::info('Certificate files uploaded', [
                'certificate_id' => $certificate->id,
                'files_count' => count($uploadedFiles)
            ]);

            return back()->with('success', count($uploadedFiles) . ' files uploaded successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to upload certificate files', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to upload files: ' . $e->getMessage()]);
        }
    }

    /**
     * Download a certificate file
     */
    public function downloadFile(EmployeeCertificate $certificate, string $fileName)
    {
        $files = $certificate->certificate_files ?? [];
        $file = collect($files)->firstWhere('stored_name', $fileName);

        if (!$file) {
            abort(404, 'File not found');
        }

        try {
            return $this->fileUploadService->downloadFile($file['path'], $file['original_name']);
        } catch (\Exception $e) {
            Log::error('Failed to download certificate file', [
                'certificate_id' => $certificate->id,
                'file' => $fileName,
                'error' => $e->getMessage()
            ]);

            abort(404, 'File not found or corrupted');
        }
    }

    /**
     * Delete a certificate file
     */
    public function deleteFile(EmployeeCertificate $certificate, string $fileName)
    {
        try {
            $files = $certificate->certificate_files ?? [];
            $file = collect($files)->firstWhere('stored_name', $fileName);

            if (!$file) {
                return back()->withErrors(['error' => 'File not found']);
            }

            // Delete file from storage
            $this->fileUploadService->deleteFile($file['path']);

            // Remove file from certificate record
            $certificate->removeFile($fileName);

            Log::info('Certificate file deleted', [
                'certificate_id' => $certificate->id,
                'file' => $file['original_name']
            ]);

            return back()->with('success', 'File deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete certificate file', [
                'certificate_id' => $certificate->id,
                'file' => $fileName,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to delete file: ' . $e->getMessage()]);
        }
    }

    /**
     * Renew a certificate (create new one with extended dates)
     */
    public function renew(Request $request, EmployeeCertificate $certificate)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'certificate_number' => 'required|string|unique:employee_certificates,certificate_number'
        ]);

        try {
            // Create new certificate based on the old one
            $newCertificate = $certificate->replicate();
            $newCertificate->certificate_number = $request->certificate_number;
            $newCertificate->issue_date = $request->issue_date;
            $newCertificate->expiry_date = $request->expiry_date;
            $newCertificate->status = 'active';
            $newCertificate->created_at = now();
            $newCertificate->updated_at = now();
            $newCertificate->save();

            // Mark old certificate as expired/superseded
            $certificate->update(['status' => 'expired']);

            Log::info('Certificate renewed', [
                'old_certificate_id' => $certificate->id,
                'new_certificate_id' => $newCertificate->id,
                'new_certificate_number' => $newCertificate->certificate_number
            ]);

            return redirect()->route('employee-certificates.show', $newCertificate)
                           ->with('success', 'Certificate renewed successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to renew certificate', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to renew certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update certificates
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'exists:employee_certificates,id',
            'action' => 'required|in:activate,expire,extend_expiry,update_status',
            'status' => 'required_if:action,update_status|string',
            'extend_months' => 'required_if:action,extend_expiry|integer|min:1|max:60'
        ]);

        try {
            $certificateIds = $request->certificate_ids;
            $updatedCount = 0;

            switch ($request->action) {
                case 'activate':
                    $updatedCount = EmployeeCertificate::whereIn('id', $certificateIds)
                                                     ->update(['status' => 'active']);
                    break;

                case 'expire':
                    $updatedCount = EmployeeCertificate::whereIn('id', $certificateIds)
                                                     ->update(['status' => 'expired']);
                    break;

                case 'update_status':
                    $updatedCount = EmployeeCertificate::whereIn('id', $certificateIds)
                                                     ->update(['status' => $request->status]);
                    break;

                case 'extend_expiry':
                    $certificates = EmployeeCertificate::whereIn('id', $certificateIds)->get();
                    foreach ($certificates as $cert) {
                        if ($cert->expiry_date) {
                            $cert->update([
                                'expiry_date' => \Carbon\Carbon::parse($cert->expiry_date)->addMonths($request->extend_months)
                            ]);
                            $updatedCount++;
                        }
                    }
                    break;
            }

            Log::info('Bulk certificate update', [
                'action' => $request->action,
                'certificate_count' => count($certificateIds),
                'updated_count' => $updatedCount
            ]);

            return back()->with('success', "{$updatedCount} certificates updated successfully.");

        } catch (\Exception $e) {
            Log::error('Bulk certificate update failed', [
                'action' => $request->action,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Bulk update failed: ' . $e->getMessage()]);
        }
    }
}
