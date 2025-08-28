<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TrainingRecord extends Model
{
    use HasFactory;

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
        'status',
        'compliance_status',
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
        'reminder_sent_at' => 'datetime',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'training_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'reminder_count' => 'integer'
    ];

    // =================================================================
    // CORE RELATIONSHIPS
    // =================================================================

    /**
     * Relationship to Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship to Training Type
     */
    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Relationship to Training Provider
     */
    public function trainingProvider()
    {
        return $this->belongsTo(TrainingProvider::class);
    }

    /**
     * Relationship to User who created this record
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relationship to User who last updated this record
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    // =================================================================
    // CERTIFICATE RELATIONSHIPS - NEW ADDITION
    // =================================================================

    /**
     * Get the certificate associated with this training record
     */
    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    /**
     * Get all certificates for this training record (including renewals)
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the latest/active certificate
     */
    public function activeCertificate()
    {
        return $this->hasOne(Certificate::class)
                    ->where('status', 'issued')
                    ->whereNull('deleted_at')
                    ->latest('issue_date');
    }

    // =================================================================
    // EXISTING SCOPES - PRESERVED
    // =================================================================

    /**
     * Scope for active certificates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed training
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for compliant certificates
     */
    public function scopeCompliant($query)
    {
        return $query->where('compliance_status', 'compliant');
    }

    /**
     * Scope for expiring certificates
     */
    public function scopeExpiring($query)
    {
        return $query->where('compliance_status', 'expiring_soon');
    }

    /**
     * Scope for expired certificates
     */
    public function scopeExpired($query)
    {
        return $query->where('compliance_status', 'expired');
    }

    /**
     * Scope for certificates by provider
     */
    public function scopeByProvider($query, $providerId)
    {
        return $query->where('training_provider_id', $providerId);
    }

    /**
     * Scope for certificates by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->whereHas('employee', function($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Scope for certificates expiring within days
     */
    public function scopeExpiringWithinDays($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', Carbon::now()->addDays($days))
                    ->where('expiry_date', '>=', Carbon::now());
    }

    // =================================================================
    // CERTIFICATE-SPECIFIC SCOPES - NEW ADDITION
    // =================================================================

    /**
     * Scope for training records with active certificates
     */
    public function scopeWithActiveCertificate($query)
    {
        return $query->whereHas('certificate', function($q) {
            $q->where('status', 'issued')
              ->where(function($subQ) {
                  $subQ->whereNull('expiry_date')
                       ->orWhere('expiry_date', '>', now());
              });
        });
    }

    /**
     * Scope for training records without certificates
     */
    public function scopeWithoutCertificate($query)
    {
        return $query->doesntHave('certificate');
    }

    /**
     * Scope for training records with expired certificates
     */
    public function scopeWithExpiredCertificate($query)
    {
        return $query->whereHas('certificate', function($q) {
            $q->where('status', 'issued')
              ->where('expiry_date', '<=', now());
        });
    }

    // =================================================================
    // EXISTING BUSINESS LOGIC - PRESERVED
    // =================================================================

    /**
     * Check if certificate is expiring soon
     */
    public function isExpiringSoon($days = 30)
    {
        if (!$this->expiry_date) return false;

        return Carbon::parse($this->expiry_date)->between(
            Carbon::now(),
            Carbon::now()->addDays($days)
        );
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_date) return false;

        return Carbon::parse($this->expiry_date)->isPast();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->compliance_status) {
            'compliant' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute()
    {
        return match($this->compliance_status) {
            'compliant' => 'Active',
            'expiring_soon' => 'Expiring Soon',
            'expired' => 'Expired',
            default => 'Unknown'
        };
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) return null;

        $now = Carbon::now();
        $expiry = Carbon::parse($this->expiry_date);

        if ($expiry->isPast()) {
            return -$expiry->diffInDays($now);
        }

        return $expiry->diffInDays($now);
    }

    /**
     * Auto-update compliance status based on expiry date
     */
    public function updateComplianceStatus()
    {
        if (!$this->expiry_date) {
            $this->compliance_status = 'compliant';
            return;
        }

        $now = Carbon::now();
        $expiry = Carbon::parse($this->expiry_date);

        if ($expiry->isPast()) {
            $this->compliance_status = 'expired';
        } elseif ($expiry->diffInDays($now) <= 30) {
            $this->compliance_status = 'expiring_soon';
        } else {
            $this->compliance_status = 'compliant';
        }

        $this->save();
    }

    // =================================================================
    // CERTIFICATE-SPECIFIC BUSINESS LOGIC - NEW ADDITION
    // =================================================================

    /**
     * Check if this training record has an active certificate
     */
    public function hasActiveCertificate(): bool
    {
        return $this->activeCertificate()->exists();
    }

    /**
     * Get certificate status for this training record
     */
    public function getCertificateStatusAttribute(): string
    {
        $activeCertificate = $this->activeCertificate;

        if (!$activeCertificate) {
            return 'no_certificate';
        }

        if ($activeCertificate->isExpired()) {
            return 'certificate_expired';
        }

        if ($activeCertificate->isExpiringSoon(30)) {
            return 'certificate_expiring';
        }

        return 'certificate_active';
    }

    /**
     * Get the main certificate number (from certificate or training record)
     */
    public function getCertificateNumberAttribute()
    {
        // Check if there's an active certificate first
        if ($this->activeCertificate) {
            return $this->activeCertificate->certificate_number;
        }

        // Fall back to the certificate_number field in training_records
        return $this->attributes['certificate_number'] ?? null;
    }

    /**
     * Create a certificate from this training record
     */
    public function createCertificate(array $additionalData = [])
    {
        $certificateData = array_merge([
            'training_record_id' => $this->id,
            'employee_id' => $this->employee_id,
            'training_type_id' => $this->training_type_id,
            'training_provider_id' => $this->training_provider_id,
            'certificate_type' => 'completion',
            'issue_date' => $this->completion_date ?? $this->issue_date ?? now(),
            'expiry_date' => $this->expiry_date,
            'score' => $this->score,
            'passing_score' => $this->passing_score,
            'status' => 'issued',
            'verification_status' => 'pending',
            'is_renewable' => true,
            'notes' => "Generated from training record #{$this->id}",
        ], $additionalData);

        return Certificate::create($certificateData);
    }

    // =================================================================
    // EXISTING BOOT METHOD - PRESERVED
    // =================================================================

    /**
     * Boot method to auto-update compliance status
     */
    protected static function booted()
    {
        static::saving(function ($trainingRecord) {
            // Auto-calculate compliance status on save
            if ($trainingRecord->isDirty('expiry_date') || !$trainingRecord->compliance_status) {
                if ($trainingRecord->expiry_date) {
                    $now = Carbon::now();
                    $expiry = Carbon::parse($trainingRecord->expiry_date);

                    if ($expiry->isPast()) {
                        $trainingRecord->compliance_status = 'expired';
                    } elseif ($expiry->diffInDays($now) <= 30) {
                        $trainingRecord->compliance_status = 'expiring_soon';
                    } else {
                        $trainingRecord->compliance_status = 'compliant';
                    }
                } else {
                    $trainingRecord->compliance_status = 'compliant';
                }
            }
        });
    }
}
