<?php
// app/Services/TrainingComplianceService.php

namespace App\Services;

use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TrainingComplianceService
{
    /**
     * Enhanced business rules untuk training compliance
     */

    // Notification thresholds (dalam hari)
    const EXPIRY_WARNING_DAYS = [90, 60, 30, 14, 7, 1];
    const CRITICAL_EXPIRY_DAYS = 30;
    const URGENT_EXPIRY_DAYS = 7;

    // Training categories priority
    const PRIORITY_LEVELS = [
        'MPGA' => 1,        // Highest priority
        'Safety' => 1,      // Highest priority
        'Security' => 2,    // High priority
        'Aviation' => 2,    // High priority
        'Technical' => 3,   // Medium priority
        'Quality' => 4,     // Low priority
        'Service' => 5      // Lowest priority
    ];

    /**
     * Get comprehensive compliance status for employee
     */
    public function getEmployeeComplianceStatus(Employee $employee): array
    {
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->get();
        $employeeRecords = $employee->trainingRecords()
            ->with('trainingType')
            ->get()
            ->keyBy('training_type_id');

        $compliance = [
            'overall_status' => 'compliant',
            'compliance_score' => 0,
            'total_mandatory' => $mandatoryTrainings->count(),
            'completed_mandatory' => 0,
            'critical_issues' => [],
            'warnings' => [],
            'recommendations' => [],
            'next_actions' => []
        ];

        foreach ($mandatoryTrainings as $training) {
            if (!isset($employeeRecords[$training->id])) {
                // Missing mandatory training
                $compliance['critical_issues'][] = [
                    'type' => 'missing_mandatory',
                    'training' => $training->name,
                    'priority' => $this->getPriority($training->category),
                    'action' => 'Schedule immediately'
                ];
                $compliance['overall_status'] = 'non_compliant';
            } else {
                $record = $employeeRecords[$training->id];
                $daysUntilExpiry = Carbon::parse($record->expiry_date)->diffInDays(Carbon::now(), false);

                if ($record->status === 'expired') {
                    $compliance['critical_issues'][] = [
                        'type' => 'expired',
                        'training' => $training->name,
                        'expired_days' => abs($daysUntilExpiry),
                        'priority' => $this->getPriority($training->category),
                        'action' => 'Renew immediately'
                    ];
                    $compliance['overall_status'] = 'non_compliant';
                } elseif ($daysUntilExpiry <= self::URGENT_EXPIRY_DAYS) {
                    $compliance['critical_issues'][] = [
                        'type' => 'urgent_renewal',
                        'training' => $training->name,
                        'days_left' => $daysUntilExpiry,
                        'priority' => $this->getPriority($training->category),
                        'action' => 'Schedule renewal within 3 days'
                    ];
                    if ($compliance['overall_status'] === 'compliant') {
                        $compliance['overall_status'] = 'at_risk';
                    }
                } elseif ($daysUntilExpiry <= self::CRITICAL_EXPIRY_DAYS) {
                    $compliance['warnings'][] = [
                        'type' => 'expiring_soon',
                        'training' => $training->name,
                        'days_left' => $daysUntilExpiry,
                        'priority' => $this->getPriority($training->category),
                        'action' => 'Schedule renewal'
                    ];
                    if ($compliance['overall_status'] === 'compliant') {
                        $compliance['overall_status'] = 'warning';
                    }
                } else {
                    $compliance['completed_mandatory']++;
                }
            }
        }

        // Calculate compliance score
        $compliance['compliance_score'] = $compliance['total_mandatory'] > 0
            ? round(($compliance['completed_mandatory'] / $compliance['total_mandatory']) * 100, 2)
            : 100;

        // Generate recommendations
        $compliance['recommendations'] = $this->generateRecommendations($employee, $compliance);

        return $compliance;
    }

    /**
     * Get department compliance overview
     */
    public function getDepartmentCompliance($departmentId = null): array
    {
        $query = Employee::with(['department', 'trainingRecords.trainingType']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();
        $overview = [
            'total_employees' => $employees->count(),
            'compliant' => 0,
            'at_risk' => 0,
            'warning' => 0,
            'non_compliant' => 0,
            'overall_compliance_rate' => 0,
            'critical_issues' => 0,
            'urgent_renewals' => 0,
            'by_training_type' => [],
            'priority_actions' => []
        ];

        foreach ($employees as $employee) {
            $status = $this->getEmployeeComplianceStatus($employee);

            switch ($status['overall_status']) {
                case 'compliant':
                    $overview['compliant']++;
                    break;
                case 'at_risk':
                    $overview['at_risk']++;
                    break;
                case 'warning':
                    $overview['warning']++;
                    break;
                case 'non_compliant':
                    $overview['non_compliant']++;
                    break;
            }

            $overview['critical_issues'] += count($status['critical_issues']);

            foreach ($status['critical_issues'] as $issue) {
                if ($issue['type'] === 'urgent_renewal' && $issue['days_left'] <= 3) {
                    $overview['urgent_renewals']++;
                }
            }
        }

        // Calculate overall compliance rate
        $overview['overall_compliance_rate'] = $overview['total_employees'] > 0
            ? round(($overview['compliant'] / $overview['total_employees']) * 100, 2)
            : 100;

        // Get priority actions
        $overview['priority_actions'] = $this->getPriorityActions($departmentId);

        return $overview;
    }

    /**
     * Enhanced certificate numbering dengan business rules
     */
    public function generateCertificateNumber(TrainingType $trainingType, Employee $employee): string
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->format('m');

        // Format: [TRAINING_CODE]/[DEPT]/[YEAR][MONTH]/[SEQUENCE]
        // Example: MPGA-BASIC/GSE/202408/001

        $prefix = sprintf(
            '%s/%s/%s%s',
            $trainingType->code,
            $employee->department->code,
            $year,
            $month
        );

        // Get next sequence number
        $lastRecord = TrainingRecord::where('certificate_number', 'LIKE', $prefix . '/%')
            ->orderBy('certificate_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastRecord) {
            $parts = explode('/', $lastRecord->certificate_number);
            $lastSequence = intval(end($parts));
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s/%03d', $prefix, $sequence);
    }

    /**
     * Automatic status update dengan business logic
     */
    public function updateTrainingStatuses(): array
    {
        $updated = [
            'active_to_expiring' => 0,
            'expiring_to_expired' => 0,
            'notifications_sent' => 0
        ];

        // Update expiring soon
        $expiringRecords = TrainingRecord::where('status', 'active')
            ->where('expiry_date', '<=', Carbon::now()->addDays(self::CRITICAL_EXPIRY_DAYS))
            ->where('expiry_date', '>', Carbon::now())
            ->get();

        foreach ($expiringRecords as $record) {
            $record->update(['status' => 'expiring_soon']);
            $updated['active_to_expiring']++;

            // Send notification
            $this->sendExpiryNotification($record);
            $updated['notifications_sent']++;
        }

        // Update expired
        $expiredRecords = TrainingRecord::where('status', 'expiring_soon')
            ->where('expiry_date', '<=', Carbon::now())
            ->get();

        foreach ($expiredRecords as $record) {
            $record->update(['status' => 'expired']);
            $updated['expiring_to_expired']++;

            // Send urgent notification
            $this->sendUrgentExpiryNotification($record);
            $updated['notifications_sent']++;
        }

        Log::info('Training status update completed', $updated);

        return $updated;
    }

    /**
     * Validation rules untuk training record
     */
    public function validateTrainingRecord(array $data): array
    {
        $errors = [];

        // Validate employee exists and is active
        if (isset($data['employee_id'])) {
            $employee = Employee::find($data['employee_id']);
            if (!$employee) {
                $errors['employee_id'] = 'Employee not found';
            } elseif ($employee->status !== 'active') {
                $errors['employee_id'] = 'Employee is not active';
            }
        }

        // Validate training type
        if (isset($data['training_type_id'])) {
            $trainingType = TrainingType::find($data['training_type_id']);
            if (!$trainingType) {
                $errors['training_type_id'] = 'Training type not found';
            } elseif (!$trainingType->is_active) {
                $errors['training_type_id'] = 'Training type is not active';
            }
        }

        // Validate dates
        if (isset($data['issue_date']) && isset($data['expiry_date'])) {
            $issueDate = Carbon::parse($data['issue_date']);
            $expiryDate = Carbon::parse($data['expiry_date']);

            if ($expiryDate->lessThanOrEqualTo($issueDate)) {
                $errors['expiry_date'] = 'Expiry date must be after issue date';
            }

            if ($issueDate->greaterThan(Carbon::now())) {
                $errors['issue_date'] = 'Issue date cannot be in the future';
            }
        }

        // Validate duplicate certificate
        if (isset($data['certificate_number']) && isset($data['id'])) {
            $duplicate = TrainingRecord::where('certificate_number', $data['certificate_number'])
                ->where('id', '!=', $data['id'])
                ->first();

            if ($duplicate) {
                $errors['certificate_number'] = 'Certificate number already exists';
            }
        }

        return $errors;
    }

    /**
     * Business rules untuk mandatory training
     */
    public function checkMandatoryTrainingCompliance(Employee $employee): array
    {
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->get();
        $employeeTrainings = $employee->trainingRecords()
            ->whereIn('training_type_id', $mandatoryTrainings->pluck('id'))
            ->where('status', '!=', 'expired')
            ->get()
            ->keyBy('training_type_id');

        $missing = [];
        $expiring = [];

        foreach ($mandatoryTrainings as $training) {
            if (!isset($employeeTrainings[$training->id])) {
                $missing[] = [
                    'training' => $training,
                    'priority' => $this->getPriority($training->category),
                    'grace_period_days' => $this->getGracePeriod($training->category)
                ];
            } else {
                $record = $employeeTrainings[$training->id];
                $daysUntilExpiry = Carbon::parse($record->expiry_date)->diffInDays(Carbon::now(), false);

                if ($daysUntilExpiry <= self::CRITICAL_EXPIRY_DAYS) {
                    $expiring[] = [
                        'training' => $training,
                        'record' => $record,
                        'days_left' => $daysUntilExpiry,
                        'priority' => $this->getPriority($training->category)
                    ];
                }
            }
        }

        return [
            'missing' => $missing,
            'expiring' => $expiring,
            'is_compliant' => empty($missing) && empty(array_filter($expiring, fn($e) => $e['days_left'] <= 0))
        ];
    }

    private function getPriority(string $category): int
    {
        return self::PRIORITY_LEVELS[$category] ?? 5;
    }

    private function getGracePeriod(string $category): int
    {
        $gracePeriods = [
            'MPGA' => 0,        // No grace period
            'Safety' => 0,      // No grace period
            'Security' => 7,    // 7 days grace period
            'Aviation' => 14,   // 14 days grace period
            'Technical' => 30,  // 30 days grace period
            'Quality' => 60,    // 60 days grace period
            'Service' => 90     // 90 days grace period
        ];

        return $gracePeriods[$category] ?? 30;
    }

    private function generateRecommendations(Employee $employee, array $compliance): array
    {
        $recommendations = [];

        // Based on critical issues
        if (count($compliance['critical_issues']) > 0) {
            $recommendations[] = [
                'type' => 'immediate_action',
                'message' => 'Schedule immediate training for ' . count($compliance['critical_issues']) . ' critical items',
                'priority' => 'high'
            ];
        }

        // Based on compliance score
        if ($compliance['compliance_score'] < 80) {
            $recommendations[] = [
                'type' => 'compliance_improvement',
                'message' => 'Focus on mandatory training completion to improve compliance score',
                'priority' => 'medium'
            ];
        }

        // Department-specific recommendations
        $deptCode = $employee->department->code;
        $deptRecommendations = $this->getDepartmentSpecificRecommendations($deptCode);
        $recommendations = array_merge($recommendations, $deptRecommendations);

        return $recommendations;
    }

    private function getDepartmentSpecificRecommendations(string $deptCode): array
    {
        $recommendations = [
            'GSE' => [
                ['type' => 'skill_development', 'message' => 'Consider Equipment Operation Training for skill enhancement', 'priority' => 'low']
            ],
            'SEC' => [
                ['type' => 'compliance', 'message' => 'Ensure Aviation Security Training is always up to date', 'priority' => 'high']
            ],
            'PAX' => [
                ['type' => 'service_quality', 'message' => 'Customer Service Excellence training recommended', 'priority' => 'medium']
            ]
        ];

        return $recommendations[$deptCode] ?? [];
    }

    private function getPriorityActions($departmentId = null): array
    {
        $query = TrainingRecord::with(['employee.department', 'trainingType'])
            ->where('status', 'expired')
            ->orWhere(function($q) {
                $q->where('status', 'expiring_soon')
                  ->where('expiry_date', '<=', Carbon::now()->addDays(7));
            });

        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->map(function($record) {
                return [
                    'employee' => $record->employee->name,
                    'employee_id' => $record->employee->employee_id,
                    'training' => $record->trainingType->name,
                    'status' => $record->status,
                    'expiry_date' => $record->expiry_date,
                    'days_overdue' => $record->status === 'expired'
                        ? Carbon::parse($record->expiry_date)->diffInDays(Carbon::now())
                        : 0,
                    'priority' => $this->getPriority($record->trainingType->category)
                ];
            })
            ->toArray();
    }

    private function sendExpiryNotification(TrainingRecord $record)
    {
        // Implementation for sending notification emails
        // This would integrate with your notification system
        Log::info('Expiry notification sent', [
            'employee' => $record->employee->name,
            'training' => $record->trainingType->name,
            'expiry_date' => $record->expiry_date
        ]);
    }

    private function sendUrgentExpiryNotification(TrainingRecord $record)
    {
        // Implementation for sending urgent notification emails
        // This would integrate with your notification system
        Log::info('Urgent expiry notification sent', [
            'employee' => $record->employee->name,
            'training' => $record->trainingType->name,
            'expired_date' => $record->expiry_date
        ]);
    }
}
