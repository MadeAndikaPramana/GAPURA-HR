<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingProvider;
use App\Models\TrainingRecord;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class CertificateController extends Controller
{
    /**
     * Display certificates listing with filters and search
     */
    public function index(Request $request): Response
    {
        $query = Certificate::with([
            'employee:id,name,nip,department_id',
            'employee.department:id,name,code',
            'trainingType:id,name,code,category',
            'trainingProvider:id,name,code'
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('certificate_number', 'like', "%{$searchTerm}%")
                  ->orWhere('issuer_name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('employee', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('nip', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('trainingType', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expired();
            } elseif ($request->status === 'expiring') {
                $query->expiring(30);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->byDepartment($request->department_id);
        }

        // Training type filter
        if ($request->filled('training_type_id')) {
            $query->byTrainingType($request->training_type_id);
        }

        // Training provider filter
        if ($request->filled('training_provider_id')) {
            $query->where('training_provider_id', $request->training_provider_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'issue_date');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['issue_date', 'expiry_date', 'certificate_number', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $certificates = $query->paginate($request->get('per_page', 15))
                             ->withQueryString();

        // Get filter options
        $departments = Department::select('id', 'name', 'code')->orderBy('name')->get();
        $trainingTypes = TrainingType::select('id', 'name', 'code', 'category')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get();
        $trainingProviders = TrainingProvider::select('id', 'name', 'code')
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get();

        // Certificate statistics
        $stats = $this->getCertificateStats();

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'department_id' => $request->department_id,
                'training_type_id' => $request->training_type_id,
                'training_provider_id' => $request->training_provider_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'filterOptions' => [
                'departments' => $departments,
                'trainingTypes' => $trainingTypes,
                'trainingProviders' => $trainingProviders,
            ],
            'stats' => $stats
        ]);
    }

    /**
     * Show certificate details
     */
    public function show(Certificate $certificate): Response
    {
        $certificate->load([
            'employee:id,name,nip,email,phone,department_id,position',
            'employee.department:id,name,code',
            'trainingType:id,name,code,category,description,validity_period_months',
            'trainingProvider:id,name,code,contact_person,email,phone',
            'trainingRecord:id,training_date,completion_date,score,passing_score,training_hours,cost,location,instructor_name,notes',
            'parentCertificate:id,certificate_number,issue_date,status',
            'renewals:id,certificate_number,issue_date,status,expiry_date',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);

        // Get related certificates for same employee and training type
        $relatedCertificates = Certificate::where('employee_id', $certificate->employee_id)
                                        ->where('training_type_id', $certificate->training_type_id)
                                        ->where('id', '!=', $certificate->id)
                                        ->with('trainingProvider:id,name')
                                        ->orderBy('issue_date', 'desc')
                                        ->limit(5)
                                        ->get();

        return Inertia::render('Certificates/Show', [
            'certificate' => $certificate,
            'relatedCertificates' => $relatedCertificates
        ]);
    }

    /**
     * Show create certificate form
     */
    public function create(Request $request): Response
    {
        // Get options for form
        $employees = Employee::select('id', 'name', 'nip', 'department_id')
                            ->with('department:id,name')
                            ->where('status', 'active')
                            ->orderBy('name')
                            ->get();

        $trainingTypes = TrainingType::select('id', 'name', 'code', 'category', 'validity_period_months')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get();

        $trainingProviders = TrainingProvider::select('id', 'name', 'code')
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get();

        // If training_record_id is provided, pre-populate from training record
        $prePopulateData = null;
        if ($request->filled('training_record_id')) {
            $trainingRecord = TrainingRecord::with([
                'employee:id,name,nip',
                'trainingType:id,name',
                'trainingProvider:id,name'
            ])->find($request->training_record_id);

            if ($trainingRecord) {
                $prePopulateData = [
                    'training_record_id' => $trainingRecord->id,
                    'employee_id' => $trainingRecord->employee_id,
                    'training_type_id' => $trainingRecord->training_type_id,
                    'training_provider_id' => $trainingRecord->training_provider_id,
                    'issue_date' => $trainingRecord->completion_date?->format('Y-m-d') ?? $trainingRecord->issue_date?->format('Y-m-d'),
                    'expiry_date' => $trainingRecord->expiry_date?->format('Y-m-d'),
                    'score' => $trainingRecord->score,
                    'passing_score' => $trainingRecord->passing_score,
                ];
            }
        }

        return Inertia::render('Certificates/Create', [
            'employees' => $employees,
            'trainingTypes' => $trainingTypes,
            'trainingProviders' => $trainingProviders,
            'prePopulateData' => $prePopulateData
        ]);
    }

    /**
     * Store new certificate
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_record_id' => 'nullable|exists:training_records,id',
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'training_provider_id' => 'nullable|exists:training_providers,id',
            'certificate_type' => 'required|in:completion,competency,compliance',
            'issuer_name' => 'required|string|max:255',
            'issuer_title' => 'nullable|string|max:255',
            'issuer_organization' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'score' => 'nullable|numeric|min:0|max:100',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'achievements' => 'nullable|string',
            'remarks' => 'nullable|string',
            'is_renewable' => 'boolean',
            'is_compliance_required' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $certificate = Certificate::create(array_merge($validated, [
                'issued_at' => now(),
                'status' => 'issued', // Auto-issue for now
                'verification_status' => 'pending',
                'created_by_id' => auth()->id(),
            ]));

            // Update training record if provided
            if ($request->training_record_id) {
                TrainingRecord::find($request->training_record_id)->update([
                    'certificate_number' => $certificate->certificate_number,
                    'status' => 'completed'
                ]);
            }

            DB::commit();

            return redirect()
                ->route('certificates.show', $certificate)
                ->with('success', 'Certificate created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit certificate form
     */
    public function edit(Certificate $certificate): Response
    {
        $certificate->load([
            'employee:id,name,nip',
            'trainingType:id,name',
            'trainingProvider:id,name',
            'trainingRecord:id'
        ]);

        $employees = Employee::select('id', 'name', 'nip', 'department_id')
                            ->with('department:id,name')
                            ->where('status', 'active')
                            ->orderBy('name')
                            ->get();

        $trainingTypes = TrainingType::select('id', 'name', 'code', 'category')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get();

        $trainingProviders = TrainingProvider::select('id', 'name', 'code')
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get();

        return Inertia::render('Certificates/Edit', [
            'certificate' => $certificate,
            'employees' => $employees,
            'trainingTypes' => $trainingTypes,
            'trainingProviders' => $trainingProviders
        ]);
    }

    /**
     * Update certificate
     */
    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        $validated = $request->validate([
            'certificate_type' => 'required|in:completion,competency,compliance',
            'issuer_name' => 'required|string|max:255',
            'issuer_title' => 'nullable|string|max:255',
            'issuer_organization' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'status' => 'required|in:draft,issued,revoked,expired,renewed',
            'verification_status' => 'required|in:pending,verified,invalid,under_review',
            'score' => 'nullable|numeric|min:0|max:100',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'achievements' => 'nullable|string',
            'remarks' => 'nullable|string',
            'is_renewable' => 'boolean',
            'is_compliance_required' => 'boolean',
            'compliance_status' => 'required|in:compliant,non_compliant,pending,exempt',
            'notes' => 'nullable|string',
        ]);

        try {
            $certificate->update(array_merge($validated, [
                'updated_by_id' => auth()->id(),
            ]));

            return redirect()
                ->route('certificates.show', $certificate)
                ->with('success', 'Certificate updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete certificate (soft delete)
     */
    public function destroy(Certificate $certificate): RedirectResponse
    {
        try {
            $certificate->delete();

            return redirect()
                ->route('certificates.index')
                ->with('success', 'Certificate deleted successfully.');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete certificate: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions for certificates
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:delete,revoke,verify,export',
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'exists:certificates,id'
        ]);

        $certificates = Certificate::whereIn('id', $request->certificate_ids);
        $count = $certificates->count();

        try {
            switch ($request->action) {
                case 'delete':
                    $certificates->delete();
                    $message = "{$count} certificates deleted successfully.";
                    break;

                case 'revoke':
                    $certificates->update([
                        'status' => 'revoked',
                        'updated_by_id' => auth()->id()
                    ]);
                    $message = "{$count} certificates revoked successfully.";
                    break;

                case 'verify':
                    $certificates->update([
                        'verification_status' => 'verified',
                        'last_verified_at' => now(),
                        'verified_by' => auth()->user()->name,
                        'updated_by_id' => auth()->id()
                    ]);
                    $message = "{$count} certificates verified successfully.";
                    break;

                case 'export':
                    // TODO: Implement export functionality
                    $message = "Export functionality will be implemented soon.";
                    break;
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Bulk action failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get certificate statistics for dashboard
     */
    private function getCertificateStats(): array
    {
        $total = Certificate::count();
        $active = Certificate::active()->count();
        $expired = Certificate::expired()->count();
        $expiring = Certificate::expiring(30)->count();
        $draft = Certificate::where('status', 'draft')->count();
        $revoked = Certificate::where('status', 'revoked')->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiring,
            'draft' => $draft,
            'revoked' => $revoked,
            'compliance_rate' => $total > 0 ? round((($active + $expiring) / $total) * 100, 1) : 0
        ];
    }

    /**
     * Generate certificate PDF
     */
    public function generatePDF(Certificate $certificate)
    {
        // TODO: Implement PDF generation
        return back()->with('info', 'PDF generation will be implemented soon.');
    }

    /**
     * Verify certificate by verification code
     */
    public function verify(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string'
        ]);

        $certificate = Certificate::where('verification_code', $request->verification_code)
                                 ->where('status', 'issued')
                                 ->with([
                                     'employee:id,name,nip',
                                     'trainingType:id,name',
                                     'trainingProvider:id,name'
                                 ])
                                 ->first();

        if (!$certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found or invalid verification code.'
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'certificate_number' => $certificate->certificate_number,
                'employee_name' => $certificate->employee->name,
                'employee_nip' => $certificate->employee->nip,
                'training_type' => $certificate->trainingType->name,
                'issue_date' => $certificate->issue_date->format('Y-m-d'),
                'expiry_date' => $certificate->expiry_date?->format('Y-m-d'),
                'status' => $certificate->status,
                'issuer' => $certificate->issuer_name,
                'is_expired' => $certificate->isExpired()
            ]
        ]);
    }

    /**
     * Public certificate verification page
     */
    public function verifyPublic(string $verificationCode)
    {
        $certificate = Certificate::where('verification_code', $verificationCode)
                                 ->where('status', 'issued')
                                 ->with([
                                     'employee:id,name,nip',
                                     'trainingType:id,name',
                                     'trainingProvider:id,name'
                                 ])
                                 ->first();

        return Inertia::render('Public/CertificateVerification', [
            'certificate' => $certificate,
            'verificationCode' => $verificationCode
        ]);
    }

    /**
     * Export certificates to Excel
     */
    public function exportExcel(Request $request)
    {
        // TODO: Implement Excel export
        return back()->with('info', 'Excel export will be implemented soon.');
    }

    /**
     * Export certificates to PDF
     */
    public function exportPDF(Request $request)
    {
        // TODO: Implement PDF export
        return back()->with('info', 'PDF export will be implemented soon.');
    }

    /**
     * Get compliance report
     */
    public function complianceReport(Request $request)
    {
        // TODO: Implement compliance report
        return back()->with('info', 'Compliance report will be implemented soon.');
    }

    /**
     * Get expiring certificates report
     */
    public function expiringReport(Request $request)
    {
        // TODO: Implement expiring report
        return back()->with('info', 'Expiring certificates report will be implemented soon.');
    }

    /**
     * Get employee certificates
     */
    public function employeeCertificates(Employee $employee)
    {
        $certificates = Certificate::where('employee_id', $employee->id)
                                 ->with(['trainingType:id,name', 'trainingProvider:id,name'])
                                 ->orderBy('issue_date', 'desc')
                                 ->paginate(15);

        return Inertia::render('Certificates/EmployeeCertificates', [
            'employee' => $employee,
            'certificates' => $certificates
        ]);
    }

    /**
     * Get training type certificates
     */
    public function trainingTypeCertificates(TrainingType $trainingType)
    {
        $certificates = Certificate::where('training_type_id', $trainingType->id)
                                 ->with(['employee:id,name', 'trainingProvider:id,name'])
                                 ->orderBy('issue_date', 'desc')
                                 ->paginate(15);

        return Inertia::render('Certificates/TrainingTypeCertificates', [
            'trainingType' => $trainingType,
            'certificates' => $certificates
        ]);
    }

    /**
     * Get department certificates
     */
    public function departmentCertificates(Department $department)
    {
        $certificates = Certificate::whereHas('employee', function($query) use ($department) {
                                     $query->where('department_id', $department->id);
                                   })
                                 ->with(['employee:id,name', 'trainingType:id,name', 'trainingProvider:id,name'])
                                 ->orderBy('issue_date', 'desc')
                                 ->paginate(15);

        return Inertia::render('Certificates/DepartmentCertificates', [
            'department' => $department,
            'certificates' => $certificates
        ]);
    }

    /**
     * Create renewal certificate
     */
    public function createRenewal(Certificate $certificate)
    {
        if (!$certificate->canBeRenewed()) {
            return back()->withErrors(['error' => 'Certificate cannot be renewed at this time.']);
        }

        try {
            $renewal = $certificate->createRenewal();

            return redirect()
                ->route('certificates.show', $renewal)
                ->with('success', 'Certificate renewal created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create renewal: ' . $e->getMessage()]);
        }
    }

    /**
     * Revoke certificate
     */
    public function revoke(Certificate $certificate)
    {
        try {
            $certificate->revoke('Manual revocation by admin');

            return back()->with('success', 'Certificate revoked successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to revoke certificate: ' . $e->getMessage()]);
        }
    }
}
