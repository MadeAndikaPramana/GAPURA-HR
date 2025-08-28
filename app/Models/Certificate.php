<?php
// app/Models/Certificate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Certificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'certificate_number',
        'certificate_type',
        'template_type',
        'training_record_id',
        'employee_id',
        'training_type_id',
        'training_provider_id',
        'issuer_name',
        'issuer_title',
        'issuer_organization',
        'issuer_signature_path',
        'issuer_seal_path',
        'issue_date',
        'effective_date',
        'expiry_date',
        'issued_at',
        'status',
        'verification_status',
        'qr_code_path',
        'verification_code',
        'blockchain_hash',
        'verification_metadata',
        'score',
        'passing_score',
        'achievements',
        'remarks',
        'certificate_file_path',
        'original_file_path',
        'file_size',
        'file_hash',
        'parent_certificate_id',
        'is_renewable',
        'renewal_count',
        'next_renewal_date',
        'renewal_notes',
        'is_compliance_required',
        'compliance_status',
        'last_verified_at',
        'verified_by',
        'custom_fields',
        'notes',
        'print_status',
        'printed_at',
        'print_count',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'issued_at' => 'datetime',
        'next_renewal_date' => 'date',
        'last_verified_at' => 'datetime',
        'printed_at' => 'datetime',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'file_size' => 'integer',
        'renewal_count' => 'integer',
        'print_count' => 'integer',
        'is_renewable' => 'boolean',
        'is_compliance_required' => 'boolean',
        'verification_metadata' => 'json',
        'custom_fields' => 'json'
    ];

    protected $dates = [
        'deleted_at'
    ];

    // =================================================================
    // RELATIONSHIPS
    // =================================================================

    /**
     * Training record this certificate belongs to
     */
    public function trainingRecord(): BelongsTo
    {
        return $this->belongsTo(TrainingRecord::class);
    }

    /**
     * Employee who received this certificate
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Training type for this certificate
     */
    public function trainingType(): BelongsTo
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Training provider who issued this certificate
     */
    public function trainingProvider(): BelongsTo
    {
        return $this->belongsTo(TrainingProvider::class);
    }

    /**
     * Parent certificate (for renewals)
     */
    public function parentCertificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'parent_certificate_id');
    }

    /**
     * Child certificates (renewals)
     */
    public function renewals(): HasMany
    {
        return $this->hasMany(Certificate::class, 'parent_certificate_id');
    }

    /**
     * User who created this certificate
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * User who last updated this certificate
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    // =================================================================
    // SCOPES
    // =================================================================

    /**
     * Scope for issued certificates
     */
    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'issued');
    }

    /**
     * Scope for active certificates (issued and not expired)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'issued')
                    ->where(function($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', now());
                    });
    }

    /**
     * Scope for expired certificates
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'issued')
                    ->where('expiry_date', '<=', now());
    }

    /**
     * Scope for expiring certificates (within warning period)
     */
    public function scopeExpiring(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'issued')
                    ->whereBetween('expiry_date', [
                        now(),
                        now()->addDays($days)
                    ]);
    }

    /**
     * Scope for certificates by employee
     */
    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for certificates by training type
     */
    public function scopeByTrainingType(Builder $query, int $trainingTypeId): Builder
    {
        return $query->where('training_type_id', $trainingTypeId);
    }

    /**
     * Scope for certificates by department
     */
    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->whereHas('employee', function($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Scope for compliance required certificates
     */
    public function scopeComplianceRequired(Builder $query): Builder
    {
        return $query->where('is_compliance_required', true);
    }

    // =================================================================
    // BUSINESS LOGIC METHODS
    // =================================================================

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if certificate is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Check if certificate is renewable
     */
    public function canBeRenewed(): bool
    {
        return $this->is_renewable &&
               $this->status === 'issued' &&
               ($this->isExpired() || $this->isExpiringSoon(90));
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get certificate age in days
     */
    public function getAgeInDaysAttribute(): int
    {
        return $this->issue_date->diffInDays(now());
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber(string $prefix = 'CERT'): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Get next sequence number for this month
        $lastCert = static::whereYear('created_at', $year)
                         ->whereMonth('created_at', $month)
                         ->latest('id')
                         ->first();

        $sequence = $lastCert ? (int) substr($lastCert->certificate_number, -4) + 1 : 1;

        return sprintf('%s-%d%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Generate verification code
     */
    public function generateVerificationCode(): string
    {
        return strtoupper(uniqid() . substr(md5($this->certificate_number . $this->employee_id), 0, 6));
    }

    /**
     * Mark as printed
     */
    public function markAsPrinted(): void
    {
        $this->update([
            'print_status' => $this->print_count > 0 ? 'reprinted' : 'printed',
            'printed_at' => now(),
            'print_count' => $this->print_count + 1
        ]);
    }

    /**
     * Revoke certificate
     */
    public function revoke(string $reason = null): bool
    {
        return $this->update([
            'status' => 'revoked',
            'notes' => $this->notes . "\n[REVOKED] " . ($reason ?: 'No reason provided') . ' at ' . now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create renewal certificate
     */
    public function createRenewal(array $attributes = []): Certificate
    {
        $renewalData = array_merge([
            'certificate_number' => static::generateCertificateNumber(),
            'certificate_type' => $this->certificate_type,
            'employee_id' => $this->employee_id,
            'training_type_id' => $this->training_type_id,
            'training_provider_id' => $this->training_provider_id,
            'parent_certificate_id' => $this->id,
            'issuer_name' => $this->issuer_name,
            'issuer_organization' => $this->issuer_organization,
            'issue_date' => now()->toDateString(),
            'issued_at' => now(),
            'status' => 'draft',
            'renewal_count' => $this->renewal_count + 1,
            'verification_code' => null // Will be generated on issue
        ], $attributes);

        return static::create($renewalData);
    }

    // =================================================================
    // ACCESSORS & MUTATORS
    // =================================================================

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'issued' => $this->isExpired() ? 'red' : ($this->isExpiringSoon() ? 'yellow' : 'green'),
            'revoked' => 'red',
            'expired' => 'red',
            'renewed' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Get verification status badge color
     */
    public function getVerificationBadgeColorAttribute(): string
    {
        return match($this->verification_status) {
            'pending' => 'yellow',
            'verified' => 'green',
            'invalid' => 'red',
            'under_review' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (!$certificate->certificate_number) {
                $certificate->certificate_number = static::generateCertificateNumber();
            }

            if (!$certificate->verification_code && $certificate->status === 'issued') {
                $certificate->verification_code = $certificate->generateVerificationCode();
            }
        });

        static::updating(function ($certificate) {
            if ($certificate->isDirty('status') && $certificate->status === 'issued' && !$certificate->verification_code) {
                $certificate->verification_code = $certificate->generateVerificationCode();
            }
        });
    }
}
