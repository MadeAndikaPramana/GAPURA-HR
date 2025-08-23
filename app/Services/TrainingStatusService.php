<?php

namespace App\Services;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrainingStatusService
{
    /**
     * Update status for a single training record based on expiry date
     */
    public function updateStatus(TrainingRecord $trainingRecord): bool
    {
        try {
            $newStatus = $this->calculateStatus($trainingRecord->expiry_date);
            $newComplianceStatus = $this->calculateComplianceStatus($newStatus);

            if ($trainingRecord->status !== $newStatus || $trainingRecord->compliance_status !== $newComplianceStatus) {
                $trainingRecord->update([
                    'status' => $newStatus,
                    'compliance_status' => $newComplianceStatus
                ]);

                Log::info('Training record status updated', [
                    'id' => $trainingRecord->id,
                    'old_status' => $trainingRecord->getOriginal('status'),
                    'new_status' => $newStatus,
                    'expiry_date' => $trainingRecord->expiry_date
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update training record status', [
                'id' => $trainingRecord->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Bulk update status for all training records
     * This should be run via scheduled command daily
     */
    public function updateAllStatuses(): array
    {
        $results = [
            'total_processed' => 0,
            'updated_count' => 0,
            'errors' => 0,
            'status_changes' => [
                'to_expired' => 0,
                'to_expiring_soon' => 0,
                'to_active' => 0
            ]
        ];

        try {
            DB::beginTransaction();

            $trainingRecords = TrainingRecord::whereNotNull('expiry_date')->get();
            $results['total_processed'] = $trainingRecords->count();

            foreach ($trainingRecords as $record) {
                $oldStatus = $record->status;
                $newStatus = $this->calculateStatus($record->expiry_date);
                $newComplianceStatus = $this->calculateComplianceStatus($newStatus);

                if ($oldStatus !== $newStatus) {
                    $record->update([
                        'status' => $newStatus,
                        'compliance_status' => $newComplianceStatus
                    ]);

                    $results['updated_count']++;

                    // Track status change types
                    switch ($newStatus) {
                        case 'expired':
                            $results['status_changes']['to_expired']++;
                            break;
                        case 'expiring_soon':
                            $results['status_changes']['to_expiring_soon']++;
                            break;
                        case 'active':
                            $results['status_changes']['to_active']++;
                            break;
                    }
                }
            }

            DB::commit();

            Log::info('Bulk status update completed', $results);

        } catch (\Exception $e) {
            DB::rollback();
            $results['errors']++;

            Log::error('Bulk status update failed', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }

        return $results;
    }

    /**
     * Calculate status based on expiry date
     */
    public function calculateStatus(?string $expiryDate): string
    {
        if (!$expiryDate) {
            return 'active';
        }

        $today = Carbon::today();
        $expiry = Carbon::parse($expiryDate);
        $daysUntilExpiry = $today->diffInDays($expiry, false);

        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }

    /**
     * Calculate compliance status based on training status
     */
    public function calculateComplianceStatus(string $status): string
    {
        switch ($status) {
            case 'expired':
                return 'expired';
            case 'expiring_soon':
                return 'expiring_soon';
            case 'active':
            case 'completed':
                return 'compliant';
            default:
                return 'not_required';
        }
    }

    /**
     * Generate certificate number
     */
    public function generateCertificateNumber(Employee $employee, TrainingType $trainingType): string
    {
        $departmentCode = $employee->department?->code ?? 'GEN';
        $trainingCode = $trainingType->code ?? 'TRN';
        $year = date('Y');
        $month = date('m');

        // Get next sequence number for this pattern
        $pattern = "{$departmentCode}-{$trainingCode}-{$year}{$month}-%";

        $lastRecord = TrainingRecord::where('certificate_number', 'like', $pattern)
            ->orderByRaw('CAST(SUBSTRING_INDEX(certificate_number, "-", -1) AS UNSIGNED) DESC')
            ->first();

        $sequence = 1;
        if ($lastRecord) {
            $parts = explode('-', $lastRecord->certificate_number);
            if (count($parts) >= 4) {
                $lastSequence = (int) end($parts);
                $sequence = $lastSequence + 1;
            }
        }

        return sprintf('%s-%s-%s%s-%03d', $departmentCode, $trainingCode, $year, $month, $sequence);
    }

    /**
     * Calculate expiry date based on issue date and training type validity
     */
    public function calculateExpiryDate(string $issueDate, int $trainingTypeId): ?string
    {
        try {
            $trainingType = TrainingType::find($trainingTypeId);

            if (!$trainingType || !$trainingType->validity_months) {
                return null;
            }

            $issue = Carbon::parse($issueDate);
            $expiry = $issue->addMonths($trainingType->validity_months);

            return $expiry->format('Y-m-d');

        } catch (\Exception $e) {
            Log::error('Failed to calculate expiry date', [
                'issue_date' => $issueDate,
                'training_type_id' => $trainingTypeId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get training records expiring in specified days
     */
    public function getExpiringRecords(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $targetDate = Carbon::today()->addDays($days);

        return TrainingRecord::with(['employee', 'trainingType'])
            ->where('expiry_date', '<=', $targetDate)
            ->where('status', '!=', 'expired')
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired training records
     */
    public function getExpiredRecords(): \Illuminate\Database\Eloquent\Collection
    {
        return TrainingRecord::with(['employee', 'trainingType'])
            ->where('expiry_date', '<', Carbon::today())
            ->where('status', '!=', 'expired')
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get compliance statistics
     */
    public function getComplianceStatistics(): array
    {
        $total = TrainingRecord::count();

        if ($total === 0) {
            return [
                'total_certificates' => 0,
                'active_certificates' => 0,
                'expiring_certificates' => 0,
                'expired_certificates' => 0,
                'compliance_rate' => 0,
                'expiring_rate' => 0,
                'expired_rate' => 0
            ];
        }

        $active = TrainingRecord::where('status', 'active')->count();
        $expiring = TrainingRecord::where('status', 'expiring_soon')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();

        return [
            'total_certificates' => $total,
            'active_certificates' => $active,
            'expiring_certificates' => $expiring,
            'expired_certificates' => $expired,
            'compliance_rate' => round(($active / $total) * 100, 2),
            'expiring_rate' => round(($expiring / $total) * 100, 2),
            'expired_rate' => round(($expired / $total) * 100, 2)
        ];
    }

    /**
     * Get employee compliance summary
     */
    public function getEmployeeComplianceSummary(Employee $employee): array
    {
        $records = $employee->trainingRecords;
        $total = $records->count();

        if ($total === 0) {
            return [
                'total_certificates' => 0,
                'active_certificates' => 0,
                'expiring_certificates' => 0,
                'expired_certificates' => 0,
                'compliance_rate' => 0
            ];
        }

        $active = $records->where('status', 'active')->count();
        $expiring = $records->where('status', 'expiring_soon')->count();
        $expired = $records->where('status', 'expired')->count();

        return [
            'total_certificates' => $total,
            'active_certificates' => $active,
            'expiring_certificates' => $expiring,
            'expired_certificates' => $expired,
            'compliance_rate' => round(($active / $total) * 100, 2)
        ];
    }

    /**
     * Get department compliance summary
     */
    public function getDepartmentComplianceSummary(int $departmentId): array
    {
        $records = TrainingRecord::whereHas('employee', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->get();

        $total = $records->count();

        if ($total === 0) {
            return [
                'total_certificates' => 0,
                'active_certificates' => 0,
                'expiring_certificates' => 0,
                'expired_certificates' => 0,
                'compliance_rate' => 0
            ];
        }

        $active = $records->where('status', 'active')->count();
        $expiring = $records->where('status', 'expiring_soon')->count();
        $expired = $records->where('status', 'expired')->count();

        return [
            'total_certificates' => $total,
            'active_certificates' => $active,
            'expiring_certificates' => $expiring,
            'expired_certificates' => $expired,
            'compliance_rate' => round(($active / $total) * 100, 2)
        ];
    }

    /**
     * Check if employee needs renewal for specific training type
     */
    public function needsRenewal(Employee $employee, TrainingType $trainingType): array
    {
        $latestRecord = TrainingRecord::where('employee_id', $employee->id)
            ->where('training_type_id', $trainingType->id)
            ->orderBy('issue_date', 'desc')
            ->first();

        if (!$latestRecord) {
            return [
                'needs_renewal' => true,
                'reason' => 'no_certificate',
                'latest_record' => null,
                'days_until_expiry' => null
            ];
        }

        $status = $this->calculateStatus($latestRecord->expiry_date);

        if ($status === 'expired') {
            return [
                'needs_renewal' => true,
                'reason' => 'expired',
                'latest_record' => $latestRecord,
                'days_until_expiry' => Carbon::parse($latestRecord->expiry_date)->diffInDays(Carbon::today(), false)
            ];
        }

        if ($status === 'expiring_soon') {
            return [
                'needs_renewal' => true,
                'reason' => 'expiring_soon',
                'latest_record' => $latestRecord,
                'days_until_expiry' => Carbon::today()->diffInDays(Carbon::parse($latestRecord->expiry_date), false)
            ];
        }

        return [
            'needs_renewal' => false,
            'reason' => 'active',
            'latest_record' => $latestRecord,
            'days_until_expiry' => Carbon::today()->diffInDays(Carbon::parse($latestRecord->expiry_date), false)
        ];
    }

    /**
     * Create renewal record
     */
    public function createRenewal(TrainingRecord $originalRecord, array $renewalData = []): TrainingRecord
    {
        DB::beginTransaction();

        try {
            // Generate new certificate number
            $certificateNumber = $this->generateCertificateNumber(
                $originalRecord->employee,
                $originalRecord->trainingType
            );

            // Prepare renewal data
            $data = array_merge([
                'employee_id' => $originalRecord->employee_id,
                'training_type_id' => $originalRecord->training_type_id,
                'certificate_number' => $certificateNumber,
                'issuer' => $originalRecord->issuer,
                'issue_date' => Carbon::today()->format('Y-m-d'),
                'status' => 'active',
                'compliance_status' => 'compliant',
                'created_by_id' => auth()->id(),
            ], $renewalData);

            // Calculate expiry date
            if ($originalRecord->trainingType->validity_months) {
                $data['expiry_date'] = Carbon::today()
                    ->addMonths($originalRecord->trainingType->validity_months)
                    ->format('Y-m-d');
            }

            $renewalRecord = TrainingRecord::create($data);

            DB::commit();

            Log::info('Renewal record created', [
                'original_id' => $originalRecord->id,
                'renewal_id' => $renewalRecord->id,
                'certificate_number' => $certificateNumber,
                'employee_id' => $originalRecord->employee_id
            ]);

            return $renewalRecord;

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to create renewal record', [
                'original_id' => $originalRecord->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get training records requiring action (expiring or expired)
     */
    public function getRecordsRequiringAction(int $warningDays = 30): array
    {
        $expiringRecords = $this->getExpiringRecords($warningDays);
        $expiredRecords = $this->getExpiredRecords();

        return [
            'expiring_soon' => $expiringRecords,
            'expired' => $expiredRecords,
            'total_requiring_action' => $expiringRecords->count() + $expiredRecords->count()
        ];
    }
}
