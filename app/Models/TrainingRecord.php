<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TrainingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'training_type_id',
        'training_provider_id',
        'batch_number',
        'training_date',
        'completion_date',
        'expiry_date',
        'status',
        'compliance_status',
        'score',
        'passing_score',
        'training_hours',
        'cost',
        'location',
        'instructor_name',
        'notes',
        'reminder_sent_at',
        'reminder_count',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'training_date' => 'date',
        'completion_date' => 'date',
        'expiry_date' => 'date',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'training_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer'
    ];

    protected $appends = [
        'is_passed',
        'days_until_expiry',
        'compliance_color',
        'next_reminder_date'
    ];

    /**
     * Boot method to handle automatic calculations
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($trainingRecord) {
            $trainingRecord->updateComplianceStatus();
            $trainingRecord->calculateExpiryDate();
        });
    }

    /**
     * Get the employee this training record belongs to
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the training type
     */
    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Get the training provider
     */
    public function trainingProvider()
    {
        return $this->belongsTo(TrainingProvider::class);
    }

    // /**
    //  * Get all certificates for this training record
    //  */
    // public function certificates()
    // {
    //     return $this->hasMany(Certificate::class);
    // }

    // /**
    //  * Get the latest certificate
    //  */
    // public function latestCertificate()
    // {
    //     return $this->hasOne(Certificate::class)->latest();
    // }

    // /**
    //  * Get active certificates (not expired)
    //  */
    // public function activeCertificates()
    // {
    //     return $this->hasMany(Certificate::class)->active();
    // }

    /**
     * Get the user who created this record
     */
    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedBy()
    {
        return $this->belongsTo(Employee::class, 'updated_by_id');
    }

    /**
     * Scope for completed training records
     */
    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for active/valid training records
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('compliance_status', 'compliant');
    }

    /**
     * Scope for expired training records
     */
    public function scopeExpired(Builder $query)
    {
        return $query->where('compliance_status', 'expired');
    }

    /**
     * Scope for training records expiring soon
     */
    public function scopeExpiringSoon(Builder $query, $days = 30)
    {
        return $query->where('compliance_status', 'expiring_soon')
                    ->orWhere(function ($q) use ($days) {
                        $q->whereNotNull('expiry_date')
                          ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
                    });
    }

    /**
     * Scope for mandatory training records
     */
    public function scopeMandatory(Builder $query)
    {
        return $query->whereHas('trainingType', function ($q) {
            $q->where('is_mandatory', true);
        });
    }

    /**
     * Scope for training records by department
     */
    public function scopeByDepartment(Builder $query, $departmentId)
    {
        return $query->whereHas('employee', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Scope for training records by training category
     */
    public function scopeByCategory(Builder $query, $categoryId)
    {
        return $query->whereHas('trainingType', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope for search functionality
     */
    public function scopeSearch(Builder $query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('batch_number', 'like', "%{$term}%")
              ->orWhere('location', 'like', "%{$term}%")
              ->orWhere('instructor_name', 'like', "%{$term}%")
              ->orWhereHas('employee', function ($empQuery) use ($term) {
                  $empQuery->where('name', 'like', "%{$term}%")
                           ->orWhere('employee_id', 'like', "%{$term}%");
              })
              ->orWhereHas('trainingType', function ($typeQuery) use ($term) {
                  $typeQuery->where('name', 'like', "%{$term}%")
                           ->orWhere('code', 'like', "%{$term}%");
              });
        });
    }

    /**
     * Check if training was passed
     */
    public function getIsPassedAttribute()
    {
        if (is_null($this->score) || is_null($this->passing_score)) {
            return null; // No score available
        }

        return $this->score >= $this->passing_score;
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (is_null($this->expiry_date)) {
            return null;
        }

        return now()->startOfDay()->diffInDays(Carbon::parse($this->expiry_date)->startOfDay(), false);
    }

    /**
     * Get compliance status color for UI
     */
    public function getComplianceColorAttribute()
    {
        return match($this->compliance_status) {
            'compliant' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            'not_required' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get next reminder date
     */
    public function getNextReminderDateAttribute()
    {
        if (is_null($this->expiry_date)) {
            return null;
        }

        $reminderMonths = $this->trainingType?->renewal_reminder_months ?? 3;
        return Carbon::parse($this->expiry_date)->subMonths($reminderMonths);
    }

    /**
     * Update compliance status based on expiry date
     */
    public function updateComplianceStatus()
    {
        if (is_null($this->expiry_date)) {
            $this->compliance_status = 'compliant';
            return;
        }

        $now = now()->startOfDay();
        $expiry = Carbon::parse($this->expiry_date)->startOfDay();
        $daysUntilExpiry = $now->diffInDays($expiry, false);

        if ($daysUntilExpiry < 0) {
            $this->compliance_status = 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            $this->compliance_status = 'expiring_soon';
        } else {
            $this->compliance_status = 'compliant';
        }
    }

    /**
     * Calculate expiry date based on training type validity
     */
    public function calculateExpiryDate()
    {
        if ($this->expiry_date || !$this->completion_date || !$this->trainingType) {
            return; // Don't override if already set
        }

        if ($this->trainingType->validity_months) {
            $this->expiry_date = Carbon::parse($this->completion_date)
                ->addMonths($this->trainingType->validity_months);
        }
    }

    /**
     * Mark training as completed
     */
    public function markAsCompleted($completionDate = null, $score = null)
    {
        $this->update([
            'status' => 'completed',
            'completion_date' => $completionDate ?: now(),
            'score' => $score
        ]);

        // Auto-create certificate if training type requires it
        // if ($this->trainingType->requires_certification && !$this->certificates()->exists()) {
        //     app(\App\Services\CertificateService::class)->createCertificateFromTrainingRecord($this);
        // }

        return $this;
    }

    /**
     * Check if training needs renewal
     */
    public function needsRenewal($days = 90)
    {
        if (is_null($this->expiry_date)) {
            return false;
        }

        return $this->days_until_expiry <= $days && $this->days_until_expiry > 0;
    }

    /**
     * Check if training is overdue
     */
    public function isOverdue()
    {
        return $this->compliance_status === 'expired';
    }

    /**
     * Get renewal recommendation
     */
    public function getRenewalRecommendation()
    {
        if (is_null($this->expiry_date)) {
            return null;
        }

        $daysUntilExpiry = $this->days_until_expiry;

        if ($daysUntilExpiry < 0) {
            return [
                'urgency' => 'critical',
                'message' => 'Training expired ' . abs($daysUntilExpiry) . ' days ago. Immediate renewal required.',
                'action' => 'Schedule immediately'
            ];
        } elseif ($daysUntilExpiry <= 7) {
            return [
                'urgency' => 'urgent',
                'message' => 'Training expires in ' . $daysUntilExpiry . ' days.',
                'action' => 'Schedule this week'
            ];
        } elseif ($daysUntilExpiry <= 30) {
            return [
                'urgency' => 'high',
                'message' => 'Training expires in ' . $daysUntilExpiry . ' days.',
                'action' => 'Schedule within 2 weeks'
            ];
        } elseif ($daysUntilExpiry <= 90) {
            return [
                'urgency' => 'medium',
                'message' => 'Training expires in ' . $daysUntilExpiry . ' days.',
                'action' => 'Plan renewal'
            ];
        }

        return null;
    }

    /**
     * Get training cost per hour
     */
    public function getCostPerHourAttribute()
    {
        if ($this->training_hours <= 0) {
            return 0;
        }

        return round($this->cost / $this->training_hours, 2);
    }

    /**
     * Get training efficiency score (score per cost)
     */
    public function getEfficiencyScoreAttribute()
    {
        if ($this->cost <= 0 || is_null($this->score)) {
            return 0;
        }

        return round($this->score / ($this->cost / 1000), 2); // Score per 1000 cost units
    }

    /**
     * Create renewal training record
     */
    public function createRenewal($scheduledDate = null)
    {
        $renewalData = [
            'employee_id' => $this->employee_id,
            'training_type_id' => $this->training_type_id,
            'training_provider_id' => $this->training_provider_id,
            'training_date' => $scheduledDate,
            'status' => 'registered',
            'cost' => $this->trainingType->cost_per_person,
            'training_hours' => $this->trainingType->duration_hours,
            'passing_score' => $this->passing_score,
            'notes' => 'Renewal of training record ID: ' . $this->id,
            'created_by_id' => Auth::id()
        ];

        return static::create($renewalData);
    }

    /**
     * Get training record statistics
     */
    public static function getTrainingStatistics(array $filters = [])
    {
        $query = static::query();

        // Apply filters
        if (isset($filters['department_id'])) {
            $query->byDepartment($filters['department_id']);
        }

        if (isset($filters['training_type_id'])) {
            $query->where('training_type_id', $filters['training_type_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('completion_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('completion_date', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $completed = $query->completed()->count();
        $compliant = $query->active()->count();
        $expired = $query->expired()->count();
        $expiringSoon = $query->expiringSoon(30)->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'compliant' => $compliant,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'compliance_rate' => $total > 0 ? round(($compliant / $total) * 100, 2) : 0,
            'average_score' => $query->completed()->avg('score'),
            'total_cost' => $query->completed()->sum('cost'),
            'total_hours' => $query->completed()->sum('training_hours')
        ];
    }

    /**
     * Get completion trend data
     */
    public static function getCompletionTrend($months = 12, array $filters = [])
    {
        $query = static::completed();

        // Apply filters
        if (isset($filters['department_id'])) {
            $query->byDepartment($filters['department_id']);
        }

        if (isset($filters['training_type_id'])) {
            $query->where('training_type_id', $filters['training_type_id']);
        }

        $endDate = now();
        $startDate = $endDate->copy()->subMonths($months);

        return $query->selectRaw('
                DATE_FORMAT(completion_date, "%Y-%m") as month,
                COUNT(*) as completed_count,
                AVG(score) as average_score,
                SUM(cost) as total_cost,
                SUM(training_hours) as total_hours
            ')
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get training records requiring action
     */
    public static function getActionRequired()
    {
        return [
            'expired' => static::expired()->with(['employee', 'trainingType'])->get(),
            'expiring_soon' => static::expiringSoon(30)->with(['employee', 'trainingType'])->get(),
            'pending_completion' => static::where('status', 'in_progress')
                ->where('training_date', '<', now())
                ->with(['employee', 'trainingType'])
                ->get(),
            'missing_certificates' => static::completed()
                ->whereHas('trainingType', function ($q) {
                    $q->where('requires_certification', true);
                })
                ->whereDoesntHave('certificates')
                ->with(['employee', 'trainingType'])
                ->get()
        ];
    }
}
