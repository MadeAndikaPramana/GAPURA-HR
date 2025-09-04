<?php
// app/Http/Controllers/EmployeeContainerController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class EmployeeContainerController extends Controller
{
    /**
     * Display container list - Grid/List view
     */
    public function index(Request $request)
    {
        try {
            $query = Employee::query();

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('nip', 'LIKE', "%{$search}%")
                      ->orWhere('employee_id', 'LIKE', "%{$search}%")
                      ->orWhere('position', 'LIKE', "%{$search}%");
                });
            }

            // Department filter
            if ($request->filled('department')) {
                $query->where('department_id', $request->get('department'));
            }

            // Load relationships
            $query->with(['department:id,name,code']);

            // Add certificate and background check stats
            $query->withCount([
                'employeeCertificates as certificates_total',
                'employeeCertificates as certificates_active' => function ($q) {
                    $q->where('status', 'active');
                },
                'employeeCertificates as certificates_expired' => function ($q) {
                    $q->where('status', 'expired');
                },
                'employeeCertificates as certificates_expiring_soon' => function ($q) {
                    $q->where('status', 'expiring_soon');
                }
            ]);

            // Paginate results
            $employees = $query->latest()->paginate(20);

            // Get departments for filter
            $departments = Department::orderBy('name')->get(['id', 'name']);

            return Inertia::render('Employees/Index', [
                'employees' => $employees,
                'departments' => $departments,
                'filters' => $request->only(['search', 'department']),
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading employee containers: ' . $e->getMessage());

            return Inertia::render('Employees/Index', [
                'employees' => ['data' => [], 'total' => 0],
                'departments' => [],
                'filters' => [],
                'error' => 'Could not load employee containers.'
            ]);
        }
    }

    /**
     * Show individual container with sheet tabs
     */
    public function show(Employee $employee)
    {
        try {
            // Load all necessary relationships
            $employee->load([
                'department:id,name,code',
                'employeeCertificates.certificateType:id,name,code,typical_validity_months',
                'employeeCertificates' => function ($query) {
                    $query->latest();
                }
            ]);

            // Get certificate types for dropdown
            $certificateTypes = CertificateType::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'typical_validity_months']);

            // Format background check data
            $backgroundCheck = [
                'status' => $employee->background_check_status ?? 'not_started',
                'date' => $employee->background_check_date,
                'notes' => $employee->background_check_notes,
                'files' => $employee->background_check_files ?? [],
                'updated_at' => $employee->updated_at,
            ];

            return Inertia::render('Employees/Show', [
                'employee' => $employee,
                'certificates' => $employee->employeeCertificates,
                'certificateTypes' => $certificateTypes,
                'backgroundCheck' => $backgroundCheck,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading employee container: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Could not load employee container: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update employee information
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|unique:employees,nip,' . $employee->id,
            'employee_id' => 'nullable|string|unique:employees,employee_id,' . $employee->id,
            'position' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'hire_date' => 'nullable|date',
        ]);

        try {
            $employee->update([
                'name' => $request->name,
                'nip' => $request->nip ?? $request->employee_id,
                'employee_id' => $request->employee_id ?? $request->nip,
                'position' => $request->position,
                'department_id' => $request->department_id,
                'phone' => $request->phone,
                'email' => $request->email,
                'hire_date' => $request->hire_date ? now()->parse($request->hire_date) : null,
            ]);

            return back()->with('success', 'Employee updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating employee: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not update employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete employee
     */
    public function destroy(Employee $employee)
    {
        try {
            DB::transaction(function () use ($employee) {
                // Delete associated certificates
                $employee->employeeCertificates()->delete();

                // Delete employee files if exist
                $employeeDir = "employees/{$employee->id}";
                if (Storage::disk('private')->exists($employeeDir)) {
                    Storage::disk('private')->deleteDirectory($employeeDir);
                }

                // Delete employee record
                $employee->delete();
            });

            return redirect()->route('employees.index')
                           ->with('success', 'Employee deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not delete employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload background check files
     */
    public function uploadBackgroundCheckFiles(Request $request, Employee $employee)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
            'background_check_status' => 'nullable|string',
            'background_check_notes' => 'nullable|string|max:1000',
        ]);

        try {
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
                'background_check_status' => $request->background_check_status ?? $employee->background_check_status ?? 'pending_review',
                'background_check_notes' => $request->background_check_notes ?? $employee->background_check_notes,
            ]);

            return back()->with('success', 'Background check files uploaded successfully.');

        } catch (\Exception $e) {
            Log::error('Error uploading background check files: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not upload files: ' . $e->getMessage()]);
        }
    }

    /**
     * Store certificate
     */
    public function storeCertificate(Request $request, Employee $employee)
    {
        $request->validate([
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'certificate_number' => 'required|string|unique:employee_certificates,certificate_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request, $employee) {
                // Create certificate record
                $certificate = EmployeeCertificate::create([
                    'employee_id' => $employee->id,
                    'certificate_type_id' => $request->certificate_type_id,
                    'certificate_number' => $request->certificate_number,
                    'issue_date' => $request->issue_date,
                    'expiry_date' => $request->expiry_date,
                    'status' => $this->determineCertificateStatus($request->issue_date, $request->expiry_date),
                    'notes' => $request->notes,
                    'created_by' => auth()->id(),
                ]);

                // Handle file uploads if present
                if ($request->hasFile('files')) {
                    $uploadedFiles = [];

                    foreach ($request->file('files') as $file) {
                        $uploadedFile = $this->storeEmployeeFile(
                            $employee,
                            $file,
                            'certificates',
                            $certificate->id
                        );
                        $uploadedFiles[] = $uploadedFile;
                    }

                    $certificate->update(['files' => $uploadedFiles]);
                }
            });

            return back()->with('success', 'Certificate added successfully.');

        } catch (\Exception $e) {
            Log::error('Error creating certificate: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not create certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Store employee file (background check or certificate)
     */
    private function storeEmployeeFile(Employee $employee, $file, $type, $certificateId = null)
    {
        $directory = "employees/{$employee->id}/{$type}";
        if ($certificateId) {
            $directory .= "/{$certificateId}";
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs($directory, $filename, 'private');

        return [
            'name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toISOString(),
        ];
    }

    /**
     * Determine certificate status based on dates
     */
    private function determineCertificateStatus($issueDate, $expiryDate = null)
    {
        if (!$expiryDate) {
            return 'active'; // No expiry date means permanent certificate
        }

        $now = now();
        $expiry = now()->parse($expiryDate);
        $warningDate = $expiry->copy()->subDays(30); // 30 days warning

        if ($now->gt($expiry)) {
            return 'expired';
        } elseif ($now->gte($warningDate)) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }

    /**
     * Download background check file
     */
    public function downloadBackgroundCheckFile(Employee $employee, $fileIndex)
    {
        try {
            $files = $employee->background_check_files ?? [];

            if (!isset($files[$fileIndex])) {
                abort(404, 'File not found');
            }

            $file = $files[$fileIndex];
            $filePath = $file['path'];

            if (!Storage::disk('private')->exists($filePath)) {
                abort(404, 'File not found');
            }

            return Storage::disk('private')->download($filePath, $file['name']);

        } catch (\Exception $e) {
            Log::error('Error downloading background check file: ' . $e->getMessage());
            abort(404, 'File not found');
        }
    }

    /**
     * Download certificate file
     */
    public function downloadCertificateFile(EmployeeCertificate $certificate, $fileIndex)
    {
        try {
            $files = $certificate->files ?? [];

            if (!isset($files[$fileIndex])) {
                abort(404, 'File not found');
            }

            $file = $files[$fileIndex];
            $filePath = $file['path'];

            if (!Storage::disk('private')->exists($filePath)) {
                abort(404, 'File not found');
            }

            return Storage::disk('private')->download($filePath, $file['name']);

        } catch (\Exception $e) {
            Log::error('Error downloading certificate file: ' . $e->getMessage());
            abort(404, 'File not found');
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
                  ->orWhere('employee_id', 'LIKE', "%{$query}%")
                  ->orWhere('position', 'LIKE', "%{$query}%");
            })
            ->with(['department:id,name'])
            ->select(['id', 'name', 'nip', 'employee_id', 'position', 'department_id'])
            ->limit(10)
            ->get();

            return response()->json($employees);

        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Export all containers
     */
    public function exportAll(Request $request)
    {
        try {
            // Simple CSV export
            $employees = Employee::with(['department', 'employeeCertificates'])->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="employee_containers_' . date('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($employees) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'ID', 'Name', 'NIP', 'Position', 'Department',
                    'Certificates Count', 'Background Check Status', 'Created At'
                ]);

                foreach ($employees as $employee) {
                    fputcsv($handle, [
                        $employee->id,
                        $employee->name,
                        $employee->nip ?? $employee->employee_id,
                        $employee->position,
                        $employee->department?->name,
                        $employee->employeeCertificates->count(),
                        $employee->background_check_status ?? 'Not Started',
                        $employee->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting containers: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not export containers: ' . $e->getMessage()]);
        }
    }
}
