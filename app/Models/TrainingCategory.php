<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TrainingCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color_code',
        'icon_name',
        'is_mandatory',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer'
    ];

    /**
     * Get all training types in this category
     */
    public function trainingTypes()
    {
        return $this->hasMany(TrainingType::class, 'category_id');
    }

    /**
     * Get active training types in this category
     */
    public function activeTrainingTypes()
    {
        return $this->hasMany(TrainingType::class, 'category_id')->where('is_active', true);
    }

    /**
     * Get training records through training types
     */
    public function trainingRecords()
    {
        return $this->hasManyThrough(TrainingRecord::class, TrainingType::class, 'category_id', 'training_type_id');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for mandatory categories
     */
    public function scopeMandatory(Builder $query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope for ordered categories
     */
    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * Get category statistics
     */
    public function getStatisticsAttribute()
    {
        $trainingRecords = $this->trainingRecords();

        return [
            'total_training_types' => $this->trainingTypes()->count(),
            'active_training_types' => $this->activeTrainingTypes()->count(),
            'total_assignments' => $trainingRecords->count(),
            'completed_assignments' => $trainingRecords->where('status', 'completed')->count(),
            'active_certificates' => $trainingRecords->where('compliance_status', 'compliant')->count(),
            'expiring_certificates' => $trainingRecords->where('compliance_status', 'expiring_soon')->count(),
            'expired_certificates' => $trainingRecords->where('compliance_status', 'expired')->count(),
        ];
    }

    /**
     * Get compliance rate for this category
     */
    public function getComplianceRateAttribute()
    {
        $totalAssignments = $this->trainingRecords()->count();
        if ($totalAssignments === 0) return 0;

        $compliantAssignments = $this->trainingRecords()
            ->where('compliance_status', 'compliant')
            ->count();

        return round(($compliantAssignments / $totalAssignments) * 100, 2);
    }

    /**
     * Get employees with training in this category
     */
    public function employees()
    {
        return Employee::whereHas('trainingRecords.trainingType', function ($query) {
            $query->where('category_id', $this->id);
        })->distinct();
    }

    /**
     * Get departments with training in this category
     */
    public function departments()
    {
        return Department::whereHas('employees.trainingRecords.trainingType', function ($query) {
            $query->where('category_id', $this->id);
        })->distinct();
    }

    /**
     * Check if category has any training assignments
     */
    public function hasTrainingAssignments()
    {
        return $this->trainingRecords()->exists();
    }

    /**
     * Get the next display order for new categories
     */
    public static function getNextDisplayOrder()
    {
        return static::max('display_order') + 1;
    }

    /**
     * Boot method to set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (is_null($category->display_order)) {
                $category->display_order = static::getNextDisplayOrder();
            }
        });
    }

    /**
     * Get training completion trend for this category
     */
    public function getCompletionTrend($months = 12)
    {
        $endDate = now();
        $startDate = $endDate->copy()->subMonths($months);

        return $this->trainingRecords()
            ->selectRaw('
                DATE_FORMAT(completion_date, "%Y-%m") as month,
                COUNT(*) as completed_count,
                AVG(score) as average_score
            ')
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get cost analysis for this category
     */
    public function getCostAnalysis($year = null)
    {
        $year = $year ?: date('Y');

        return $this->trainingRecords()
            ->selectRaw('
                SUM(cost) as total_cost,
                AVG(cost) as average_cost,
                COUNT(*) as total_trainings,
                COUNT(DISTINCT employee_id) as unique_employees
            ')
            ->whereYear('completion_date', $year)
            ->where('status', 'completed')
            ->first();
    }
}
