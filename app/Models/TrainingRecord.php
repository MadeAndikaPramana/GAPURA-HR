<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TrainingRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'training_type_id',
        'certificate_number',
        'issuer',
        'issue_date',
        'expiry_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the employee that owns the training record
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the training type that owns the training record
     */
    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }

        return Carbon::parse($this->expiry_date)->diffInDays(Carbon::now(), false);
    }

    /**
     * Check if certificate is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->days_until_expiry <= 0;
    }

    /**
     * Check if certificate is expiring soon
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->days_until_expiry > 0 && $this->days_until_expiry <= 30;
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'expiring_soon' => 'bg-yellow-100 text-yellow-800',
            'expired' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expiring soon
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('status', 'expiring_soon');
    }

    /**
     * Scope for expired records
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for records expiring within N days
     */
    public function scopeExpiringWithin($query, $days)
    {
        $targetDate = Carbon::now()->addDays($days);
        return $query->where('expiry_date', '<=', $targetDate)
                    ->where('expiry_date', '>=', Carbon::now())
                    ->whereIn('status', ['active', 'expiring_soon']);
    }

    /**
     * Scope for specific training type
     */
    public function scopeForTrainingType($query, $trainingTypeId)
    {
        return $query->where('training_type_id', $trainingTypeId);
    }

    /**
     * Scope for specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
