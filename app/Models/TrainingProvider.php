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
     * Get all training types offered by this provider
     */
    public function trainingTypes()
    {
        return $this->hasMany(TrainingType::class);
    }

    /**
     * Get active training types offered by this provider
     */
    public function activeTrainingTypes()
    {
        return $this->hasMany(TrainingType::class)->where('is_active', true);
    }

    /**
     * Get all training records conducted by this provider
     */
    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
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
            'total_training_types' => $this->trainingTypes()->count(),
            'active_training_types' => $this->activeTrainingTypes()->count(),
            'total_trainings_conducted' => $trainingRecords->count(),
            'completed_trainings' => $trainingRecords->where('status', 'completed')->count(),
            'average_score' => $trainingRecords->where('status', 'completed')->avg('score'),
            'total_revenue_generated' => $trainingRecords->where('status', 'completed')->sum('cost'),
            'unique_employees_trained' => $trainingRecords->distinct('employee_id')->count(),
        ];
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
            ->avg('cost') ?: 1;

        if ($marketAverage == 0) return 100;

        return round((($marketAverage - $averageCost) / $marketAverage) * 100, 2);
    }

    /**
     * Get training completion trend for this provider
     */
    public function getCompletionTrend($months = 12)
    {
        $endDate = now();
        $startDate = $endDate->copy()->subMonths($months);

        return $this->trainingRecords()
            ->selectRaw('
                DATE_FORMAT(completion_date, "%Y-%m") as month,
                COUNT(*) as completed_count,
                AVG(score) as average_score,
                SUM(cost) as total_revenue
            ')
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get upcoming contract expiry warning
     */
    public function getContractExpiryWarning()
    {
        if (is_null($this->contract_end_date)) return null;

        $daysUntilExpiry = now()->diffInDays($this->contract_end_date, false);

        if ($daysUntilExpiry < 0) {
            return [
                'status' => 'expired',
                'message' => 'Contract expired ' . abs($daysUntilExpiry) . ' days ago',
                'urgency' => 'critical'
            ];
        } elseif ($daysUntilExpiry <= 30) {
            return [
                'status' => 'expiring_soon',
                'message' => 'Contract expires in ' . $daysUntilExpiry . ' days',
                'urgency' => 'high'
            ];
        } elseif ($daysUntilExpiry <= 90) {
            return [
                'status' => 'expiring',
                'message' => 'Contract expires in ' . $daysUntilExpiry . ' days',
                'urgency' => 'medium'
            ];
        }

        return null;
    }

    /**
     * Get accreditation expiry warning
     */
    public function getAccreditationExpiryWarning()
    {
        if (is_null($this->accreditation_expiry)) return null;

        $daysUntilExpiry = now()->diffInDays($this->accreditation_expiry, false);

        if ($daysUntilExpiry < 0) {
            return [
                'status' => 'expired',
                'message' => 'Accreditation expired ' . abs($daysUntilExpiry) . ' days ago',
                'urgency' => 'critical'
            ];
        } elseif ($daysUntilExpiry <= 30) {
            return [
                'status' => 'expiring_soon',
                'message' => 'Accreditation expires in ' . $daysUntilExpiry . ' days',
                'urgency' => 'high'
            ];
        } elseif ($daysUntilExpiry <= 90) {
            return [
                'status' => 'expiring',
                'message' => 'Accreditation expires in ' . $daysUntilExpiry . ' days',
                'urgency' => 'medium'
            ];
        }

        return null;
    }

    /**
     * Update provider rating based on recent training feedback
     */
    public function updateRatingFromFeedback()
    {
        $recentTrainings = $this->trainingRecords()
            ->where('status', 'completed')
            ->where('completion_date', '>=', now()->subMonths(6))
            ->whereNotNull('score')
            ->get();

        if ($recentTrainings->isEmpty()) return;

        $averageScore = $recentTrainings->avg('score');

        // Convert score (0-100) to rating (0-5)
        $newRating = ($averageScore / 100) * 5;

        $this->update(['rating' => round($newRating, 2)]);
    }

    /**
     * Search providers by name, contact person, or email
     */
    public function scopeSearch(Builder $query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('contact_person', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }
}
