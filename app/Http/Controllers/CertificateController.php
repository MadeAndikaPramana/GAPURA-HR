<?php
// app/Http/Controllers/CertificateController.php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use App\Models\TrainingProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CertificateController extends Controller
{
    /**
     * Display a listing of certificates
     */
    public function index(Request $request)
    {
        $query = Certificate::with([
            'employee:id,name,employee_id',
            'trainingType:id,name,code,category',
            'trainingProvider:id,name,code',
            'verifiedBy:id,name'
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Compliance filter
        if ($request->filled('compliance') && $request->compliance !== '') {
            $query->where('compliance_status', $request->compliance);
        }

        // Provider filter
        if ($request->filled('provider') && $request->provider !== '') {
            $query->where('training_provider_id', $request->provider);
        }

        // Training type filter
        if ($request->filled('training_type') && $request->training_type !== '') {
            $query->where('training_type_id', $request->training_type);
        }

        // Verification filter
        if ($request->filled('verification') && $request->verification !== '') {
            $query->where('is_verified', $request->verification === '1');
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Expiry status filter
        if ($request->filled('expiry_status')) {
            switch ($request->expiry_status) {
                case 'active':
                    $query->active();
                    break;
                case 'expiring_soon':
                    $query->expiringSoon(30);
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'due_renewal':
                    $query->dueForRenewal(60);
                    break;
            }
        }

        $certificates = $query->orderByDesc('issue_date')
                             ->paginate(20)
                             ->withQueryString();

        // Get filter options
        $filterOptions = [
            'statuses' => ['draft', 'active', 'expiring_soon', 'expired', 'revoked', 'suspended', 'renewed', 'cancelled'],
            'compliance_statuses' => ['compliant', 'non_compliant', 'under_review', 'exempted'],
            'training_providers' => TrainingProvider::select('id', 'name')->orderBy('name')->get(),
            'training_types' => TrainingType::select('id', 'name', 'code')->orderBy('name')->get(),
        ];

        // Calculate summary statistics
        $stats = Certificate::getAnalytics();

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
            'filters' => $request->only([
                'search', 'status', 'compliance', 'provider', 'training_type',
                'verification', 'date_from', 'date_to', 'expiry_status'
            ]),
            'filterOptions' => $filterOptions,
            'stats' => $stats
        ]);
    }

    /**
     * Show certificate details
     */
    public function show(Certificate $certificate)
    {
        $certificate->load([
            'employee.department',
            'trainingType.category',
            'trainingProvider',
            'trainingRecord',
            'verifiedBy',
            'revokedBy',
            'createdBy',
            'renewedFrom',
            'renewedTo',
            'renewalHistory'
        ]);

        // Get certificate lifecycle history
        $lifecycleEvents = $this->getCertificateLifecycleEvents($certificate);

        // Calculate certificate metrics
        $metrics = [
            'lifecycle_progress' => $certificate->getLifecycleProgress(),
            'days_until_expiry' => $certificate->getDaysUntilExpiry(),
            'verification_status' => $certificate->is_verified ? 'Verified' : 'Unverified',
            'download_count' => $certificate->download_count,
            'renewal_generation' => $certificate->renewal_generation,
            'file_size' => $certificate->file_size_kb ? $certificate->file_size_kb . ' KB' : 'N/A'
        ];

        return Inertia::render('Certificates/Show', [
            'certificate' => $certificate,
            'lifecycleEvents' => $lifecycleEvents,
            'metrics' => $metrics,
            'qrCodeUrl' => $certificate->qr_code_path ? Storage::url($certificate->qr_code_path) : null,
            'canDownload' => $certificate->certificate_file_path && Storage::exists($certificate->certificate_file_path),
            'verificationUrl' => $certificate->getVerificationUrl()
        ]);
    }

    /**
     * Show certificate creation form
     */
    public function create(Request $request)
    {
        $trainingRecord = null;

        if ($request->filled('training_record_id')) {
            $trainingRecord = TrainingRecord::with(['employee', 'trainingType', 'trainingProvider'])
                                          ->findOrFail($request->training_record_id);
        }

        return Inertia::render('Certificates/Create', [
            'trainingRecord' => $trainingRecord,
            'employees' => Employee::select('id', 'name', 'employee_id')->orderBy('name')->get(),
            'trainingTypes' => TrainingType::select('id', 'name', 'code', 'category')->orderBy('name')->get(),
            'trainingProviders' => TrainingProvider::select('id', 'name', 'code')->orderBy('name')->get(),
            'templates' => $this->getCertificateTemplates()
        ]);
    }

    /**
     * Store new certificate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_record_id' => 'required|exists:training_records,id',
            'certificate_template' => 'nullable|string',
            'issued_by' => 'required|string|max:255',
            'issuer_name' => 'nullable|string|max:255',
            'issuer_title' => 'nullable|string|max:255',
            'issuer_license' => 'nullable|string|max:100',
            'issue_date' => 'required|date',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'validity_period_days' => 'nullable|integer|min:1',
            'certificate_description' => 'nullable|string',
            'competencies_achieved' => 'nullable|array',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'grade' => 'nullable|string|max:10',
            'is_renewable' => 'boolean',
            'language' => 'nullable|string|max:5',
            'internal_notes' => 'nullable|string',
            'certificate_file' => 'nullable|file|mimes:pdf|max:5120' // 5MB max
        ]);

        $trainingRecord = TrainingRecord::with(['employee', 'trainingType', 'trainingProvider'])
                                       ->findOrFail($validated['training_record_id']);

        // Auto-populate fields from training record
        $certificateData = array_merge($validated, [
            'training_type_id' => $trainingRecord->training_type_id,
            'employee_id' => $trainingRecord->employee_id,
            'training_provider_id' => $trainingRecord->training_provider_id,
            'certificate_number' => Certificate::generateCertificateNumber($trainingRecord->trainingType->code),
            'verification_code' => Certificate::generateVerificationCode(),
            'status' => 'active',
            'lifecycle_stage' => 'issued',
            'compliance_status' => 'compliant',
            'is_verified' => false,
            'renewal_generation' => 1,
            'created_by_id' => Auth::id()
        ]);

        // Handle file upload
        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');
            $path = $file->store('certificates', 'private');

            $certificateData['certificate_file_path'] = $path;
            $certificateData['file_size_kb'] = round($file->getSize() / 1024);
            $certificateData['file_hash'] = hash_file('md5', $file->getPathname());
        }

        $certificate = Certificate::create($certificateData);

        // Generate digital signature
        $certificate->generateDigitalSignature();

        // Log activity
        activity('certificate')
            ->performedOn($certificate)
            ->causedBy(Auth::user())
            ->log('Certificate created');

        return redirect()->route('certificates.show', $certificate)
                        ->with('success', "Certificate {$certificate->certificate_number} created successfully.");
    }

    /**
     * Show certificate edit form
     */
    public function edit(Certificate $certificate)
    {
        $certificate->load(['trainingRecord.employee', 'trainingType', 'trainingProvider']);

        return Inertia::render('Certificates/Edit', [
            'certificate' => $certificate,
            'trainingProviders' => TrainingProvider::select('id', 'name', 'code')->orderBy('name')->get(),
            'templates' => $this->getCertificateTemplates(),
            'canEdit' => $this->canEditCertificate($certificate)
        ]);
    }

    /**
     * Update certificate
     */
    public function update(Request $request, Certificate $certificate)
    {
        if (!$this->canEditCertificate($certificate)) {
            return redirect()->back()
                           ->with('error', 'Certificate cannot be modified in its current status.');
        }

        $validated = $request->validate([
            'certificate_template' => 'nullable|string',
            'issued_by' => 'required|string|max:255',
            'issuer_name' => 'nullable|string|max:255',
            'issuer_title' => 'nullable|string|max:255',
            'issuer_license' => 'nullable|string|max:100',
            'valid_from' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'certificate_description' => 'nullable|string',
            'competencies_achieved' => 'nullable|array',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'passing_score' => 'nullable|numeric|min:0|max:100',
            'grade' => 'nullable|string|max:10',
            'is_renewable' => 'boolean',
            'internal_notes' => 'nullable|string',
            'certificate_file' => 'nullable|file|mimes:pdf|max:5120'
        ]);

        // Handle file upload
        if ($request->hasFile('certificate_file')) {
            // Delete old file
            if ($certificate->certificate_file_path) {
                Storage::delete($certificate->certificate_file_path);
            }

            $file = $request->file('certificate_file');
            $path = $file->store('certificates', 'private');

            $validated['certificate_file_path'] = $path;
            $validated['file_size_kb'] = round($file->getSize() / 1024);
            $validated['file_hash'] = hash_file('md5', $file->getPathname());
        }

        $validated['updated_by_id'] = Auth::id();
        $certificate->update($validated);

        // Regenerate digital signature if content changed
        $certificate->generateDigitalSignature();

        return redirect()->route('certificates.show', $certificate)
                        ->with('success', 'Certificate updated successfully.');
    }

    /**
     * Download certificate file
     */
    public function download(Certificate $certificate)
    {
        if (!$certificate->certificate_file_path || !Storage::exists($certificate->certificate_file_path)) {
            abort(404, 'Certificate file not found.');
        }

        $certificate->incrementDownloadCount();

        $filename = $certificate->certificate_number . '_' .
                   str_replace(' ', '_', $certificate->employee->name) . '.pdf';

        return Storage::download($certificate->certificate_file_path, $filename);
    }

    /**
     * Verify certificate by verification code
     */
    public function verify($verificationCode)
    {
        $certificate = Certificate::where('verification_code', $verificationCode)
            ->with([
                'employee:id,name,employee_id',
                'trainingType:id,name,code,category',
                'trainingProvider:id,name'
            ])
            ->first();

        if (!$certificate) {
            return Inertia::render('Certificates/VerificationResult', [
                'success' => false,
                'message' => 'Certificate not found or invalid verification code.',
                'verificationCode' => $verificationCode
            ]);
        }

        // Increment verification attempts
        $certificate->increment('verification_attempts');
        $certificate->update(['last_verification_attempt' => now()]);

        // Check certificate validity
        $isValid = $certificate->status === 'active' &&
                  (!$certificate->expiry_date || $certificate->expiry_date >= now());

        return Inertia::render('Certificates/VerificationResult', [
            'success' => true,
            'certificate' => $certificate,
            'isValid' => $isValid,
            'verificationMessage' => $this->getVerificationMessage($certificate),
            'verifiedAt' => now()->toDateTimeString()
        ]);
    }

    /**
     * Renew certificate
     */
    public function renew(Request $request, Certificate $certificate)
    {
        if (!$certificate->is_renewable) {
            return redirect()->back()
                           ->with('error', 'This certificate is not renewable.');
        }

        $validated = $request->validate([
            'expiry_date' => 'required|date|after:today',
            'renewal_notes' => 'nullable|string'
        ]);

        $newCertificate = $certificate->renew([
            'expiry_date' => $validated['expiry_date'],
            'internal_notes' => $validated['renewal_notes'] ?? null,
            'created_by_id' => Auth::id()
        ]);

        // Log activity
        activity('certificate')
            ->performedOn($newCertificate)
            ->causedBy(Auth::user())
            ->withProperties(['original_certificate_id' => $certificate->id])
            ->log('Certificate renewed');

        return redirect()->route('certificates.show', $newCertificate)
                        ->with('success', "Certificate renewed successfully. New certificate number: {$newCertificate->certificate_number}");
    }

    /**
     * Revoke certificate
     */
    public function revoke(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'revocation_reason' => 'required|string|max:500',
            'revocation_notes' => 'nullable|string'
        ]);

        $certificate->revoke($validated['revocation_reason'], Auth::id());

        if (isset($validated['revocation_notes'])) {
            $certificate->update(['revocation_notes' => $validated['revocation_notes']]);
        }

        return redirect()->back()
                        ->with('success', 'Certificate revoked successfully.');
    }

    /**
     * Suspend certificate
     */
    public function suspend(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'suspension_start' => 'required|date',
            'suspension_end' => 'required|date|after:suspension_start',
            'suspension_reason' => 'required|string|max:500'
        ]);

        $certificate->suspend(
            $validated['suspension_start'],
            $validated['suspension_end'],
            $validated['suspension_reason']
        );

        return redirect()->back()
                        ->with('success', 'Certificate suspended successfully.');
    }

    /**
     * Reactivate suspended certificate
     */
    public function reactivate(Certificate $certificate)
    {
        if ($certificate->status !== 'suspended') {
            return redirect()->back()
                           ->with('error', 'Only suspended certificates can be reactivated.');
        }

        $certificate->reactivate();

        return redirect()->back()
                        ->with('success', 'Certificate reactivated successfully.');
    }

    /**
     * Verify certificate manually
     */
    public function markVerified(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'verification_notes' => 'nullable|string'
        ]);

        $certificate->verify(Auth::user(), $validated['verification_notes']);

        return redirect()->back()
                        ->with('success', 'Certificate verified successfully.');
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:verify,unverify,delete,export,update_status',
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'exists:certificates,id',
            'new_status' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        $certificates = Certificate::whereIn('id', $validated['certificate_ids']);

        switch ($validated['action']) {
            case 'verify':
                $certificates->update([
                    'is_verified' => true,
                    'verification_date' => now(),
                    'verified_by_id' => Auth::id(),
                    'verification_notes' => $validated['notes'] ?? null
                ]);
                $message = 'Certificates verified successfully.';
                break;

            case 'unverify':
                $certificates->update([
                    'is_verified' => false,
                    'verification_date' => null,
                    'verified_by_id' => null,
                    'verification_notes' => null
                ]);
                $message = 'Certificates unverified successfully.';
                break;

            case 'update_status':
                if (!$validated['new_status']) {
                    return redirect()->back()->with('error', 'Status is required.');
                }
                $certificates->update(['status' => $validated['new_status']]);
                $message = "Certificates status updated to {$validated['new_status']}.";
                break;

            case 'delete':
                foreach ($certificates->get() as $certificate) {
                    $this->deleteCertificateFiles($certificate);
                }
                $certificates->delete();
                $message = 'Certificates deleted successfully.';
                break;

            case 'export':
                return $this->exportCertificates($validated['certificate_ids']);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Certificate analytics dashboard
     */
    public function analytics(Request $request)
    {
        $dateRange = $request->get('range', '30'); // Default 30 days
        $startDate = now()->subDays($dateRange);

        $analytics = Certificate::getAnalytics($startDate, now());

        // Additional analytics
        $expiryTrend = $this->getExpiryTrend(12);
        $providerPerformance = $this->getProviderPerformance();
        $complianceBreakdown = $this->getComplianceBreakdown();
        $renewalStatistics = $this->getRenewalStatistics();

        return Inertia::render('Certificates/Analytics', [
            'analytics' => $analytics,
            'expiryTrend' => $expiryTrend,
            'providerPerformance' => $providerPerformance,
            'complianceBreakdown' => $complianceBreakdown,
            'renewalStatistics' => $renewalStatistics,
            'dateRange' => $dateRange
        ]);
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    private function getCertificateTemplates()
    {
        return [
            'default' => 'Default Certificate Template',
            'safety' => 'Safety Training Certificate',
            'aviation' => 'Aviation Certificate',
            'technical' => 'Technical Competency Certificate',
            'compliance' => 'Compliance Training Certificate'
        ];
    }

    private function canEditCertificate(Certificate $certificate)
    {
        return !in_array($certificate->status, ['revoked', 'renewed', 'cancelled']);
    }

    private function getCertificateLifecycleEvents(Certificate $certificate)
    {
        $events = [];

        $events[] = [
            'date' => $certificate->issue_date,
            'event' => 'Certificate Issued',
            'description' => "Certificate {$certificate->certificate_number} issued",
            'type' => 'issued'
        ];

        if ($certificate->verification_date) {
            $events[] = [
                'date' => $certificate->verification_date,
                'event' => 'Certificate Verified',
                'description' => 'Certificate verified by ' . ($certificate->verifiedBy->name ?? 'System'),
                'type' => 'verified'
            ];
        }

        if ($certificate->revocation_date) {
            $events[] = [
                'date' => $certificate->revocation_date,
                'event' => 'Certificate Revoked',
                'description' => $certificate->revocation_reason,
                'type' => 'revoked'
            ];
        }

        return collect($events)->sortBy('date')->values();
    }

    private function getVerificationMessage(Certificate $certificate)
    {
        if ($certificate->status === 'revoked') {
            return 'This certificate has been revoked and is no longer valid.';
        }

        if ($certificate->status === 'suspended') {
            return 'This certificate is currently suspended.';
        }

        if ($certificate->isExpired()) {
            return 'This certificate has expired and is no longer valid.';
        }

        if ($certificate->isExpiringSoon()) {
            $days = $certificate->getDaysUntilExpiry();
            return "This certificate is valid but expires in {$days} days.";
        }

        return 'This certificate is valid and active.';
    }

    private function deleteCertificateFiles(Certificate $certificate)
    {
        if ($certificate->certificate_file_path) {
            Storage::delete($certificate->certificate_file_path);
        }
        if ($certificate->qr_code_path) {
            Storage::delete($certificate->qr_code_path);
        }
    }

    private function exportCertificates($certificateIds)
    {
        // Implementation for exporting certificates to Excel/PDF
        // This would use a service class like CertificateExportService
        return response()->json(['message' => 'Export functionality to be implemented']);
    }

    private function getExpiryTrend($months)
    {
        // Return expiry trend data for charts
        return [];
    }

    private function getProviderPerformance()
    {
        // Return provider performance metrics
        return [];
    }

    private function getComplianceBreakdown()
    {
        // Return compliance breakdown data
        return [];
    }

    private function getRenewalStatistics()
    {
        // Return renewal statistics
        return [];
    }
}
