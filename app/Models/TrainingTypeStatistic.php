<?php
// app/Models/TrainingTypeStatistic.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingTypeStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_type_id',
        'total_certificates',
        'active_certificates',
        'expiring_certificates',
        'expired_certificates',
        'compliance_rate',
        'employees_trained',
        'employees_need_training',
        'risk_level',
        'calculated_priority_score',
        'total_cost_ytd',
        'average_cost_per_certificate',
        'certificates_issued_this_month',
        'certificates_issued_this_quarter',
        'certificates_issued_this_year',
        'next_batch_expiry_date',
        'certificates_expiring_next_30_days',
        'certificates_expiring_next_90_days',
        'calculated_at',
        'last_certificate_issued_at'
    ];

    protected $casts = [
        'compliance_rate' => 'decimal:2',
        'total_cost_ytd' => 'decimal:2',
        'average_cost_per_certificate' => 'decimal:2',
        'next_batch_expiry_date' => 'date',
        'calculated_at' => 'datetime',
        'last_certificate_issued_at' => 'datetime'
    ];

    /**
     * Training type this statistic belongs to
     */
    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Check if statistics are fresh (updated within last hour)
     */
    public function isFresh(): bool
    {
        return $this->calculated_at &&
               $this->calculated_at->diffInHours(now()) < 1;
    }

    /**
     * Get risk color for UI
     */
    public function getRiskColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get compliance status text
     */
    public function getComplianceStatusAttribute(): string
    {
        if ($this->compliance_rate >= 95) return 'excellent';
        if ($this->compliance_rate >= 85) return 'good';
        if ($this->compliance_rate >= 70) return 'fair';
        if ($this->compliance_rate >= 50) return 'poor';
        return 'critical';
    }
}
