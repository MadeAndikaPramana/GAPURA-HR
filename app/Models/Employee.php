<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;

class Employee extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'phone',
        'department_id',
        'position',
        'position_level',
        'employment_type',
        'hire_date',
        'supervisor_id',
        'status',
        'background_check_date',
        'background_check_status',
        'background_check_notes',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'profile_photo_path'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'years_of_service',
        'compliance_status',
        'total_certificates',
        'active_certificates'
    ];

    /**
     * Get the department this employee belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the supervisor of this employee
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Get all employees supervised by this employee
     */
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * Get departments managed by this employee
     */
    public function managedDepartments()
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    /**
     * Get all certificates for this employee (a training record is a certificate)
     */
    public function certificates()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get completed certificates
     */
    public function completedCertificates()
    {
        return $this->certificates()->completed();
    }

    /**
     * Get active/compliant certificates
     */
    public function activeCertificates()
    {
        return $this->certificates()->active();
    }

    /**
     * Get expired certificates
     */
    public function expiredCertificates()
    {
        return $this->certificates()->expired();
    }

    /**
     * Get certificates expiring soon
     */
    public function expiringSoonCertificates()
    {
        return $this->certificates()->expiringSoon();
    }

    // /**
    //  * Get notifications for this employee
    //  */
    // public function notifications()
    // {
    //     return $this->hasMany(\App\Models\Notification::class, 'recipient_id');
    // }

    // /**
    //  * Get unread notifications
    //  */
    // public function unreadNotifications()
    // {
    //     return $this->hasMany(\App\Models\Notification::class, 'recipient_id')
    //                 ->where('status', '!=', 'read');
    // }

    /**
     * Get training records created by this employee
     */
    public function createdTrainingRecords()
    {
        return $this->hasMany(TrainingRecord::class, 'created_by_id');
    }

    // /**
    //  * Get certificates verified by this employee
    //  */
    // public function verifiedCertificates()
    // {
    //     return $this->hasMany(Certificate::class, 'verified_by_id');
    // }

    /**
     * Scope for active employees
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for employees by department
     */
    public function scopeByDepartment(Builder $query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for employees by position level
     */
    public function scopeByLevel(Builder $query, $level)
    {
        return $query->where('position_level', $level);
    }

    /**
     * Scope for search functionality
     */
    public function scopeSearch(Builder $query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('employee_id', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('position', 'like', "%{$term}%");
        });
    }

    /**
     * Scope for employees with compliance issues
     */
    public function scopeWithComplianceIssues(Builder $query)
    {
        return $query->whereHas('trainingRecords', function ($q) {
            $q->where('compliance_status', 'expired')
              ->orWhere('compliance_status', 'expiring_soon');
        });
    }

    /**
     * Scope for employees missing mandatory training
     */
    public function scopeMissingMandatoryTraining(Builder $query)
    {
        return $query->whereDoesntHave('trainingRecords', function ($q) {
            $q->whereHas('trainingType', function ($typeQuery) {
                $typeQuery->where('is_mandatory', true);
            })->where('compliance_status', 'compliant');
        });
    }

    /**
     * Get full name (alias for name)
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Calculate years of service
     */
    public function getYearsOfServiceAttribute()
    {
        if (!$this->hire_date) return 0;

        return $this->hire_date->diffInYears(now());
    }

    /**
     * Get overall compliance status
     */
    public function getComplianceStatusAttribute()
    {
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->pluck('id');

        if ($mandatoryTrainings->isEmpty()) {
            return 'compliant';
        }

        $employeeTrainings = $this->certificates()
            ->whereIn('training_type_id', $mandatoryTrainings)
            ->get();

        $hasExpired = $employeeTrainings->contains('compliance_status', 'expired');
        $hasExpiringSoon = $employeeTrainings->contains('compliance_status', 'expiring_soon');
        $missingTrainings = $mandatoryTrainings->count() - $employeeTrainings->count();

        if ($hasExpired || $missingTrainings > 0) {
            return 'non_compliant';
        } elseif ($hasExpiringSoon) {
            return 'expiring_soon';
        }

        return 'compliant';
    }

    /**
     * Get total certificates count
     */
    public function getTotalCertificatesAttribute()
    {
        return $this->certificates()->count();
    }

    /**
     * Get active certificates count
     */
    public function getActiveCertificatesAttribute()
    {
        return $this->activeCertificates()->count();
    }

    /**
     * Check if employee has specific training
     */
    public function hasTraining($trainingTypeId, $mustBeActive = true)
    {
        $query = $this->certificates()->where('training_type_id', $trainingTypeId);

        if ($mustBeActive) {
            $query->where('compliance_status', 'compliant');
        }

        return $query->exists();
    }

    /**
     * Check if employee has valid certificate for training type
     */
    public function hasValidCertificate($trainingTypeId)
    {
        return $this->certificates()
            ->where('training_type_id', $trainingTypeId)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            })
            ->exists();
    }

    /**
     * Get employee's training compliance summary
     */
    public function getTrainingComplianceSummary()
    {
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->get();
        $summary = [
            'total_mandatory' => $mandatoryTrainings->count(),
            'completed' => 0,
            'compliant' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'missing' => 0,
            'details' => []
        ];

        foreach ($mandatoryTrainings as $trainingType) {
            $certificate = $this->certificates()
                ->where('training_type_id', $trainingType->id)
                ->latest()
                ->first();

            if (!$certificate) {
                $summary['missing']++;
                $summary['details'][] = [
                    'training_type' => $trainingType->name,
                    'status' => 'missing',
                    'action_required' => 'Schedule training'
                ];
            } else {
                $summary['completed']++;

                switch ($certificate->compliance_status) {
                    case 'compliant':
                        $summary['compliant']++;
                        break;
                    case 'expiring_soon':
                        $summary['expiring_soon']++;
                        break;
                    case 'expired':
                        $summary['expired']++;
                        break;
                }

                $summary['details'][] = [
                    'training_type' => $trainingType->name,
                    'status' => $certificate->compliance_status,
                    'expiry_date' => $certificate->expiry_date,
                    'days_until_expiry' => $certificate->days_until_expiry,
                    'action_required' => $certificate->getRenewalRecommendation()['action'] ?? null
                ];
            }
        }

        $summary['compliance_rate'] = $summary['total_mandatory'] > 0
            ? round(($summary['compliant'] / $summary['total_mandatory']) * 100, 2)
            : 100;

        return $summary;
    }

    /**
     * Get upcoming training schedule
     */
    public function getUpcomingTrainings($days = 30)
    {
        return $this->certificates()
            ->where('status', 'registered')
            ->whereNotNull('training_date')
            ->whereBetween('training_date', [now(), now()->addDays($days)])
            ->with(['trainingType', 'trainingProvider'])
            ->orderBy('training_date')
            ->get();
    }

    /**
     * Get training history with analytics
     */
    public function getTrainingHistory($year = null)
    {
        $query = $this->completedCertificates()
            ->with(['trainingType.category', 'trainingProvider']);

        if ($year) {
            $query->whereYear('completion_date', $year);
        }

        $trainings = $query->orderBy('completion_date', 'desc')->get();

        return [
            'trainings' => $trainings,
            'analytics' => [
                'total_trainings' => $trainings->count(),
                'total_hours' => $trainings->sum('training_hours'),
                'total_cost' => $trainings->sum('cost'),
                'average_score' => $trainings->avg('score'),
                'certificates_earned' => $trainings->count(),
                'by_category' => $trainings->groupBy('trainingType.category.name')->map->count(),
                'by_year' => $trainings->groupBy(fn($t) => $t->completion_date->year)->map->count()
            ]
        ];
    }

    /**
     * Get employee's direct reports with their compliance status
     */
    public function getTeamComplianceStatus()
    {
        return $this->subordinates()
            ->with(['department', 'certificates.trainingType'])
            ->get()
            ->map(function ($employee) {
                return [
                    'employee' => $employee,
                    'compliance_summary' => $employee->getTrainingComplianceSummary()
                ];
            });
    }

    /**
     * Assign training to employee
     */
    public function assignTraining($trainingTypeId, array $additionalData = [])
    {
        $trainingType = TrainingType::findOrFail($trainingTypeId);

        // Check if employee already has active training of this type
        $existingRecord = $this->certificates()
            ->where('training_type_id', $trainingTypeId)
            ->where('compliance_status', 'compliant')
            ->first();

        if ($existingRecord) {
            return [
                'success' => false,
                'message' => 'Employee already has active training of this type',
                'existing_record' => $existingRecord
            ];
        }

        $trainingData = array_merge([
            'employee_id' => $this->id,
            'training_type_id' => $trainingTypeId,
            'training_provider_id' => $trainingType->training_provider_id,
            'status' => 'registered',
            'cost' => $trainingType->cost_per_person,
            'training_hours' => $trainingType->duration_hours,
            'passing_score' => 70.00,
            'created_by_id' => Auth::id()
        ], $additionalData);

        $trainingRecord = TrainingRecord::create($trainingData);

        // Send notification
        // app(\App\Services\NotificationService::class)
        //     ->sendTrainingAssignmentNotification($trainingRecord);

        return [
            'success' => true,
            'message' => 'Training assigned successfully',
            'training_record' => $trainingRecord
        ];
    }

    /**
     * Get employee dashboard data
     */
    public function getDashboardData()
    {
        return [
            'compliance_summary' => $this->getTrainingComplianceSummary(),
            'upcoming_trainings' => $this->getUpcomingTrainings(),
            'recent_certificates' => $this->certificates()
                ->with(['trainingType'])
                ->latest()
                ->limit(5)
                ->get(),
            'expiring_certificates' => $this->expiringSoonCertificates()
                ->with(['trainingType'])
                ->orderBy('expiry_date')
                ->get(),
            // 'unread_notifications' => $this->unreadNotifications()
            //     ->orderBy('priority', 'desc')
            //     ->orderBy('created_at', 'desc')
            //     ->limit(10)
            //     ->get(),
            'training_progress' => [
                'total_hours' => $this->completedCertificates()->sum('training_hours'),
                'this_year_hours' => $this->completedCertificates()
                    ->whereYear('completion_date', date('Y'))
                    ->sum('training_hours'),
                'certificates_earned' => $this->certificates()->count(),
                'this_year_certificates' => $this->certificates()
                    ->whereYear('issue_date', date('Y'))
                    ->count()
            ]
        ];
    }

    /**
     * Get employee performance metrics
     */
    public function getPerformanceMetrics($year = null)
    {
        $year = $year ?: date('Y');

        $trainings = $this->completedCertificates()
            ->whereYear('completion_date', $year)
            ->get();

        return [
            'training_completion_rate' => $this->calculateCompletionRate($year),
            'average_score' => $trainings->avg('score'),
            'total_training_hours' => $trainings->sum('training_hours'),
            'compliance_score' => $this->calculateComplianceScore(),
            'improvement_areas' => $this->identifyImprovementAreas($trainings),
            'achievements' => $this->getTrainingAchievements($year)
        ];
    }

    /**
     * Calculate training completion rate
     */
    private function calculateCompletionRate($year)
    {
        $scheduled = $this->certificates()
            ->whereYear('training_date', $year)
            ->count();

        $completed = $this->completedCertificates()
            ->whereYear('completion_date', $year)
            ->count();

        return $scheduled > 0 ? round(($completed / $scheduled) * 100, 2) : 0;
    }

    /**
     * Calculate compliance score
     */
    private function calculateComplianceScore()
    {
        $summary = $this->getTrainingComplianceSummary();
        return $summary['compliance_rate'];
    }

    /**
     * Identify areas for improvement
     */
    private function identifyImprovementAreas($trainings)
    {
        $areas = [];

        $lowScoreTrainings = $trainings->where('score', '<', 80);
        if ($lowScoreTrainings->count() > 0) {
            $areas[] = 'Consider additional study for low-scoring training areas';
        }

        $expiredCount = $this->expiredCertificates()->count();
        if ($expiredCount > 0) {
            $areas[] = 'Renew expired certificates to maintain compliance';
        }

        return $areas;
    }

    /**
     * Get training achievements
     */
    private function getTrainingAchievements($year)
    {
        $achievements = [];

        $trainings = $this->completedCertificates()
            ->whereYear('completion_date', $year)
            ->get();

        if ($trainings->count() >= 5) {
            $achievements[] = 'Training Enthusiast - Completed 5+ trainings this year';
        }

        $avgScore = $trainings->avg('score');
        if ($avgScore >= 90) {
            $achievements[] = 'Excellence Award - Average score above 90%';
        }

        $totalHours = $trainings->sum('training_hours');
        if ($totalHours >= 40) {
            $achievements[] = 'Dedicated Learner - 40+ training hours this year';
        }

        return $achievements;
    }
}
