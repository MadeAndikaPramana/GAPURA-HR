<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'validity_months',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'validity_months' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all training records for this type
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get active training records for this type
     */
    public function activeTrainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'active');
    }

    /**
     * Get certificate sequences for this type
     */
    public function certificateSequences(): HasMany
    {
        return $this->hasMany(CertificateSequence::class);
    }

    /**
     * Get compliance statistics for this training type
     */
    public function getComplianceStatsAttribute(): array
    {
        $total = $this->trainingRecords()->count();
        $active = $this->activeTrainingRecords()->count();
        $expiring = $this->trainingRecords()->where('status', 'expiring_soon')->count();
        $expired = $this->trainingRecords()->where('status', 'expired')->count();

        return [
            'total' => $total,
            'active' => $active,
            'expiring' => $expiring,
            'expired' => $expired,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Scope for active training types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}

