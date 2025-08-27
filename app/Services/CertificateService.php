<?php
// app/Services/CertificateService.php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingRecord;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CertificateService
{
    /**
     * Create certificate from training record
     */
    public function createFromTrainingRecord(TrainingRecord $trainingRecord, array $options = [])
    {
        $certificateData = array_merge([
            'training_record_id' => $trainingRecord->id,
            'training_type_id' => $trainingRecord->training_type_id,
            'employee_id' => $trainingRecord->employee_id,
            'training_provider_id' => $trainingRecord->training_provider_id,
            'certificate_number' => Certificate::generateCertificateNumber($trainingRecord->trainingType->code ?? 'GAP'),
            'verification_code' => Certificate::generateVerificationCode(),
            'issued_by' => $trainingRecord->trainingProvider->name ?? 'PT. Gapura Angkasa',
            'issue_date' => $trainingRecord->completion_date ?? now(),
            'expiry_date' => $this->calculateExpiryDate($trainingRecord),
            'final_score' => $trainingRecord->score,
            'passing_score' => $trainingRecord->passing_score,
            'status' => 'active',
            'is_verified' => false,
            'renewal_generation' => 1,
            'created_by_id' => auth()->id()
        ], $options);

        $certificate = Certificate::create($certificateData);

        return $certificate;
    }

    /**
     * Update certificate status based on current date
     */
    public function updateCertificateStatuses()
    {
        $updated = 0;

        // Update expired certificates
        $expiredCount = Certificate::where('status', '!=', 'expired')
            ->where('status', '!=', 'revoked')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->update(['status' => 'expired']);

        // Update expiring soon certificates
        $expiringSoonCount = Certificate::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->update(['status' => 'expiring_soon']);

        Log::info('Certificate statuses updated', [
            'expired' => $expiredCount,
            'expiring_soon' => $expiringSoonCount
        ]);

        return [
            'expired' => $expiredCount,
            'expiring_soon' => $expiringSoonCount,
            'total_updated' => $expiredCount + $expiringSoonCount
        ];
    }

    /**
     * Get certificates due for renewal
     */
    public function getCertificatesDueForRenewal($days = 60)
    {
        return Certificate::where('is_renewable', true)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->with(['employee', 'trainingType', 'trainingProvider'])
            ->get();
    }

    /**
     * Calculate expiry date from training record
     */
    private function calculateExpiryDate(TrainingRecord $trainingRecord)
    {
        if ($trainingRecord->expiry_date) {
            return $trainingRecord->expiry_date;
        }

        $issueDate = $trainingRecord->completion_date ?? $trainingRecord->issue_date ?? now();
        $validityMonths = $trainingRecord->trainingType->validity_period_months ?? 12;

        return Carbon::parse($issueDate)->addMonths($validityMonths);
    }
}
