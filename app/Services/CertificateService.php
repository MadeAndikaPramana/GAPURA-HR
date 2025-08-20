<?php

namespace App\Services;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CertificateService
{
    /**
     * Generate a unique certificate number
     */
    public function generateCertificateNumber(TrainingType $trainingType, string $issuer, Employee $employee): string
    {
        $year = Carbon::now()->year;
        $typeCode = $trainingType->code ?? strtoupper(substr($trainingType->name, 0, 4));

        // Generate base pattern: TYPE-YEAR-SEQUENCE
        $basePattern = "{$typeCode}-{$year}";

        // Get the next sequence number for this pattern
        $lastRecord = TrainingRecord::where('certificate_number', 'like', "{$basePattern}-%")
            ->orderBy('certificate_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastRecord) {
            $parts = explode('-', $lastRecord->certificate_number);
            if (count($parts) >= 3) {
                $lastSequence = intval(end($parts));
                $sequence = $lastSequence + 1;
            }
        }

        return sprintf("%s-%03d", $basePattern, $sequence);
    }

    /**
     * Calculate expiry date based on training type validity
     */
    public function calculateExpiryDate(string $issueDate, TrainingType $trainingType): string
    {
        $issueCarbon = Carbon::parse($issueDate);
        $expiryCarbon = $issueCarbon->clone()->addMonths($trainingType->validity_months);

        return $expiryCarbon->toDateString();
    }

    /**
     * Create a new certificate record
     */
    public function createCertificate(array $data): TrainingRecord
    {
        DB::beginTransaction();

        try {
            $employee = Employee::findOrFail($data['employee_id']);
            $trainingType = TrainingType::findOrFail($data['training_type_id']);

            // Auto-generate certificate number if not provided
            if (empty($data['certificate_number'])) {
                $data['certificate_number'] = $this->generateCertificateNumber(
                    $trainingType,
                    $data['issuer'],
                    $employee
                );
            }

            // Auto-calculate expiry date if not provided
            if (empty($data['expiry_date']) && !empty($data['issue_date'])) {
                $data['expiry_date'] = $this->calculateExpiryDate($data['issue_date'], $trainingType);
            }

            // Determine initial status
            $data['status'] = $this->determineStatus($data['expiry_date']);

            $certificate = TrainingRecord::create($data);

            // Log certificate creation
            Log::info('Certificate created', [
                'certificate_id' => $certificate->id,
                'employee_id' => $employee->employee_id,
                'training_type' => $trainingType->name,
                'certificate_number' => $certificate->certificate_number
            ]);

            DB::commit();

            return $certificate;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create certificate', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Renew an existing certificate
     */
    public function renewCertificate(TrainingRecord $certificate, array $renewalData): TrainingRecord
    {
        DB::beginTransaction();

        try {
            // Generate new certificate number
            $renewalData['certificate_number'] = $this->generateCertificateNumber(
                $certificate->trainingType,
                $renewalData['issuer'] ?? $certificate->issuer,
                $certificate->employee
            );

            // Calculate new expiry date
            $renewalData['expiry_date'] = $this->calculateExpiryDate(
                $renewalData['issue_date'],
                $certificate->trainingType
            );

            // Determine status
            $renewalData['status'] = $this->determineStatus($renewalData['expiry_date']);

            // Add renewal notes
            $renewalData['notes'] = ($renewalData['notes'] ?? '') .
                " [Renewed from: {$certificate->certificate_number}]";

            // Create new certificate record
            $newCertificate = TrainingRecord::create([
                'employee_id' => $certificate->employee_id,
                'training_type_id' => $certificate->training_type_id,
                'certificate_number' => $renewalData['certificate_number'],
                'issuer' => $renewalData['issuer'] ?? $certificate->issuer,
                'issue_date' => $renewalData['issue_date'],
                'expiry_date' => $renewalData['expiry_date'],
                'status' => $renewalData['status'],
                'notes' => $renewalData['notes']
            ]);

            // Mark old certificate as superseded
            $certificate->update([
                'status' => 'superseded',
                'notes' => ($certificate->notes ?? '') . " [Superseded by: {$newCertificate->certificate_number}]"
            ]);

            // Log renewal
            Log::info('Certificate renewed', [
                'old_certificate_id' => $certificate->id,
                'new_certificate_id' => $newCertificate->id,
                'employee_id' => $certificate->employee->employee_id,
                'training_type' => $certificate->trainingType->name
            ]);

            DB::commit();

            return $newCertificate;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to renew certificate', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
                'renewal_data' => $renewalData
            ]);
            throw $e;
        }
    }

    /**
     * Get certificate lifecycle history
     */
    public function getCertificateHistory(TrainingRecord $certificate): array
    {
        $employee = $certificate->employee;
        $trainingType = $certificate->trainingType;

        // Get all certificates for this employee and training type
        $allCertificates = TrainingRecord::where('employee_id', $employee->id)
            ->where('training_type_id', $trainingType->id)
            ->orderBy('issue_date', 'asc')
            ->get();

        return [
            'current_certificate' => $certificate,
            'all_certificates' => $allCertificates,
            'renewal_count' => $allCertificates->count() - 1,
            'first_certification_date' => $allCertificates->first()->issue_date ?? null,
            'total_years_certified' => $this->calculateTotalYearsCertified($allCertificates),
            'gaps_in_certification' => $this->findCertificationGaps($allCertificates)
        ];
    }

    /**
     * Get certificates requiring renewal
     */
    public function getCertificatesRequiringRenewal(int $daysAhead = 30): array
    {
        $cutoffDate = Carbon::now()->addDays($daysAhead);

        $certificates = TrainingRecord::with(['employee.department', 'trainingType'])
            ->where('expiry_date', '<=', $cutoffDate)
            ->where('expiry_date', '>=', Carbon::now())
            ->whereIn('status', ['active', 'expiring_soon'])
            ->orderBy('expiry_date', 'asc')
            ->get();

        return [
            'certificates' => $certificates,
            'by_department' => $certificates->groupBy('employee.department.name'),
            'by_training_type' => $certificates->groupBy('trainingType.name'),
            'by_urgency' => [
                'critical' => $certificates->filter(fn($cert) => Carbon::parse($cert->expiry_date)->diffInDays(Carbon::now()) <= 7),
                'urgent' => $certificates->filter(fn($cert) => Carbon::parse($cert->expiry_date)->diffInDays(Carbon::now()) <= 14),
                'upcoming' => $certificates->filter(fn($cert) => Carbon::parse($cert->expiry_date)->diffInDays(Carbon::now()) <= 30)
            ]
        ];
    }

    /**
     * Validate certificate data
     */
    public function validateCertificateData(array $data): array
    {
        $errors = [];

        // Check if employee exists
        if (!Employee::find($data['employee_id'] ?? null)) {
            $errors['employee_id'] = 'Employee not found';
        }

        // Check if training type exists
        if (!TrainingType::find($data['training_type_id'] ?? null)) {
            $errors['training_type_id'] = 'Training type not found';
        }

        // Check certificate number uniqueness
        if (!empty($data['certificate_number'])) {
            $existing = TrainingRecord::where('certificate_number', $data['certificate_number'])
                ->when(isset($data['id']), fn($q) => $q->where('id', '!=', $data['id']))
                ->exists();

            if ($existing) {
                $errors['certificate_number'] = 'Certificate number already exists';
            }
        }

        // Validate dates
        if (!empty($data['issue_date']) && !empty($data['expiry_date'])) {
            $issueDate = Carbon::parse($data['issue_date']);
            $expiryDate = Carbon::parse($data['expiry_date']);

            if ($expiryDate->lte($issueDate)) {
                $errors['expiry_date'] = 'Expiry date must be after issue date';
            }
        }

        return $errors;
    }

    /**
     * Get certificate statistics
     */
    public function getCertificateStatistics(): array
    {
        $stats = TrainingRecord::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = "active" THEN 1 END) as active,
            COUNT(CASE WHEN status = "expiring_soon" THEN 1 END) as expiring_soon,
            COUNT(CASE WHEN status = "expired" THEN 1 END) as expired,
            COUNT(CASE WHEN status = "superseded" THEN 1 END) as superseded
        ')->first();

        $byMonth = TrainingRecord::selectRaw('
            YEAR(issue_date) as year,
            MONTH(issue_date) as month,
            COUNT(*) as count
        ')
        ->whereYear('issue_date', Carbon::now()->year)
        ->groupBy('year', 'month')
        ->orderBy('year')
        ->orderBy('month')
        ->get();

        $expiringThisMonth = TrainingRecord::whereMonth('expiry_date', Carbon::now()->month)
            ->whereYear('expiry_date', Carbon::now()->year)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->count();

        return [
            'totals' => $stats,
            'by_month' => $byMonth,
            'expiring_this_month' => $expiringThisMonth,
            'compliance_rate' => $stats->total > 0 ? round(($stats->active / $stats->total) * 100, 2) : 0
        ];
    }

    /**
     * Determine certificate status based on expiry date
     */
    private function determineStatus(string $expiryDate): string
    {
        $expiry = Carbon::parse($expiryDate);
        $now = Carbon::now();
        $daysUntilExpiry = $now->diffInDays($expiry, false);

        if ($expiry->isPast()) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }

    /**
     * Calculate total years an employee has been certified
     */
    private function calculateTotalYearsCertified($certificates): float
    {
        if ($certificates->isEmpty()) {
            return 0;
        }

        $firstCert = $certificates->sortBy('issue_date')->first();
        $lastCert = $certificates->sortBy('expiry_date')->last();

        $startDate = Carbon::parse($firstCert->issue_date);
        $endDate = min(Carbon::parse($lastCert->expiry_date), Carbon::now());

        return round($startDate->diffInYears($endDate, true), 1);
    }

    /**
     * Find gaps in certification history
     */
    private function findCertificationGaps($certificates): array
    {
        $gaps = [];
        $sortedCerts = $certificates->sortBy('issue_date');

        for ($i = 0; $i < $sortedCerts->count() - 1; $i++) {
            $currentExpiry = Carbon::parse($sortedCerts[$i]->expiry_date);
            $nextIssue = Carbon::parse($sortedCerts[$i + 1]->issue_date);

            if ($nextIssue->gt($currentExpiry)) {
                $gaps[] = [
                    'start' => $currentExpiry->toDateString(),
                    'end' => $nextIssue->toDateString(),
                    'duration_days' => $currentExpiry->diffInDays($nextIssue)
                ];
            }
        }

        return $gaps;
    }

    /**
     * Generate certificate audit trail
     */
    public function getCertificateAuditTrail(TrainingRecord $certificate): array
    {
        return [
            'certificate_id' => $certificate->id,
            'employee' => $certificate->employee->name,
            'employee_id' => $certificate->employee->employee_id,
            'training_type' => $certificate->trainingType->name,
            'certificate_number' => $certificate->certificate_number,
            'issuer' => $certificate->issuer,
            'issue_date' => $certificate->issue_date,
            'expiry_date' => $certificate->expiry_date,
            'status' => $certificate->status,
            'created_at' => $certificate->created_at,
            'updated_at' => $certificate->updated_at,
            'notes' => $certificate->notes,
            'related_certificates' => $this->getRelatedCertificates($certificate),
            'compliance_status' => $this->getCertificateComplianceStatus($certificate)
        ];
    }

    /**
     * Get related certificates (renewals, superseded)
     */
    private function getRelatedCertificates(TrainingRecord $certificate): array
    {
        return TrainingRecord::where('employee_id', $certificate->employee_id)
            ->where('training_type_id', $certificate->training_type_id)
            ->where('id', '!=', $certificate->id)
            ->orderBy('issue_date')
            ->get()
            ->toArray();
    }

    /**
     * Get certificate compliance status
     */
    private function getCertificateComplianceStatus(TrainingRecord $certificate): array
    {
        $now = Carbon::now();
        $expiry = Carbon::parse($certificate->expiry_date);
        $issue = Carbon::parse($certificate->issue_date);

        return [
            'is_current' => $certificate->status === 'active',
            'days_until_expiry' => $now->diffInDays($expiry, false),
            'certificate_age_days' => $issue->diffInDays($now),
            'compliance_level' => $this->getComplianceLevel($certificate->status),
            'action_required' => $this->getActionRequired($certificate->status, $now->diffInDays($expiry, false))
        ];
    }

    /**
     * Get compliance level based on status
     */
    private function getComplianceLevel(string $status): string
    {
        return match ($status) {
            'active' => 'compliant',
            'expiring_soon' => 'warning',
            'expired' => 'non_compliant',
            'superseded' => 'superseded',
            default => 'unknown'
        };
    }

    /**
     * Get action required based on status and days to expiry
     */
    private function getActionRequired(string $status, int $daysToExpiry): string
    {
        if ($status === 'expired') {
            return 'immediate_renewal_required';
        } elseif ($status === 'expiring_soon') {
            if ($daysToExpiry <= 7) {
                return 'urgent_renewal_required';
            } elseif ($daysToExpiry <= 14) {
                return 'schedule_renewal';
            } else {
                return 'plan_renewal';
            }
        } elseif ($status === 'active') {
            return 'no_action_required';
        }

        return 'review_required';
    }
}
