<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TrainingProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'email',
        'phone',
        'address',
        'website',
        'accreditation_number',
        'accreditation_expiry',
        'contract_start_date',
        'contract_end_date',
        'rating',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'accreditation_expiry' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'rating' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get all training records conducted by this provider
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get unique training types that this provider has conducted
     */
    public function trainingTypes()
    {
        return $this->hasManyThrough(
            TrainingType::class,
            TrainingRecord::class,
            'training_provider_id', // Foreign key on training_records table
            'id',                   // Foreign key on training_types table
            'id',                   // Local key on training_providers table
            'training_type_id'      // Local key on training_records table
        )->distinct();
    }

    /**
     * Get unique departments that this provider has served
     */
    public function departments()
    {
        return $this->hasManyThrough(
            Department::class,
            TrainingRecord::class,
            'training_provider_id', // Foreign key on training_records table
            'id',                   // Foreign key on departments table
            'id',                   // Local key on training_providers table
            'employee_id'          // We need to go through employee
        )->join('employees', 'employees.id', '=', 'training_records.employee_id')
         ->where('employees.department_id', '=', 'departments.id')
         ->distinct();
    }

    /**
     * Get employees trained by this provider
     */
    public function trainedEmployees()
    {
        return $this->hasManyThrough(
            Employee::class,
            TrainingRecord::class,
            'training_provider_id',
            'id',
            'id',
            'employee_id'
        )->distinct();
    }

    /**
     * Scope for active providers
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for providers with valid accreditation
     */
    public function scopeAccredited(Builder $query)
    {
        return $query->whereNotNull('accreditation_number')
                    ->where(function ($q) {
                        $q->whereNull('accreditation_expiry')
                          ->orWhere('accreditation_expiry', '>=', now());
                    });
    }

    /**
     * Scope for providers with active contracts
     */
    public function scopeWithActiveContract(Builder $query)
    {
        return $query->where(function ($q) {
            $q->where(function ($subQ) {
                $subQ->whereNull('contract_start_date')
                     ->whereNull('contract_end_date');
            })->orWhere(function ($subQ) {
                $subQ->where('contract_start_date', '<=', now())
                     ->where('contract_end_date', '>=', now());
            });
        });
    }

    /**
     * Scope for highly rated providers
     */
    public function scopeHighlyRated(Builder $query, $minimumRating = 4.0)
    {
        return $query->where('rating', '>=', $minimumRating);
    }

    /**
     * Scope for providers that serve specific department
     */
    public function scopeServingDepartment(Builder $query, $departmentId)
    {
        return $query->whereHas('trainingRecords.employee', function($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Scope for providers that offer specific training type
     */
    public function scopeOfferingTrainingType(Builder $query, $trainingTypeId)
    {
        return $query->whereHas('trainingRecords', function($q) use ($trainingTypeId) {
            $q->where('training_type_id', $trainingTypeId);
        });
    }

    /**
     * Check if provider's accreditation is valid
     */
    public function hasValidAccreditation()
    {
        if (is_null($this->accreditation_number)) return false;
        if (is_null($this->accreditation_expiry)) return true;

        return $this->accreditation_expiry >= now()->toDateString();
    }

    /**
     * Check if provider has active contract
     */
    public function hasActiveContract()
    {
        if (is_null($this->contract_start_date) && is_null($this->contract_end_date)) {
            return true; // No contract restrictions
        }

        $now = now()->toDateString();

        return $this->contract_start_date <= $now && $this->contract_end_date >= $now;
    }

    /**
     * Get provider statistics
     */
    public function getStatisticsAttribute()
    {
        $trainingRecords = $this->trainingRecords();

        return [
            'total_training_types' => $this->getUniqueTrainingTypesCount(),
            'active_training_types' => $this->getActiveTrainingTypesCount(),
            'total_trainings_conducted' => $trainingRecords->count(),
            'completed_trainings' => $trainingRecords->where('status', 'completed')->count(),
            'average_score' => $trainingRecords->where('status', 'completed')->avg('score'),
            'total_revenue_generated' => $trainingRecords->where('status', 'completed')->sum('cost'),
            'unique_employees_trained' => $trainingRecords->distinct('employee_id')->count(),
            'departments_served' => $this->getUniqueDepartmentsCount(),
        ];
    }

    /**
     * Get unique training types count
     */
    public function getUniqueTrainingTypesCount()
    {
        return $this->trainingRecords()
                    ->distinct('training_type_id')
                    ->count();
    }

    /**
     * Get active training types count
     */
    public function getActiveTrainingTypesCount()
    {
        return $this->trainingRecords()
                    ->whereHas('trainingType', function($q) {
                        $q->where('is_active', true);
                    })
                    ->distinct('training_type_id')
                    ->count();
    }

    /**
     * Get unique departments served count
     */
    public function getUniqueDepartmentsCount()
    {
        return $this->trainingRecords()
                    ->join('employees', 'employees.id', '=', 'training_records.employee_id')
                    ->distinct('employees.department_id')
                    ->count();
    }

    /**
     * Get provider performance metrics
     */
    public function getPerformanceMetricsAttribute()
    {
        $completedTrainings = $this->trainingRecords()->where('status', 'completed');
        $totalTrainings = $this->trainingRecords();

        $completionRate = $totalTrainings->count() > 0
            ? ($completedTrainings->count() / $totalTrainings->count()) * 100
            : 0;

        return [
            'completion_rate' => round($completionRate, 2),
            'average_score' => round($completedTrainings->avg('score') ?: 0, 2),
            'on_time_completion_rate' => $this->getOnTimeCompletionRate(),
            'employee_satisfaction' => $this->rating,
            'cost_effectiveness' => $this->getCostEffectiveness(),
        ];
    }

    /**
     * Calculate on-time completion rate
     */
    private function getOnTimeCompletionRate()
    {
        $completedTrainings = $this->trainingRecords()
            ->where('status', 'completed')
            ->whereNotNull('training_date')
            ->whereNotNull('completion_date')
            ->get();

        if ($completedTrainings->isEmpty()) return 0;

        $onTimeCount = $completedTrainings->filter(function ($record) {
            return $record->completion_date <= $record->training_date;
        })->count();

        return round(($onTimeCount / $completedTrainings->count()) * 100, 2);
    }

    /**
     * Calculate cost effectiveness compared to market average
     */
    private function getCostEffectiveness()
    {
        $averageCost = $this->trainingRecords()
            ->where('status', 'completed')
            ->avg('cost') ?: 0;

        $marketAverage = TrainingRecord::where('status', 'completed')
            ->avg('cost') ?: 0;

        if ($marketAverage == 0) return 100;

        return round((($marketAverage - $averageCost) / $marketAverage) * 100, 2);
    }

    /**
     * Get formatted display name (with code if available)
     */
    public function getDisplayNameAttribute()
    {
        return $this->code ? "{$this->code} - {$this->name}" : $this->name;
    }

    /**
     * Get status badge info
     */
    public function getStatusBadgeAttribute()
    {
        if (!$this->is_active) {
            return ['color' => 'red', 'text' => 'Inactive'];
        }

        if (!$this->hasValidAccreditation()) {
            return ['color' => 'yellow', 'text' => 'Accreditation Expired'];
        }

        if (!$this->hasActiveContract()) {
            return ['color' => 'yellow', 'text' => 'Contract Expired'];
        }

        return ['color' => 'green', 'text' => 'Active'];
    }
}
