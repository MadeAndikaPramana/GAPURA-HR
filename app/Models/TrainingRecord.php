<?php
// app/Models/TrainingRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TrainingRecord extends Model
{
    use HasFactory;

    /**
     * Simplified status options - only active or expired
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'employee_id',
        'training_type_id',
        'training_provider_id',
        'certificate_number',
        'issuer',
        'issue_date',
        'completion_date',
        'expiry_date',
        'training_date',
        'status', // Only 'active' or 'expired'
        'compliance_status', // Keep for backward compatibility, will sync with status
        'batch_number',
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
        'issue_date' => 'date',
        'completion_date' => 'date',
        'expiry_date' => 'date',
        'training_date' => 'date',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'training_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function trainingProvider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [
                        Carbon::now()->toDateString(),
                        Carbon::now()->addDays($days)->toDateString()
                    ]);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByTrainingType($query, $trainingTypeId)
    {
        return $query->where('training_type_id', $trainingTypeId);
    }

    public function scopeByProvider($query, $providerId)
    {
        return $query->where('training_provider_id', $providerId);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE || !$this->expiry_date) {
            return false;
        }

        return Carbon::parse($this->expiry_date)->diffInDays(Carbon::today(), false) <= 30;
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date || $this->status !== self::STATUS_ACTIVE) {
            return null;
        }

        $days = Carbon::today()->diffInDays(Carbon::parse($this->expiry_date), false);
        return $days >= 0 ? $days : 0;
    }

    public function getDaysPassedExpiryAttribute(): ?int
    {
        if (!$this->expiry_date || $this->status === self::STATUS_ACTIVE) {
            return null;
        }

        return Carbon::parse($this->expiry_date)->diffInDays(Carbon::today(), false);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => $this->is_expiring_soon
                ? 'bg-yellow-100 text-yellow-800'
                : 'bg-green-100 text-green-800',
            self::STATUS_EXPIRED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        if ($this->status === self::STATUS_ACTIVE && $this->is_expiring_soon) {
            return 'Expiring Soon';
        }

        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status)
        };
    }

    public function getComplianceStatusAttribute(): string
    {
        // For backward compatibility, map to old compliance status logic
        if ($this->status === self::STATUS_EXPIRED) {
            return 'expired';
        }

        if ($this->is_expiring_soon) {
            return 'expiring_soon';
        }

        return 'compliant';
    }

    // Methods
    public function updateStatus(): bool
    {
        if (!$this->expiry_date) {
            // No expiry date means permanent certificate
            $this->status = self::STATUS_ACTIVE;
            return $this->save();
        }

        $now = Carbon::now()->startOfDay();
        $expiryDate = Carbon::parse($this->expiry_date)->startOfDay();

        $newStatus = $expiryDate->lt($now) ? self::STATUS_EXPIRED : self::STATUS_ACTIVE;

        if ($this->status !== $newStatus) {
            $oldStatus = $this->status;
            $this->status = $newStatus;

            $saved = $this->save();

            // Also update related certificate if exists
            if ($saved && $this->certificate) {
                $this->certificate->status = $newStatus;
                $this->certificate->save();
            }

            return $saved;
        }

        return true;
    }

    public function markAsExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        $saved = $this->save();

        // Also update related certificate
        if ($saved && $this->certificate) {
            $this->certificate->markAsExpired();
        }

        return $saved;
    }

    public function markAsActive(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        $saved = $this->save();

        // Also update related certificate
        if ($saved && $this->certificate) {
            $this->certificate->markAsActive();
        }

        return $saved;
    }

    // Static methods
    public static function updateExpiredRecords(): int
    {
        $count = 0;

        // Find active records that have passed their expiry date
        $expiredRecords = self::where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', Carbon::today())
            ->get();

        foreach ($expiredRecords as $record) {
            $record->markAsExpired();
            $count++;
        }

        return $count;
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired'
        ];
    }

    public static function getComplianceStats(): array
    {
        $total = self::count();
        $active = self::where('status', self::STATUS_ACTIVE)->count();
        $expired = self::where('status', self::STATUS_EXPIRED)->count();
        $expiringSoon = self::active()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($record) {
            // Auto-update status based on expiry date when saving
            if ($record->expiry_date) {
                $now = Carbon::now()->startOfDay();
                $expiryDate = Carbon::parse($record->expiry_date)->startOfDay();
                $record->status = $expiryDate->lt($now) ? self::STATUS_EXPIRED : self::STATUS_ACTIVE;
            }

            // Always sync compliance_status with status for backward compatibility
            if (Schema::hasColumn('training_records', 'compliance_status')) {
                $record->compliance_status = $record->status;
            }
        });

        static::saved(function ($record) {
            // Sync status with related certificate
            if ($record->certificate) {
                $record->certificate->status = $record->status;
                $record->certificate->save();
            }
        });
    }
}
