<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeContainerController extends Controller
{
    /**
     * Display grid view of all employee containers with statistics
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'employeeCertificates.certificateType'])
                        ->withCount([
                            'employeeCertificates',
                            'employeeCertificates as active_certificates_count' => function ($query) {
                                $query->where('status', 'active');
                            },
                            'employeeCertificates as expired_certificates_count' => function ($query) {
                                $query->where('status', 'expired');
                            },
                            'employeeCertificates as expiring_soon_certificates_count' => function ($query) {
                                $query->where('status', 'expiring_soon');
                            }
                        ]);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('department_id')) {
            $query->byDepartment($request->department_id);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'has_expired':
                    $query->whereHas('employeeCertificates', function ($q) {
                        $q->where('status', 'expired');
                    });
                    break;
                case 'expiring_soon':
                    $query->whereHas('employeeCertificates', function ($q) {
                        $q->where('status', 'expiring_soon');
                    });
                    break;
                case 'active':
                    $query->whereHas('employeeCertificates', function ($q) {
                        $q->where('status', 'active');
                    });
                    break;
                case 'no_certificates':
                    $query->withoutCertificates();
                    break;
                case 'no_background_check':
                    $query->withoutBackgroundCheck();
                    break;
            }
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        switch ($sortBy) {
            case 'certificates_count':
                $query->orderBy('employee_certificates_count', $sortDirection);
                break;
            case 'department':
                $query->join('departments', 'employees.department_id', '=', 'departments.id')
                      ->orderBy('departments.name', $sortDirection);
                break;
            case 'status':
                $query->orderBy('status', $sortDirection);
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
        }

        $employees = $query->paginate(24); // Grid view with 24 items per page

        // Transform data for container view
        $containers = $employees->getCollection()->map(function ($employee) {
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'position' => $employee->position,
                'department' => $employee->department?->name ?? 'No Department',
                'department_id' => $employee->department_id,
                'hire_date' => $employee->hire_date?->format('d M Y'),
                'email' => $employee->email,
                'phone' => $employee->phone,
                'status' => $employee->status,
                
                // Container specific data
                'container_status' => $employee->getContainerStatus(),
                'background_check_status' => $employee->getBackgroundCheckStatus(),
                'background_check_files_count' => count($employee->background_check_files ?? []),
                
                // Certificate statistics
                'certificates' => [
                    'total' => $employee->employee_certificates_count,
                    'active' => $employee->active_certificates_count,
                    'expired' => $employee->expired_certificates_count,
                    'expiring_soon' => $employee->expiring_soon_certificates_count,
                ],
                
                // Quick actions
                'has_files' => count($employee->background_check_files ?? []) > 0 || $employee->employee_certificates_count > 0,
                'last_updated' => $employee->updated_at->format('d M Y H:i'),
                'container_url' => route('employee-containers.show', $employee->id),
            ];
        });

        $employees->setCollection($containers);

        // Get overall statistics for dashboard
        $statistics = $this->getContainerStatistics();

        // Get departments for filter
        $departments = Department::where('is_active', true)
                                ->orderBy('name')
                                ->get(['id', 'name']);

        return Inertia::render('Employees/Container', [
            'containers' => $employees,
            'statistics' => $statistics,
            'departments' => $departments,
            'filters' => [
                'search' => $request->search,
                'department_id' => $request->department_id,
                'status' => $request->status,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ]
        ]);
    }

    /**
     * Display individual employee container with all details
     */
    public function show(Employee $employee)
    {
        // Load all necessary relationships
        $employee->load([
            'department',
            'employeeCertificates' => function ($query) {
                $query->with('certificateType')->orderBy('issue_date', 'desc');
            }
        ]);

        // Get complete container data
        $containerData = $employee->getContainerData();

        // Get available certificate types for adding new certificates
        $certificateTypes = CertificateType::active()
                                          ->orderBy('name')
                                          ->get(['id', 'name', 'code', 'category', 'validity_months', 'is_mandatory']);

        // Get recent activity (last 10 certificate updates)
        $recentActivity = EmployeeCertificate::where('employee_id', $employee->id)
                                           ->with('certificateType')
                                           ->orderBy('updated_at', 'desc')
                                           ->limit(10)
                                           ->get()
                                           ->map(function ($cert) {
                                               return [
                                                   'id' => $cert->id,
                                                   'type' => 'certificate',
                                                   'action' => 'Updated',
                                                   'description' => "Certificate: {$cert->certificateType->name}",
                                                   'status' => $cert->status,
                                                   'date' => $cert->updated_at->format('d M Y H:i'),
                                                   'files_count' => count($cert->certificate_files ?? [])
                                               ];
                                           });

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'container' => $containerData,
            'certificateTypes' => $certificateTypes,
            'recentActivity' => $recentActivity,
            'breadcrumbs' => [
                ['name' => 'Containers', 'url' => route('employee-containers.index')],
                ['name' => $employee->name, 'current' => true]
            ]
        ]);
    }

    /**
     * Upload background check files
     */
    public function uploadBackgroundCheckFiles(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:5',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max per file
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:pending_review,cleared,requires_follow_up,rejected'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'File validation failed.');
        }

        try {
            DB::beginTransaction();

            $uploadedFiles = [];
            $existingFiles = $employee->background_check_files ?? [];

            foreach ($request->file('files') as $file) {
                $filename = time() . '_' . Str::random(8) . '_' . $file->getClientOriginalName();
                $path = $file->storeAs(
                    "containers/employee-{$employee->id}/background-checks",
                    $filename,
                    'private'
                );

                $uploadedFiles[] = [
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }

            // Merge with existing files
            $allFiles = array_merge($existingFiles, $uploadedFiles);

            // Update employee background check data
            $employee->update([
                'background_check_files' => $allFiles,
                'background_check_status' => $request->status,
                'background_check_date' => now(),
                'background_check_notes' => $request->notes
            ]);

            DB::commit();

            return back()->with('success', 'Background check files uploaded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files on error
            foreach ($uploadedFiles as $fileData) {
                Storage::disk('private')->delete($fileData['path']);
            }

            return back()->with('error', 'Failed to upload files: ' . $e->getMessage());
        }
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

        $fileData = $files[$fileIndex];
        $path = $fileData['path'];

        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File not found on storage');
        }

        return Storage::disk('private')->download($path, $fileData['original_name']);
    }

    /**
     * Update background check status and notes
     */
    public function updateBackgroundCheck(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:not_started,in_progress,pending_review,cleared,requires_follow_up,expired,rejected',
            'notes' => 'nullable|string|max:1000',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $employee->update([
            'background_check_status' => $request->status,
            'background_check_notes' => $request->notes,
            'background_check_date' => $request->date ? Carbon::parse($request->date) : $employee->background_check_date
        ]);

        return back()->with('success', 'Background check updated successfully.');
    }

    /**
     * Delete background check file
     */
    public function deleteBackgroundCheckFile(Employee $employee, $fileIndex)
    {
        $files = $employee->background_check_files ?? [];

        if (!isset($files[$fileIndex])) {
            return back()->with('error', 'File not found.');
        }

        $fileData = $files[$fileIndex];
        
        try {
            // Delete from storage
            if (Storage::disk('private')->exists($fileData['path'])) {
                Storage::disk('private')->delete($fileData['path']);
            }

            // Remove from array
            unset($files[$fileIndex]);
            $files = array_values($files); // Re-index array

            // Update employee
            $employee->update(['background_check_files' => $files]);

            return back()->with('success', 'File deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }

    /**
     * Store new certificate for employee
     */
    public function storeCertificate(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'certificate_number' => 'nullable|string|max:100',
            'issuer' => 'nullable|string|max:255',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'score' => 'nullable|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'files' => 'nullable|array|max:3',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            // Handle file uploads
            $certificateFiles = [];
            if ($request->hasFile('files')) {
                $certificateType = CertificateType::findOrFail($request->certificate_type_id);
                
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . Str::random(8) . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs(
                        "containers/employee-{$employee->id}/certificates/{$certificateType->code}",
                        $filename,
                        'private'
                    );

                    $certificateFiles[] = [
                        'filename' => $filename,
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
            }

            // Create certificate
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
                'training_hours' => $request->training_hours,
                'cost' => $request->cost,
                'score' => $request->score,
                'location' => $request->location,
                'instructor_name' => $request->instructor_name,
                'notes' => $request->notes,
                'certificate_files' => $certificateFiles,
                'created_by_id' => auth()->id()
            ]);

            // Update status based on dates
            $certificate->updateStatusBasedOnDates();

            DB::commit();

            return back()->with('success', 'Certificate added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded files on error
            foreach ($certificateFiles as $fileData) {
                Storage::disk('private')->delete($fileData['path']);
            }

            return back()->with('error', 'Failed to add certificate: ' . $e->getMessage());
        }
    }

    /**
     * Update certificate information
     */
    public function updateCertificate(Request $request, Employee $employee, EmployeeCertificate $certificate)
    {
        // Verify certificate belongs to employee
        if ($certificate->employee_id !== $employee->id) {
            abort(403, 'Certificate does not belong to this employee.');
        }

        $validator = Validator::make($request->all(), [
            'certificate_number' => 'nullable|string|max:100',
            'issuer' => 'nullable|string|max:255',
            'training_provider' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'score' => 'nullable|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'files' => 'nullable|array|max:3',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            // Handle new file uploads
            $existingFiles = $certificate->certificate_files ?? [];
            $newFiles = [];

            if ($request->hasFile('files')) {
                $certificateType = $certificate->certificateType;
                
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . Str::random(8) . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs(
                        "containers/employee-{$employee->id}/certificates/{$certificateType->code}",
                        $filename,
                        'private'
                    );

                    $newFiles[] = [
                        'filename' => $filename,
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
            }

            // Merge files
            $allFiles = array_merge($existingFiles, $newFiles);

            // Update certificate
            $certificate->update([
                'certificate_number' => $request->certificate_number,
                'issuer' => $request->issuer,
                'training_provider' => $request->training_provider,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'completion_date' => $request->completion_date,
                'training_date' => $request->training_date,
                'training_hours' => $request->training_hours,
                'cost' => $request->cost,
                'score' => $request->score,
                'location' => $request->location,
                'instructor_name' => $request->instructor_name,
                'notes' => $request->notes,
                'certificate_files' => $allFiles,
                'updated_by_id' => auth()->id()
            ]);

            // Update status based on dates
            $certificate->updateStatusBasedOnDates();

            DB::commit();

            return back()->with('success', 'Certificate updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded files on error
            foreach ($newFiles as $fileData) {
                Storage::disk('private')->delete($fileData['path']);
            }

            return back()->with('error', 'Failed to update certificate: ' . $e->getMessage());
        }
    }

    /**
     * Delete certificate
     */
    public function deleteCertificate(Employee $employee, EmployeeCertificate $certificate)
    {
        // Verify certificate belongs to employee
        if ($certificate->employee_id !== $employee->id) {
            abort(403, 'Certificate does not belong to this employee.');
        }

        try {
            // Delete associated files from storage
            $files = $certificate->certificate_files ?? [];
            foreach ($files as $fileData) {
                if (Storage::disk('private')->exists($fileData['path'])) {
                    Storage::disk('private')->delete($fileData['path']);
                }
            }

            // Delete certificate
            $certificate->delete();

            return back()->with('success', 'Certificate deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete certificate: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate file
     */
    public function downloadCertificateFile(Employee $employee, EmployeeCertificate $certificate, $fileIndex)
    {
        // Verify certificate belongs to employee
        if ($certificate->employee_id !== $employee->id) {
            abort(403, 'Certificate does not belong to this employee.');
        }

        $files = $certificate->certificate_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $fileData = $files[$fileIndex];
        $path = $fileData['path'];

        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File not found on storage');
        }

        return Storage::disk('private')->download($path, $fileData['original_name']);
    }

    /**
     * Bulk update certificate statuses
     */
    public function bulkUpdateCertificateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:update_all_statuses,mark_expired,mark_expiring_soon',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $query = EmployeeCertificate::query();

            // Apply employee filter if provided
            if ($request->filled('employee_ids')) {
                $query->whereIn('employee_id', $request->employee_ids);
            }

            $certificates = $query->get();
            $updated = 0;

            foreach ($certificates as $certificate) {
                switch ($request->action) {
                    case 'update_all_statuses':
                        $certificate->updateStatusBasedOnDates();
                        $updated++;
                        break;
                    
                    case 'mark_expired':
                        if ($certificate->expiry_date && $certificate->expiry_date->isPast()) {
                            $certificate->update(['status' => 'expired']);
                            $updated++;
                        }
                        break;
                    
                    case 'mark_expiring_soon':
                        if ($certificate->expiry_date) {
                            $warningDays = $certificate->certificateType->warning_days ?? 90;
                            $warningDate = $certificate->expiry_date->subDays($warningDays);
                            
                            if (now()->greaterThanOrEqualTo($warningDate) && !$certificate->expiry_date->isPast()) {
                                $certificate->update(['status' => 'expiring_soon']);
                                $updated++;
                            }
                        }
                        break;
                }
            }

            DB::commit();

            return back()->with('success', "Successfully updated {$updated} certificates.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to bulk update certificates: ' . $e->getMessage());
        }
    }

    /**
     * Search containers
     */
    public function search(Request $request)
    {
        $query = Employee::with(['department', 'employeeCertificates.certificateType']);

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        $employees = $query->limit(10)->get();

        $results = $employees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'position' => $employee->position,
                'department' => $employee->department?->name,
                'certificates_count' => $employee->employeeCertificates->count(),
                'url' => route('employee-containers.show', $employee->id)
            ];
        });

        return response()->json($results);
    }

    /**
     * Export container data
     */
    public function exportContainers(Request $request)
    {
        // This method would implement Excel/CSV export functionality
        // For now, return a basic implementation
        
        $query = Employee::with(['department', 'employeeCertificates.certificateType']);

        // Apply same filters as index method
        if ($request->filled('department_id')) {
            $query->byDepartment($request->department_id);
        }

        if ($request->filled('status')) {
            // Apply status filters similar to index method
        }

        $employees = $query->get();

        // Would typically use Laravel Excel here
        return response()->json([
            'message' => 'Export functionality would be implemented here',
            'count' => $employees->count()
        ]);
    }

    /**
     * Get container statistics for dashboard
     */
    public function getContainerStatistics()
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();

        $certificateStats = EmployeeCertificate::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN status = "expiring_soon" THEN 1 ELSE 0 END) as expiring_soon,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
        ')->first();

        $backgroundCheckStats = Employee::selectRaw('
            SUM(CASE WHEN background_check_status = "cleared" THEN 1 ELSE 0 END) as cleared,
            SUM(CASE WHEN background_check_status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN background_check_status IS NULL OR background_check_status = "not_started" THEN 1 ELSE 0 END) as not_started
        ')->first();

        return [
            'employees' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'with_certificates' => Employee::withCertificates()->count(),
                'without_certificates' => Employee::withoutCertificates()->count(),
            ],
            'certificates' => [
                'total' => $certificateStats->total ?? 0,
                'active' => $certificateStats->active ?? 0,
                'expired' => $certificateStats->expired ?? 0,
                'expiring_soon' => $certificateStats->expiring_soon ?? 0,
                'pending' => $certificateStats->pending ?? 0,
            ],
            'background_checks' => [
                'cleared' => $backgroundCheckStats->cleared ?? 0,
                'in_progress' => $backgroundCheckStats->in_progress ?? 0,
                'not_started' => $backgroundCheckStats->not_started ?? 0,
            ],
            'compliance_rate' => $totalEmployees > 0 
                ? round((($certificateStats->active ?? 0) / $totalEmployees) * 100, 1)
                : 0
        ];
    }

    /**
     * Get quick search results for API
     */
    public function quickSearch(Request $request)
    {
        if (!$request->filled('q')) {
            return response()->json([]);
        }

        $employees = Employee::search($request->q)
                            ->with('department')
                            ->limit(5)
                            ->get();

        $results = $employees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'text' => "{$employee->name} ({$employee->employee_id})",
                'department' => $employee->department?->name,
                'url' => route('employee-containers.show', $employee->id)
            ];
        });

        return response()->json($results);
    }

    /**
     * Get certificate alerts for notifications
     */
    public function getCertificateAlerts()
    {
        $expiring = EmployeeCertificate::with(['employee', 'certificateType'])
                                     ->where('status', 'expiring_soon')
                                     ->orderBy('expiry_date', 'asc')
                                     ->limit(10)
                                     ->get();

        $expired = EmployeeCertificate::with(['employee', 'certificateType'])
                                    ->where('status', 'expired')
                                    ->orderBy('expiry_date', 'desc')
                                    ->limit(10)
                                    ->get();

        return response()->json([
            'expiring' => $expiring->map(function ($cert) {
                return [
                    'employee' => $cert->employee->name,
                    'certificate_type' => $cert->certificateType->name,
                    'expiry_date' => $cert->expiry_date->format('d M Y'),
                    'days_until_expiry' => $cert->getDaysUntilExpiry(),
                    'url' => route('employee-containers.show', $cert->employee_id)
                ];
            }),
            'expired' => $expired->map(function ($cert) {
                return [
                    'employee' => $cert->employee->name,
                    'certificate_type' => $cert->certificateType->name,
                    'expiry_date' => $cert->expiry_date->format('d M Y'),
                    'days_overdue' => abs($cert->getDaysUntilExpiry()),
                    'url' => route('employee-containers.show', $cert->employee_id)
                ];
            })
        ]);
    }
}