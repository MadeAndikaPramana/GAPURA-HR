<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Employee;
use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\CertificateResource;
use App\Http\Resources\TrainingRecordResource;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmployeeApiController extends Controller
{
    /**
     * Get employee certificates
     */
    public function getCertificates(Employee $employee): JsonResponse
    {
        try {
            // Check if user can access this employee's data
            $this->authorizeEmployeeAccess($employee);

            $certificates = Certificate::whereHas('trainingRecord', function ($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })
                ->with([
                    'trainingRecord.trainingType.category',
                    'trainingRecord.trainingProvider'
                ])
                ->orderBy('issue_date', 'desc')
                ->get();

            // Group certificates by status
            $groupedCertificates = [
                'active' => $certificates->filter(fn($cert) => $cert->status === 'active'),
                'expiring_soon' => $certificates->filter(fn($cert) => $cert->status === 'expiring_soon'),
                'expired' => $certificates->filter(fn($cert) => $cert->status === 'expired'),
                'permanent' => $certificates->filter(fn($cert) => $cert->status === 'permanent')
            ];

            // Calculate statistics
            $stats = [
                'total_certificates' => $certificates->count(),
                'active_certificates' => $groupedCertificates['active']->count(),
                'expiring_soon' => $groupedCertificates['expiring_soon']->count(),
                'expired_certificates' => $groupedCertificates['expired']->count(),
                'compliance_rate' => $this->calculateEmployeeComplianceRate($employee),
                'next_expiry_date' => $this->getNextExpiryDate($certificates),
                'last_updated' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'certificates' => CertificateResource::collection($certificates),
                    'grouped_certificates' => $groupedCertificates,
                    'statistics' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee certificates',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get employee training schedule
     */
    public function getTrainingSchedule(Employee $employee): JsonResponse
    {
        try {
            $this->authorizeEmployeeAccess($employee);

            $upcomingTrainings = TrainingRecord::where('employee_id', $employee->id)
                ->where('status', 'scheduled')
                ->with(['trainingType', 'trainingProvider'])
                ->orderBy('scheduled_date', 'asc')
                ->get();

            $recentTrainings = TrainingRecord::where('employee_id', $employee->id)
                ->where('status', 'completed')
                ->with(['trainingType', 'trainingProvider'])
                ->orderBy('completion_date', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'upcoming_trainings' => TrainingRecordResource::collection($upcomingTrainings),
                    'recent_trainings' => TrainingRecordResource::collection($recentTrainings),
                    'statistics' => [
                        'upcoming_count' => $upcomingTrainings->count(),
                        'completed_count' => $recentTrainings->count(),
                        'next_training_date' => $upcomingTrainings->first()?->scheduled_date
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch training schedule',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get employee compliance status
     */
    public function getComplianceStatus(Employee $employee): JsonResponse
    {
        try {
            $this->authorizeEmployeeAccess($employee);

            // Get mandatory trainings
            $mandatoryTrainings = \App\Models\TrainingType::where('is_mandatory', true)->get();

            // Check compliance for each mandatory training
            $complianceDetails = [];
            foreach ($mandatoryTrainings as $trainingType) {
                $latestRecord = TrainingRecord::where('employee_id', $employee->id)
                    ->where('training_type_id', $trainingType->id)
                    ->orderBy('completion_date', 'desc')
                    ->first();

                $complianceDetails[] = [
                    'training_type' => $trainingType->name,
                    'training_type_id' => $trainingType->id,
                    'is_compliant' => $latestRecord ? $latestRecord->compliance_status === 'compliant' : false,
                    'status' => $latestRecord?->compliance_status ?? 'missing',
                    'last_completed' => $latestRecord?->completion_date,
                    'expiry_date' => $latestRecord?->expiry_date,
                    'days_until_expiry' => $latestRecord?->expiry_date ?
                        now()->diffInDays($latestRecord->expiry_date, false) : null
                ];
            }

            // Calculate overall compliance
            $compliantCount = collect($complianceDetails)->where('is_compliant', true)->count();
            $overallCompliance = $mandatoryTrainings->count() > 0 ?
                round(($compliantCount / $mandatoryTrainings->count()) * 100, 1) : 100;

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'overall_compliance_rate' => $overallCompliance,
                    'compliance_status' => $overallCompliance >= 90 ? 'compliant' :
                        ($overallCompliance >= 70 ? 'warning' : 'non_compliant'),
                    'mandatory_trainings_count' => $mandatoryTrainings->count(),
                    'compliant_trainings_count' => $compliantCount,
                    'compliance_details' => $complianceDetails,
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch compliance status',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Helper Methods
     */
    protected function authorizeEmployeeAccess(Employee $employee, bool $requireSelfOrAdmin = false)
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Unauthorized access');
        }

        if ($requireSelfOrAdmin) {
            if ($user->id !== $employee->id && !$user->hasRole(['admin', 'hr'])) {
                throw new \Exception('Insufficient permissions');
            }
        } else {
            // For viewing, allow self, supervisor, HR, and admin
            if ($user->id !== $employee->id &&
                $user->id !== $employee->supervisor_id &&
                !$user->hasRole(['admin', 'hr', 'manager'])) {
                throw new \Exception('Insufficient permissions');
            }
        }
    }

    protected function calculateEmployeeComplianceRate(Employee $employee): float
    {
        $mandatoryTrainings = \App\Models\TrainingType::where('is_mandatory', true)->count();

        if ($mandatoryTrainings === 0) return 100.0;

        $compliantTrainings = TrainingRecord::where('employee_id', $employee->id)
            ->whereHas('trainingType', function ($query) {
                $query->where('is_mandatory', true);
            })
            ->where('compliance_status', 'compliant')
            ->distinct('training_type_id')
            ->count();

        return round(($compliantTrainings / $mandatoryTrainings) * 100, 1);
    }

    protected function getNextExpiryDate($certificates): ?string
    {
        $nextExpiry = $certificates
            ->filter(fn($cert) => $cert->expiry_date && $cert->expiry_date > now())
            ->sortBy('expiry_date')
            ->first();

        return $nextExpiry ? $nextExpiry->expiry_date->toDateString() : null;
    }
}
