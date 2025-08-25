<?php
// app/Models/TrainingRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TrainingRecord extends Model
{
    use HasFactory;

    // PERBAIKAN: Tambah field yang missing ke fillable
    protected $fillable = [
        'employee_id',
        'training_type_id',
        'training_provider_id',
        'certificate_number',           // ✅ TAMBAH: Field ini missing
        'issuer',                      // ✅ TAMBAH: Field ini missing
        'issue_date',                  // ✅ TAMBAH: Field ini missing
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

    // PERBAIKAN: Tambah casting untuk field baru
    protected $casts = [
        'issue_date' => 'date',           // ✅ TAMBAH: Casting untuk issue_date
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
     * PERBAIKAN: Boot method to handle automatic calculations
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($trainingRecord) {
            // PERBAIKAN: Update compliance_status berdasarkan expiry_date
            $trainingRecord->updateComplianceStatus();

            // Auto-set created_by dan updated_by
            if (Auth::check()) {
                if ($trainingRecord->isDirty() && !$trainingRecord->wasRecentlyCreated) {
                    $trainingRecord->updated_by_id = Auth::id();
                }
                if ($trainingRecord->wasRecentlyCreated) {
                    $trainingRecord->created_by_id = Auth::id();
                }
            }
        });
    }

    /**
     * PERBAIKAN: Method untuk update compliance status
     */
    public function updateComplianceStatus()
    {
        if (!$this->expiry_date) {
            $this->compliance_status = 'not_required';
            return;
        }

        $daysUntilExpiry = now()->startOfDay()->diffInDays(
            Carbon::parse($this->expiry_date)->startOfDay(),
            false
        );

        if ($daysUntilExpiry < 0) {
            $this->compliance_status = 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            $this->compliance_status = 'expiring_soon';
        } else {
            $this->compliance_status = 'compliant';
        }
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

    /**
     * Get the user who created this record
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by compliance status
     */
    public function scopeByComplianceStatus($query, $complianceStatus)
    {
        return $query->where('compliance_status', $complianceStatus);
    }

    /**
     * Scope for expiring soon records
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>=', now())
                    ->where('compliance_status', '!=', 'expired');
    }

    /**
     * Scope for expired records
     */
    public function scopeExpired($query)
    {
        return $query->where('compliance_status', 'expired');
    }

    /**
     * Scope for active/compliant records
     */
    public function scopeCompliant($query)
    {
        return $query->where('compliance_status', 'compliant');
    }

    /**
     * Scope for search functionality
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('certificate_number', 'like', "%{$term}%")
              ->orWhere('issuer', 'like', "%{$term}%")
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
    public function getComplianceColorAttribute(): string
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

        $reminderMonths = $this->trainingType?->renewal_reminder_months ?? 1;
        return Carbon::parse($this->expiry_date)->subMonths($reminderMonths);
    }

    /**
     * PERBAIKAN: Method untuk generate certificate number
     */
    public static function generateCertificateNumber($trainingTypeId, $issuer = null)
    {
        $trainingType = TrainingType::find($trainingTypeId);
        $prefix = $trainingType ? strtoupper($trainingType->code) : 'TRN';
        $issuerCode = $issuer ? strtoupper(substr(str_replace(' ', '', $issuer), 0, 3)) : 'GAP';

        $year = date('Y');
        $sequence = self::where('certificate_number', 'like', "{$prefix}-{$issuerCode}-%")->count() + 1;

        return sprintf("%s-%s-%s-%04d", $prefix, $issuerCode, $year, $sequence);
    }
}
