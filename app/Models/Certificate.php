<?php
// app/Models/Certificate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_record_id',
        'training_type_id',
        'employee_id',
        'training_provider_id',
        'certificate_number',
        'certificate_series',
        'verification_code',
        'digital_signature',
        'issued_by',
        'issuer_name',
        'issuer_title',
        'issuer_license',
        'issue_date',
        'valid_from',
        'expiry_date',
        'original_expiry_date',
        'validity_period_days',
        'status',
        'lifecycle_stage',
        'certificate_description',
        'competencies_achieved',
        'assessment_results',
        'final_score',
        'passing_score',
        'grade',
        'certificate_file_path',
        'certificate_template',
        'qr_code_path',
        'additional_documents',
        'file_size_kb',
        'file_hash',
        'is_verified',
        'verification_date',
        'verified_by_id',
        'verification_notes',
        'is_renewable',
        'renewal_due_date',
        'renewal_reminder_sent',
        'renewal_reminder_count',
        'renewed_from_id',
        'renewed_to_id',
        'renewal_generation',
        'last_compliance_check',
        'compliance_status',
        'compliance_notes',
        'compliance_checklist',
        'revocation_date',
        'revocation_reason',
        'revoked_by_id',
        'revocation_notes',
        'suspension_start',
        'suspension_end',
        'suspension_reason',
        'download_count',
        'last_downloaded',
        'usage_statistics',
        'cost_per_certificate',
        'metadata',
        'internal_notes',
        'language',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_from' => 'date',
        'expiry_date' => 'date',
        'original_expiry_date' => 'date',
        'verification_date' => 'datetime',
        'renewal_due_date' => 'date',
        'renewal_reminder_sent' => 'date',
        'last_compliance_check' => 'date',
        'revocation_date' => 'date',
        'suspension_start' => 'date',
        'suspension_end' => 'date',
        'last_downloaded' => 'datetime',
        'competencies_achieved' => 'array',
        'assessment_results' => 'array',
        'additional_documents' => 'array',
        'usage_statistics' => 'array',
        'metadata' => 'array',
        'compliance_checklist' => 'array',
        'final_score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'cost_per_certificate' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_renewable' => 'boolean',
        'download_count' => 'integer',
        'renewal_reminder_count' => 'integer',
        'renewal_generation' => 'integer',
        'validity_period_days' => 'integer',
        'file_size_kb' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function trainingRecord()
    {
        return $this->belongsTo(TrainingRecord::class);
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function trainingProvider()
    {
        return $this->belongsTo(TrainingProvider::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function renewedFrom()
    {
        return $this->belongsTo(Certificate::class, 'renewed_from_id');
    }

    public function renewedTo()
    {
        return $this->hasOne(Certificate::class, 'renewed_from_id');
    }

    public function renewalHistory()
    {
        return $this->hasMany(Certificate::class, 'renewed_from_id');
    }

    // ==========================================
    // SCOPES & QUERY BUILDERS
    // ==========================================

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon(Builder $query, $days = 30)
    {
        return $query->where('status', 'expiring_soon')
                    ->orWhere(function($q) use ($days) {
                        $q->where('status', 'active')
                          ->whereNotNull('expiry_date')
                          ->where('expiry_date', '<=', now()->addDays($days));
                    });
    }

    public function scopeVerified(Builder $query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByProvider(Builder $query, $providerId)
    {
        return $query->where('training_provider_id', $providerId);
    }

    public function scopeByTrainingType(Builder $query, $typeId)
    {
        return $query->where('training_type_id', $typeId);
    }

    public function scopeCompliant(Builder $query)
    {
        return $query->where('compliance_status', 'compliant');
    }

    public function scopeRenewable(Builder $query)
    {
        return $query->where('is_renewable', true);
    }

    public function scopeDueForRenewal(Builder $query, $days = 30)
    {
        return $query->where('is_renewable', true)
                    ->where('renewal_due_date', '<=', now()->addDays($days));
    }

    public function scopeSearch(Builder $query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('certificate_number', 'like', "%{$term}%")
              ->orWhere('verification_code', 'like', "%{$term}%")
              ->orWhere('issued_by', 'like', "%{$term}%")
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

    // ==========================================
    // CERTIFICATE LIFECYCLE METHODS
    // ==========================================

    public function updateStatus()
    {
        $now = now();
        $oldStatus = $this->status;

        if ($this->revocation_date && $this->revocation_date <= $now) {
            $this->status = 'revoked';
        } elseif ($this->suspension_start && $this->suspension_end &&
                  $now->between($this->suspension_start, $this->suspension_end)) {
            $this->status = 'suspended';
        } elseif ($this->expiry_date && $this->expiry_date < $now) {
            $this->status = 'expired';
        } elseif ($this->expiry_date && $this->expiry_date <= $now->addDays(30)) {
            $this->status = 'expiring_soon';
        } elseif ($this->valid_from && $this->valid_from > $now) {
            $this->status = 'draft';
        } else {
            $this->status = 'active';
        }

        if ($oldStatus !== $this->status) {
            $this->save();
        }

        return $this->status;
    }

    public function revoke($reason, $revokedBy = null)
    {
        $this->update([
            'status' => 'revoked',
            'revocation_date' => now(),
            'revocation_reason' => $reason,
            'revoked_by_id' => $revokedBy ?? auth()->id()
        ]);

        // Log the revocation
        activity('certificate')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Certificate revoked');

        return $this;
    }

    public function suspend($startDate, $endDate, $reason)
    {
        $this->update([
            'status' => 'suspended',
            'suspension_start' => $startDate,
            'suspension_end' => $endDate,
            'suspension_reason' => $reason
        ]);

        return $this;
    }

    public function reactivate()
    {
        $this->update([
            'suspension_start' => null,
            'suspension_end' => null,
            'suspension_reason' => null
        ]);

        $this->updateStatus();
        return $this;
    }

    public function renew(array $renewalData = [])
    {
        $newCertificate = $this->replicate([
            'certificate_number',
            'verification_code',
            'issue_date',
            'expiry_date',
            'renewal_generation'
        ]);

        $newCertificate->fill(array_merge([
            'certificate_number' => static::generateCertificateNumber($this->trainingType->code),
            'verification_code' => static::generateVerificationCode(),
            'issue_date' => now(),
            'expiry_date' => $renewalData['expiry_date'] ?? $this->calculateNewExpiryDate(),
            'renewal_generation' => $this->renewal_generation + 1,
            'renewed_from_id' => $this->id,
            'status' => 'active'
        ], $renewalData));

        $newCertificate->save();

        // Update original certificate
        $this->update([
            'status' => 'renewed',
            'renewed_to_id' => $newCertificate->id
        ]);

        return $newCertificate;
    }

    // ==========================================
    // VERIFICATION & SECURITY
    // ==========================================

    public function verify(User $verifier, $notes = null)
    {
        $this->update([
            'is_verified' => true,
            'verification_date' => now(),
            'verified_by_id' => $verifier->id,
            'verification_notes' => $notes
        ]);

        return $this;
    }

    public function generateDigitalSignature()
    {
        $data = $this->certificate_number .
                $this->employee->name .
                $this->issue_date .
                $this->trainingType->code;

        $this->digital_signature = hash('sha256', $data . config('app.key'));
        $this->save();

        return $this->digital_signature;
    }

    public function verifyIntegrity()
    {
        if (!$this->certificate_file_path || !Storage::exists($this->certificate_file_path)) {
            return false;
        }

        $currentHash = hash_file('md5', Storage::path($this->certificate_file_path));
        return $currentHash === $this->file_hash;
    }

    // ==========================================
    // CERTIFICATE GENERATION & FILES
    // ==========================================

    public static function generateCertificateNumber($trainingTypeCode = null)
    {
        $prefix = $trainingTypeCode ? strtoupper($trainingTypeCode) : 'GAP';
        $year = date('Y');
        $month = date('m');

        $lastCertificate = static::whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->where('certificate_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderByDesc('certificate_number')
            ->first();

        $sequence = 1;
        if ($lastCertificate) {
            $parts = explode('-', $lastCertificate->certificate_number);
            if (count($parts) >= 3) {
                $sequence = intval(end($parts)) + 1;
            }
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    public static function generateVerificationCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('verification_code', $code)->exists());

        return $code;
    }

    public function getVerificationUrl()
    {
        return route('certificates.verify', $this->verification_code);
    }

    public function getDownloadUrl()
    {
        if (!$this->certificate_file_path) {
            return null;
        }

        return route('certificates.download', $this->id);
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
        $this->update(['last_downloaded' => now()]);
    }

    // ==========================================
    // ANALYTICS & REPORTING
    // ==========================================

    public static function getAnalytics($startDate = null, $endDate = null)
    {
        $query = static::query();

        if ($startDate) {
            $query->where('issue_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('issue_date', '<=', $endDate);
        }

        $total = $query->count();

        return [
            'total' => $total,
            'active' => $query->clone()->where('status', 'active')->count(),
            'expired' => $query->clone()->where('status', 'expired')->count(),
            'expiring_soon' => $query->clone()->where('status', 'expiring_soon')->count(),
            'revoked' => $query->clone()->where('status', 'revoked')->count(),
            'suspended' => $query->clone()->where('status', 'suspended')->count(),
            'verified' => $query->clone()->where('is_verified', true)->count(),
            'due_for_renewal' => $query->clone()->dueForRenewal()->count(),
            'by_status' => $query->clone()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'by_compliance' => $query->clone()->groupBy('compliance_status')->selectRaw('compliance_status, count(*) as count')->pluck('count', 'compliance_status'),
            'verification_rate' => $total > 0 ? round(($query->clone()->where('is_verified', true)->count() / $total) * 100, 2) : 0,
        ];
    }

    public function calculateNewExpiryDate()
    {
        if ($this->trainingType && $this->trainingType->validity_period_months) {
            return now()->addMonths($this->trainingType->validity_period_months);
        }

        if ($this->validity_period_days) {
            return now()->addDays($this->validity_period_days);
        }

        return now()->addYear();
    }

    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date &&
               $this->expiry_date > now() &&
               $this->expiry_date <= now()->addDays($days);
    }

    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    public function getLifecycleProgress()
    {
        if (!$this->issue_date || !$this->expiry_date) {
            return 0;
        }

        $totalDays = $this->issue_date->diffInDays($this->expiry_date);
        $daysPassed = $this->issue_date->diffInDays(now());

        return $totalDays > 0 ? min(round(($daysPassed / $totalDays) * 100, 2), 100) : 100;
    }
}
