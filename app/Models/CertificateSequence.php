<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateSequence extends Model
{
    protected $fillable = [
        'training_type_id',
        'issuer',
        'year',
        'month',
        'last_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'last_number' => 'integer',
    ];

    /**
     * Get the training type that owns the certificate sequence
     */
    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Get the next certificate number for this sequence
     */
    public function getNextNumber(): int
    {
        $this->increment('last_number');
        return $this->last_number;
    }

    /**
     * Reset sequence for new period
     */
    public function resetForNewPeriod(): void
    {
        $this->update(['last_number' => 0]);
    }

    /**
     * Scope for specific training type and issuer
     */
    public function scopeForTrainingAndIssuer($query, $trainingTypeId, $issuer)
    {
        return $query->where('training_type_id', $trainingTypeId)
                    ->where('issuer', $issuer);
    }

    /**
     * Scope for current period
     */
    public function scopeCurrentPeriod($query)
    {
        $now = now();
        return $query->where('year', $now->year)
                    ->where('month', $now->month);
    }
}
