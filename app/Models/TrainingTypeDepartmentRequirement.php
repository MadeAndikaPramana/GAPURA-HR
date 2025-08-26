<?php

// app/Models/TrainingTypeDepartmentRequirement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingTypeDepartmentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_type_id',
        'department_id',
        'is_required',
        'frequency_months',
        'target_compliance_rate',
        'department_specific_requirements'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'target_compliance_rate' => 'decimal:2'
    ];

    /**
     * Training type this requirement belongs to
     */
    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Department this requirement applies to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get human-readable frequency
     */
    public function getFormattedFrequencyAttribute(): string
    {
        if (!$this->frequency_months) {
            return 'One-time only';
        }

        if ($this->frequency_months === 12) {
            return 'Annual';
        } elseif ($this->frequency_months === 6) {
            return 'Bi-annual';
        } elseif ($this->frequency_months === 3) {
            return 'Quarterly';
        } else {
            return "Every {$this->frequency_months} months";
        }
    }
}
