<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Services\CertificateService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    protected $certificateService;
    protected $notificationService;

    public function __construct(CertificateService $certificateService, NotificationService $notificationService)
    {
        $this->certificateService = $certificateService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display certificate listing with advanced filtering
     */
    public function index(Request $request)
    {
        $query = Certificate::with([
            'trainingRecord.employee.department',
            'trainingRecord.trainingType.category',
            'verifiedBy'
        ]);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'expiring_soon':
                    $query->expiringSoon(30);
                    break;
                case 'expiring':
                    $query->expiringSoon(90);
                    break;
            }
        }

        // Department filter
        if ($request->has('department') && $request->department) {
            $query->whereHas('trainingRecord.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }

        // Training type filter
        if ($request->has('training_type') && $request->training_type) {
            $query->whereHas('trainingRecord', function ($q) use ($request) {
                $q->where('training_type_id', $request->training_type);
            });
        }

        // Verification status filter
        if ($request->has('verified') && $request->verified !== '') {
            $query->where('is_verified', $request->boolean('verified'));
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Sort options
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['created_at', 'issue_date', 'expiry_date', 'certificate_number'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $certificates = $query->paginate(20)->withQueryString();

        // Get filter options
        $departments = \App\Models\Department::all(['id', 'name']);
        $trainingTypes = TrainingType::all(['id', 'name']);

        // Get analytics
        $analytics = Certificate::getCertificateAnalytics();

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
            'departments' => $departments,
            'trainingTypes' => $trainingTypes,
            'analytics' => $analytics,
            'filters' => $request->only([
                'search', 'status', 'department', 'training_type',
                'verified', 'date_from', 'date_to', 'sort', 'direction'
            ])
        ]);
    }

    /**
     * Show certificate details
     */
    public function show(Certificate $certificate)
    {
        $certificate->load([
            'trainingRecord.employee.department',
            'trainingRecord.trainingType.category',
            'trainingRecord.trainingProvider',
            'verifiedBy'
        ]);

        return Inertia::render('Certificates/Show', [
            'certificate' => $certificate,
            'verificationUrl' => $certificate->verification_url,
            'downloadUrl' => $certificate->getDownloadUrl(),
            'renewalRecommendationDate' => $certificate->getRenewalRecommendationDate()
        ]);
    }

    /**
     * Show form for creating new certificate
     */
    public function create(Request $request)
    {
        $trainingRecordId = $request->get('training_record_id');
        $trainingRecord = null;

        if ($trainingRecordId) {
            $trainingRecord = TrainingRecord::with([
                'employee.department',
                'trainingType',
                'trainingProvider'
            ])->findOrFail($trainingRecordId);
        }

        $trainingRecords = TrainingRecord::with(['employee', 'trainingType'])
            ->where('status', 'completed')
            ->whereDoesntHave('certificates')
            ->get();

        return Inertia::render('Certificates/Create', [
            'trainingRecord' => $trainingRecord,
            'trainingRecords' => $trainingRecords
        ]);
    }

    /**
     * Store new certificate
     */
    public function store(Request $request)
    {
        $request->validate([
            'training_record_id' => 'required|exists:training_records,id',
            'certificate_number' => 'nullable|string|max:100|unique:certificates',
            'issued_by' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'notes' => 'nullable|string'
        ]);

        $trainingRecord = TrainingRecord::findOrFail($request->training_record_id);

        // Auto-generate certificate number if not provided
        $certificateNumber = $request->certificate_number ?:
            Certificate::generateCertificateNumber($trainingRecord->trainingType->code);

        // Calculate expiry date if not provided
        $expiryDate = $request->expiry_date;
        if (!$expiryDate && $trainingRecord->trainingType->validity_months) {
            $expiryDate = now()->addMonths($trainingRecord->trainingType->validity_months);
        }

        $certificateData = [
            'training_record_id' => $request->training_record_id,
            'certificate_number' => $certificateNumber,
            'issued_by' => $request->issued_by,
            'issue_date' => $request->issue_date,
            'expiry_date' => $expiryDate,
            'notes' => $request->notes,
            'is_verified' => true, // Auto-verify internal certificates
            'verification_date' => now(),
            'verified_by_id' => Auth::id()
        ];

        // Handle file upload
        if ($request->hasFile('certificate_file')) {
            $certificateData['certificate_file_path'] = $this->certificateService
                ->storeCertificateFile($request->file('certificate_file'), $certificateNumber);
        }

        $certificate = Certificate::create($certificateData);

        // Generate QR code
        $this->certificateService->generateQrCode($certificate);

        // Update training record status
        $trainingRecord->update([
            'status' => 'completed',
            'completion_date' => $request->issue_date
        ]);

        // Send notification to employee
        $this->notificationService->sendCertificateIssuedNotification($certificate);

        return redirect()->route('certificates.show', $certificate)
            ->with('success', 'Certificate created successfully.');
    }

    /**
     * Show form for editing certificate
     */
    public function edit(Certificate $certificate)
    {
        $certificate->load([
            'trainingRecord.employee.department',
            'trainingRecord.trainingType'
        ]);

        return Inertia::render('Certificates/Edit', [
            'certificate' => $certificate
        ]);
    }

    /**
     * Update certificate
     */
    public function update(Request $request, Certificate $certificate)
    {
        $request->validate([
            'certificate_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('certificates')->ignore($certificate->id)
            ],
            'issued_by' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string',
            'is_verified' => 'boolean'
        ]);

        $updateData = $request->only([
            'certificate_number',
            'issued_by',
            'issue_date',
            'expiry_date',
            'notes',
            'is_verified'
        ]);

        // Handle file upload
        if ($request->hasFile('certificate_file')) {
            // Delete old file if exists
            if ($certificate->certificate_file_path) {
                Storage::delete($certificate->certificate_file_path);
            }

            $updateData['certificate_file_path'] = $this->certificateService
                ->storeCertificateFile($request->file('certificate_file'), $certificate->certificate_number);
        }

        // Update verification details if marking as verified
        if ($request->is_verified && !$certificate->is_verified) {
            $updateData['verification_date'] = now();
            $updateData['verified_by_id'] = Auth::id();
        }

        $certificate->update($updateData);

        return redirect()->route('certificates.show', $certificate)
            ->with('success', 'Certificate updated successfully.');
    }

    /**
     * Delete certificate
     */
    public function destroy(Certificate $certificate)
    {
        // Delete associated files
        if ($certificate->certificate_file_path) {
            Storage::delete($certificate->certificate_file_path);
        }
        if ($certificate->qr_code_path) {
            Storage::delete($certificate->qr_code_path);
        }

        $certificate->delete();

        return redirect()->route('certificates.index')
            ->with('success', 'Certificate deleted successfully.');
    }

    /**
     * Download certificate file
     */
    public function download(Certificate $certificate)
    {
        if (!$certificate->certificate_file_path || !Storage::exists($certificate->certificate_file_path)) {
            abort(404, 'Certificate file not found.');
        }

        $filename = $certificate->certificate_number . '_' .
                   $certificate->trainingRecord->employee->name . '.pdf';

        return Storage::download($certificate->certificate_file_path, $filename);
    }

    /**
     * Verify certificate by verification code
     */
    public function verify($verificationCode)
    {
        $certificate = Certificate::where('verification_code', $verificationCode)
            ->with([
                'trainingRecord.employee.department',
                'trainingRecord.trainingType.category',
                'trainingRecord.trainingProvider'
            ])
            ->first();

        if (!$certificate) {
            return Inertia::render('Certificates/VerificationResult', [
                'success' => false,
                'message' => 'Certificate not found or invalid verification code.'
            ]);
        }

        return Inertia::render('Certificates/VerificationResult', [
            'success' => true,
            'certificate' => $certificate,
            'message' => 'Certificate verified successfully.'
        ]);
    }

    /**
     * Bulk operations on certificates
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:verify,unverify,delete,export',
            'certificate_ids' => 'required|array',
            'certificate_ids.*' => 'exists:certificates,id'
        ]);

        $certificates = Certificate::whereIn('id', $request->certificate_ids);

        switch ($request->action) {
            case 'verify':
                $certificates->update([
                    'is_verified' => true,
                    'verification_date' => now(),
                    'verified_by_id' => Auth::id()
                ]);
                $message = 'Certificates verified successfully.';
                break;

            case 'unverify':
                $certificates->update([
                    'is_verified' => false,
                    'verification_date' => null,
                    'verified_by_id' => null
                ]);
                $message = 'Certificates unverified successfully.';
                break;

            case 'delete':
                foreach ($certificates->get() as $certificate) {
                    // Delete files
                    if ($certificate->certificate_file_path) {
                        Storage::delete($certificate->certificate_file_path);
                    }
                    if ($certificate->qr_code_path) {
                        Storage::delete($certificate->qr_code_path);
                    }
                }
                $certificates->delete();
                $message = 'Certificates deleted successfully.';
                break;

            case 'export':
                return $this->exportCertificates($request->certificate_ids);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Export certificates to Excel
     */
    public function exportCertificates($certificateIds = null)
    {
        $query = Certificate::with([
            'trainingRecord.employee.department',
            'trainingRecord.trainingType.category',
            'trainingRecord.trainingProvider'
        ]);

        if ($certificateIds) {
            $query->whereIn('id', $certificateIds);
        }

        $certificates = $query->get();

        return $this->certificateService->exportToExcel($certificates);
    }

    /**
     * Get certificate analytics for dashboard
     */
    public function analytics(Request $request)
    {
        $analytics = Certificate::getCertificateAnalytics();
        $expiryTrend = Certificate::getExpiryTrend(12);

        // Department breakdown
        $departmentBreakdown = \App\Models\Department::withCount([
            'employees as total_certificates' => function ($query) {
                $query->join('training_records', 'employees.id', '=', 'training_records.employee_id')
                      ->join('certificates', 'training_records.id', '=', 'certificates.training_record_id');
            },
            'employees as active_certificates' => function ($query) {
                $query->join('training_records', 'employees.id', '=', 'training_records.employee_id')
                      ->join('certificates', 'training_records.id', '=', 'certificates.training_record_id')
                      ->whereRaw('(certificates.expiry_date IS NULL OR certificates.expiry_date >= ?)', [now()]);
            }
        ])->get();

        return response()->json([
            'analytics' => $analytics,
            'expiry_trend' => $expiryTrend,
            'department_breakdown' => $departmentBreakdown
        ]);
    }

    /**
     * Send renewal reminders
     */
    public function sendRenewalReminders(Request $request)
    {
        $days = $request->get('days', [90, 60, 30, 7]);
        $results = [];

        foreach ($days as $day) {
            $certificates = Certificate::expiringIn($day)->get();
            $count = 0;

            foreach ($certificates as $certificate) {
                $this->notificationService->sendRenewalReminder($certificate, $day);
                $count++;
            }

            $results[] = [
                'days' => $day,
                'count' => $count
            ];
        }

        return response()->json([
            'message' => 'Renewal reminders sent successfully.',
            'results' => $results
        ]);
    }
}
