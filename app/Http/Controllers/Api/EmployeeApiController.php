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
                'next_expiry' => $this->getNextExpiryDate($certificates)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'certificates' => CertificateResource::collection($certificates),
                    'grouped_certificates' => [
                        'active' => CertificateResource::collection($groupedCertificates['active']),
                        'expiring_soon' => CertificateResource::collection($groupedCertificates['expiring_soon']),
                        'expired' => CertificateResource::collection($groupedCertificates['expired']),
                        'permanent' => CertificateResource::collection($groupedCertificates['permanent'])
                    ],
                    'statistics' => $stats
                ],
                'message' => 'Certificates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee training schedule
     */
    public function getTrainingSchedule(Employee $employee, Request $request): JsonResponse
    {
        try {
            $this->authorizeEmployeeAccess($employee);

            $daysAhead = $request->get('days', 30);
            $includeCompleted = $request->boolean('include_completed', false);

            $query = TrainingRecord::where('employee_id', $employee->id)
                ->with([
                    'trainingType.category',
                    'trainingProvider'
                ])
                ->whereNotNull('training_date')
                ->where('training_date', '>=', now())
                ->where('training_date', '<=', now()->addDays($daysAhead));

            if (!$includeCompleted) {
                $query->whereNotIn('status', ['completed', 'cancelled']);
            }

            $upcomingTrainings = $query->orderBy('training_date')->get();

            // Group by date for easier mobile consumption
            $groupedByDate = $upcomingTrainings->groupBy(function ($training) {
                return $training->training_date->format('Y-m-d');
            });

            $schedule = [];
            foreach ($groupedByDate as $date => $trainings) {
                $schedule[] = [
                    'date' => $date,
                    'date_formatted' => Carbon::parse($date)->format('F j, Y'),
                    'day_of_week' => Carbon::parse($date)->format('l'),
                    'trainings' => TrainingRecordResource::collection($trainings)
                ];
            }

            // Get overdue trainings
            $overdueTrainings = TrainingRecord::where('employee_id', $employee->id)
                ->whereIn('status', ['registered', 'in_progress'])
                ->where('training_date', '<', now())
                ->with(['trainingType.category', 'trainingProvider'])
                ->orderBy('training_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'upcoming_schedule' => $schedule,
                    'overdue_trainings' => TrainingRecordResource::collection($overdueTrainings),
                    'summary' => [
                        'upcoming_count' => $upcomingTrainings->count(),
                        'overdue_count' => $overdueTrainings->count(),
                        'next_training_date' => $upcomingTrainings->first()?->training_date?->format('Y-m-d'),
                        'days_until_next' => $upcomingTrainings->first() ?
                            now()->diffInDays($upcomingTrainings->first()->training_date) : null
                    ]
                ],
                'message' => 'Training schedule retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve training schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee compliance status
     */
    public function getComplianceStatus(Employee $employee): JsonResponse
    {
        try {
            $this->authorizeEmployeeAccess($employee);

            $complianceSummary = $employee->getTrainingComplianceSummary();

            // Get detailed compliance information
            $mandatoryTrainings = \App\Models\TrainingType::where('is_mandatory', true)
                ->with(['category'])
                ->get();

            $complianceDetails = [];
            foreach ($mandatoryTrainings as $trainingType) {
                $latestRecord = TrainingRecord::where('employee_id', $employee->id)
                    ->where('training_type_id', $trainingType->id)
                    ->latest()
                    ->with(['certificates'])
                    ->first();

                $status = 'missing';
                $daysUntilExpiry = null;
                $actionRequired = 'Schedule training';
                $priority = 'medium';

                if ($latestRecord) {
                    $status = $latestRecord->compliance_status;
                    $daysUntilExpiry = $latestRecord->days_until_expiry;

                    switch ($status) {
                        case 'compliant':
                            $actionRequired = null;
                            $priority = 'low';
                            break;
                        case 'expiring_soon':
                            $actionRequired = 'Schedule renewal';
                            $priority = 'high';
                            break;
                        case 'expired':
                            $actionRequired = 'Urgent renewal required';
                            $priority = 'critical';
                            break;
                    }
                }

                $complianceDetails[] = [
                    'training_type' => [
                        'id' => $trainingType->id,
                        'name' => $trainingType->name,
                        'code' => $trainingType->code,
                        'category' => $trainingType->category?->name,
                        'validity_months' => $trainingType->validity_months
                    ],
                    'status' => $status,
                    'latest_record' => $latestRecord ? new TrainingRecordResource($latestRecord) : null,
                    'days_until_expiry' => $daysUntilExpiry,
                    'action_required' => $actionRequired,
                    'priority' => $priority,
                    'certificates' => $latestRecord ?
                        CertificateResource::collection($latestRecord->certificates) : []
                ];
            }

            // Calculate risk score
            $riskScore = $this->calculateRiskScore($complianceDetails);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'compliance_summary' => $complianceSummary,
                    'compliance_details' => $complianceDetails,
                    'risk_assessment' => [
                        'risk_score' => $riskScore,
                        'risk_level' => $this->getRiskLevel($riskScore),
                        'critical_items' => collect($complianceDetails)
                            ->where('priority', 'critical')
                            ->count(),
                        'high_priority_items' => collect($complianceDetails)
                            ->where('priority', 'high')
                            ->count()
                    ],
                    'recommendations' => $this->getComplianceRecommendations($complianceDetails)
                ],
                'message' => 'Compliance status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve compliance status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee profile with training summary
     */
    public function getProfile(Employee $employee): JsonResponse
    {
        try {
            $this->authorizeEmployeeAccess($employee);

            $employee->load(['department', 'supervisor']);

            // Get training statistics
            $trainingStats = [
                'total_trainings' => $employee->trainingRecords()->count(),
                'completed_trainings' => $employee->completedTrainingRecords()->count(),
                'total_hours' => $employee->completedTrainingRecords()->sum('training_hours'),
                'average_score' => $employee->completedTrainingRecords()->avg('score'),
                'this_year_trainings' => $employee->completedTrainingRecords()
                    ->whereYear('completion_date', date('Y'))
                    ->count(),
                'this_year_hours' => $employee->completedTrainingRecords()
                    ->whereYear('completion_date', date('Y'))
                    ->sum('training_hours')
            ];

            // Get recent achievements
            $recentAchievements = $this->getRecentAchievements($employee);

            // Get upcoming milestones
            $upcomingMilestones = $this->getUpcomingMilestones($employee);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => new EmployeeResource($employee),
                    'training_statistics' => $trainingStats,
                    'recent_achievements' => $recentAchievements,
                    'upcoming_milestones' => $upcomingMilestones,
                    'compliance_summary' => $employee->getTrainingComplianceSummary()
                ],
                'message' => 'Employee profile retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employee profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee profile (limited fields for mobile)
     */
    public function updateProfile(Employee $employee, Request $request): JsonResponse
    {
        try {
            $this->authorizeEmployeeAccess($employee, true); // Require self or admin

            $request->validate([
                'phone' => 'nullable|string|max:20',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500'
            ]);

            $employee->update($request->only([
                'phone',
                'emergency_contact_name',
                'emergency_contact_phone',
                'address'
            ]));

            return response()->json([
                'success' => true,
                'data' => new EmployeeResource($employee->fresh()),
                'message' => 'Profile updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
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

        return $nextExpiry ? $nextExpiry->expiry_date->format('Y-m-d') : null;
    }

    protected function calculateRiskScore(array $complianceDetails): int
    {
        $score = 0;

        foreach ($complianceDetails as $detail) {
            switch ($detail['priority']) {
                case 'critical':
                    $score += 25;
                    break;
                case 'high':
                    $score += 15;
                    break;
                case 'medium':
                    $score += 5;
                    break;
            }
        }

        return min($score, 100); // Cap at 100
    }

    protected function getRiskLevel(int $score): string
    {
        if ($score >= 50) return 'high';
        if ($score >= 25) return 'medium';
        return 'low';
    }

    protected function getComplianceRecommendations(array $complianceDetails): array
    {
        $recommendations = [];

        $criticalItems = collect($complianceDetails)->where('priority', 'critical');
        $highItems = collect($complianceDetails)->where('priority', 'high');

        if ($criticalItems->count() > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'title' => 'Immediate Action Required',
                'message' => "You have {$criticalItems->count()} expired mandatory training(s). Contact HR immediately to schedule renewal.",
                'action' => 'contact_hr'
            ];
        }

        if ($highItems->count() > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Schedule Renewals Soon',
                'message' => "You have {$highItems->count()} training(s) expiring soon. Plan your renewals within the next 2 weeks.",
                'action' => 'schedule_training'
            ];
        }

        return $recommendations;
    }

    protected function getRecentAchievements(Employee $employee): array
    {
        $achievements = [];

        $recentCompletions = $employee->completedTrainingRecords()
            ->where('completion_date', '>=', now()->subMonths(6))
            ->with('trainingType')
            ->get();

        if ($recentCompletions->count() >= 3) {
            $achievements[] = [
                'type' => 'training_streak',
                'title' => 'Learning Enthusiast',
                'description' => 'Completed 3+ trainings in the last 6 months',
                'earned_date' => $recentCompletions->max('completion_date')
            ];
        }

        $highScores = $recentCompletions->where('score', '>=', 90);
        if ($highScores->count() >= 2) {
            $achievements[] = [
                'type' => 'high_performance',
                'title' => 'Excellence Award',
                'description' => 'Scored 90% or higher on multiple trainings',
                'earned_date' => $highScores->max('completion_date')
            ];
        }

        return $achievements;
    }

    protected function getUpcomingMilestones(Employee $employee): array
    {
        $milestones = [];

        // Years of service milestone
        if ($employee->hire_date) {
            $nextAnniversary = $employee->hire_date->copy()->addYears($employee->years_of_service + 1);
            if ($nextAnniversary->isFuture() && $nextAnniversary->diffInDays() <= 90) {
                $milestones[] = [
                    'type' => 'service_anniversary',
                    'title' => 'Service Anniversary',
                    'description' => ($employee->years_of_service + 1) . ' years of service',
                    'due_date' => $nextAnniversary->format('Y-m-d'),
                    'days_until' => $nextAnniversary->diffInDays()
                ];
            }
        }

        // Training hours milestone
        $currentYearHours = $employee->completedTrainingRecords()
            ->whereYear('completion_date', date('Y'))
            ->sum('training_hours');

        $nextMilestone = ceil($currentYearHours / 10) * 10; // Next 10-hour milestone
        if ($nextMilestone - $currentYearHours <= 5) {
            $milestones[] = [
                'type' => 'training_hours',
                'title' => 'Training Hours Milestone',
                'description' => "Reach {$nextMilestone} training hours this year",
                'current_progress' => $currentYearHours,
                'target' => $nextMilestone,
                'percentage' => round(($currentYearHours / $nextMilestone) * 100, 1)
            ];
        }

        return $milestones;
    }
}
