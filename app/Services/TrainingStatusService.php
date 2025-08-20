<?php

namespace App\Services;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\CertificateSequence;
use App\Models\Employee;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrainingStatusService
{
    /**
     * Update training record statuses based on expiry dates
     * Should be called via scheduled command daily
     */
    public function updateAllStatuses()
    {
        $updated = 0;
        $now = Carbon::now();
        $warningDays = 30; // 30 days before expiry = expiring_soon

        try {
            DB::beginTransaction();

            // Mark as expired (past expiry date)
            $expiredCount = TrainingRecord::where('status', '!=', 'expired')
                ->where('expiry_date', '<', $now->toDateString())
                ->update(['status' => 'expired', 'updated_at' => $now]);

            // Mark as expiring soon (within 30 days)
            $expiringSoonCount = TrainingRecord::where('status', 'active')
                ->where('expiry_date', '>=', $now->toDateString())
                ->where('expiry_date', '<=', $now->copy()->addDays($warningDays)->toDateString())
                ->update(['status' => 'expiring_soon', 'updated_at' => $now]);

            // Mark as active (more than 30 days until expiry and not expired)
            $activeCount = TrainingRecord::where('status', 'expiring_soon')
                ->where('expiry_date', '>', $now->copy()->addDays($warningDays)->toDateString())
                ->update(['status' => 'active', 'updated_at' => $now]);

            $updated = $expiredCount + $expiringSoonCount + $activeCount;

            DB::commit();

            Log::info("Training status updated successfully", [
                'expired' => $expiredCount,
                'expiring_soon' => $expiringSoonCount,
                'active' => $activeCount,
                'total_updated' => $updated,
                'timestamp' => $now->toISOString()
            ]);

            return $updated;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update training statuses", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get training records that are expiring soon
     */
    public function getExpiringSoon($days = 30)
    {
        $warningDate = Carbon::now()->addDays($days);

        return TrainingRecord::with(['employee', 'trainingType'])
            ->where('expiry_date', '<=', $warningDate->toDateString())
            ->where('expiry_date', '>=', Carbon::now()->toDateString())
            ->whereIn('status', ['active', 'expiring_soon'])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired training records
     */
    public function getExpired()
    {
        return TrainingRecord::with(['employee.department', 'trainingType'])
            ->where('status', 'expired')
            ->orderBy('expiry_date', 'desc')
            ->get();
    }

    /**
     * Get compliance statistics by department
     */
    public function getComplianceByDepartment()
    {
        return DB::table('departments')
            ->leftJoin('employees', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('training_records', function($join) {
                $join->on('employees.id', '=', 'training_records.employee_id');
            })
            ->selectRaw('
                departments.id as department_id,
                departments.name as department_name,
                COUNT(DISTINCT employees.id) as total_employees,
                COUNT(DISTINCT CASE WHEN training_records.status = "active" THEN training_records.id END) as active_certificates,
                COUNT(DISTINCT CASE WHEN training_records.status = "expiring_soon" THEN training_records.id END) as expiring_certificates,
                COUNT(DISTINCT CASE WHEN training_records.status = "expired" THEN training_records.id END) as expired_certificates,
                COUNT(DISTINCT training_records.id) as total_certificates,
                ROUND(
                    CASE
                        WHEN COUNT(DISTINCT training_records.id) > 0
                        THEN (COUNT(DISTINCT CASE WHEN training_records.status = "active" THEN training_records.id END) / COUNT(DISTINCT training_records.id)) * 100
                        ELSE 0
                    END, 2
                ) as compliance_rate
            ')
            ->where('employees.status', 'active')
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('compliance_rate', 'desc')
            ->get();
    }

    /**
     * Get compliance statistics by training type
     */
    public function getComplianceByTrainingType()
    {
        return DB::table('training_types')
            ->leftJoin('training_records', 'training_types.id', '=', 'training_records.training_type_id')
            ->selectRaw('
                training_types.id as training_type_id,
                training_types.name as training_name,
                training_types.category,
                training_types.validity_months,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_count,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_count,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_count,
                COUNT(training_records.id) as total_records,
                ROUND(
                    CASE
                        WHEN COUNT(training_records.id) > 0
                        THEN (COUNT(CASE WHEN training_records.status = "active" THEN 1 END) / COUNT(training_records.id)) * 100
                        ELSE 0
                    END, 2
                ) as active_percentage
            ')
            ->where('training_types.is_active', true)
            ->groupBy('training_types.id', 'training_types.name', 'training_types.category', 'training_types.validity_months')
            ->orderBy('active_percentage', 'desc')
            ->get();
    }

    /**
     * Generate certificate number
     */
    public function generateCertificateNumber($trainingTypeId, $issuer)
    {
        $trainingType = TrainingType::find($trainingTypeId);
        if (!$trainingType) {
            throw new \Exception("Training type not found");
        }

        $now = Carbon::now();

        try {
            DB::beginTransaction();

            // Find or create sequence record
            $sequence = CertificateSequence::firstOrCreate([
                'training_type_id' => $trainingTypeId,
                'issuer' => $issuer,
                'year' => $now->year,
                'month' => $now->month,
            ], [
                'last_number' => 0
            ]);

            // Increment sequence
            $sequence->increment('last_number');

            // Generate certificate number: GLG/OPR-001/2024
            $prefix = strtoupper(substr($issuer, 0, 3));
            $typeCode = $trainingType->code ?: strtoupper(substr($trainingType->name, 0, 3));
            $number = str_pad($sequence->last_number, 3, '0', STR_PAD_LEFT);

            $certificateNumber = "{$prefix}/{$typeCode}-{$number}/{$now->year}";

            DB::commit();

            Log::info("Certificate number generated", [
                'certificate_number' => $certificateNumber,
                'training_type_id' => $trainingTypeId,
                'issuer' => $issuer,
                'sequence_number' => $sequence->last_number
            ]);

            return $certificateNumber;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to generate certificate number", [
                'training_type_id' => $trainingTypeId,
                'issuer' => $issuer,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate expiry date based on training type validity
     */
    public function calculateExpiryDate($issueDate, $trainingTypeId)
    {
        $trainingType = TrainingType::find($trainingTypeId);
        if (!$trainingType) {
            throw new \Exception("Training type not found");
        }

        return Carbon::parse($issueDate)
            ->addMonths($trainingType->validity_months)
            ->toDateString();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $totalEmployees = Employee::where('status', 'active')->count();
        $totalTrainings = TrainingRecord::count();
        $activeCertificates = TrainingRecord::where('status', 'active')->count();
        $expiringSoon = TrainingRecord::where('status', 'expiring_soon')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();

        // Overall compliance rate
        $overallCompliance = $totalTrainings > 0 ?
            round(($activeCertificates / $totalTrainings) * 100, 2) : 0;

        return [
            'total_employees' => $totalEmployees,
            'total_trainings' => $totalTrainings,
            'active_certificates' => $activeCertificates,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
            'overall_compliance' => $overallCompliance,
            'compliance_by_department' => $this->getComplianceByDepartment(),
            'compliance_by_type' => $this->getComplianceByTrainingType(),
            'last_updated' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Get employees needing specific training type
     */
    public function getEmployeesNeedingTraining($trainingTypeId)
    {
        $trainingType = TrainingType::find($trainingTypeId);
        if (!$trainingType) {
            return collect();
        }

        // Get employees who don't have active certificates for this training type
        return Employee::where('status', 'active')
            ->whereDoesntHave('trainingRecords', function($query) use ($trainingTypeId) {
                $query->where('training_type_id', $trainingTypeId)
                      ->where('status', 'active');
            })
            ->with('department')
            ->get();
    }

    /**
     * Get training records for a specific employee
     */
    public function getEmployeeTrainingHistory($employeeId)
    {
        return TrainingRecord::with(['trainingType'])
            ->where('employee_id', $employeeId)
            ->orderBy('issue_date', 'desc')
            ->get()
            ->groupBy(function($record) {
                return $record->trainingType->name;
            })
            ->map(function($records) {
                return $records->sortByDesc('issue_date')->values();
            });
    }

    /**
     * Check if employee has valid certification for training type
     */
    public function hasValidCertification($employeeId, $trainingTypeId)
    {
        return TrainingRecord::where('employee_id', $employeeId)
            ->where('training_type_id', $trainingTypeId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get upcoming renewals (certificates expiring in next N days)
     */
    public function getUpcomingRenewals($days = 60)
    {
        return TrainingRecord::with(['employee.department', 'trainingType'])
            ->where('status', 'active')
            ->whereBetween('expiry_date', [
                Carbon::now()->toDateString(),
                Carbon::now()->addDays($days)->toDateString()
            ])
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->groupBy(function($record) {
                return $record->employee->department->name ?? 'No Department';
            });
    }

    /**
     * Calculate training costs and ROI
     */
    public function getTrainingAnalytics($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?: Carbon::now()->subYear();
        $endDate = $endDate ?: Carbon::now();

        $records = TrainingRecord::whereBetween('issue_date', [$startDate, $endDate]);

        return [
            'total_certifications' => $records->count(),
            'by_month' => $records->selectRaw('YEAR(issue_date) as year, MONTH(issue_date) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get(),
            'by_type' => $records->with('trainingType')
                ->get()
                ->groupBy('trainingType.name')
                ->map(function($records) {
                    return $records->count();
                })
                ->sortDesc(),
            'by_department' => $records->with('employee.department')
                ->get()
                ->groupBy('employee.department.name')
                ->map(function($records) {
                    return $records->count();
                })
                ->sortDesc(),
        ];
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport()
    {
        $report = [
            'generated_at' => Carbon::now()->toISOString(),
            'summary' => $this->getDashboardStats(),
            'department_details' => [],
            'training_type_details' => [],
            'critical_alerts' => [],
        ];

        // Department details
        foreach ($this->getComplianceByDepartment() as $dept) {
            $report['department_details'][] = [
                'department' => $dept->department_name,
                'total_employees' => $dept->total_employees,
                'total_certificates' => $dept->total_certificates,
                'active_certificates' => $dept->active_certificates,
                'compliance_rate' => $dept->compliance_rate,
                'status' => $dept->compliance_rate >= 90 ? 'excellent' :
                           ($dept->compliance_rate >= 70 ? 'good' : 'needs_improvement')
            ];
        }

        // Training type details
        foreach ($this->getComplianceByTrainingType() as $type) {
            $report['training_type_details'][] = [
                'training_type' => $type->training_name,
                'category' => $type->category,
                'total_records' => $type->total_records,
                'active_count' => $type->active_count,
                'expiring_count' => $type->expiring_count,
                'expired_count' => $type->expired_count,
                'active_percentage' => $type->active_percentage,
            ];
        }

        // Critical alerts
        $expiringSoon = $this->getExpiringSoon(7);
        if ($expiringSoon->count() > 0) {
            $report['critical_alerts'][] = [
                'type' => 'expiring_soon',
                'count' => $expiringSoon->count(),
                'message' => "{$expiringSoon->count()} certificates expire within 7 days"
            ];
        }

        $expired = $this->getExpired();
        if ($expired->count() > 0) {
            $report['critical_alerts'][] = [
                'type' => 'expired',
                'count' => $expired->count(),
                'message' => "{$expired->count()} certificates have expired"
            ];
        }

        return $report;
    }
}
