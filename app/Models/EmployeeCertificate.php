<?php
// app/Models/EmployeeCertificate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EmployeeCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'certificate_type_id',
        'certificate_number',
        'issuer',
        'training_provider',
        'issue_date',
        'expiry_date',
        'completion_date',
        'training_date',
        'status',
        'compliance_status',
        'score',
        'passing_score',
        'training_hours',
        'cost',
        'location',
        'instructor_name',
        'certificate_files',
        'notes',
        'reminder_sent_at',
        'reminder_count',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'completion_date' => 'date',
        'training_date' => 'date',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'training_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'certificate_files' => 'array',
        'reminder_sent_at' => 'datetime'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update status when dates change
        static::saving(function ($certificate) {
            $certificate->updateStatusBasedOnDates();
        });
    }

    /**
     * Get the employee who owns this certificate
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the certificate type
     */
    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
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
     * Scope to get active certificates
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired certificates
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope to get expiring soon certificates
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('status', 'expiring_soon');
    }

    /**
     * Scope to get certificates for a specific employee
     */
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get current certificate for employee and type (for recurrent certificates)
     */
    public function scopeCurrentForEmployeeAndType(Builder $query, int $employeeId, int $certificateTypeId): Builder
    {
        return $query->where('employee_id', $employeeId)
                    ->where('certificate_type_id', $certificateTypeId)
                    ->whereIn('status', ['active', 'expiring_soon'])
                    ->orderBy('expiry_date', 'desc')
                    ->limit(1);
    }

    /**
     * Scope to get certificate history for employee and type (all previous certificates)
     */
    public function scopeHistoryForEmployeeAndType(Builder $query, int $employeeId, int $certificateTypeId): Builder
    {
        return $query->where('employee_id', $employeeId)
                    ->where('certificate_type_id', $certificateTypeId)
                    ->orderBy('issue_date', 'desc');
    }

    /**
     * Update status based on expiry date
     */
    public function updateStatusBasedOnDates()
    {
        if (!$this->expiry_date) {
            // No expiry date means lifetime certificate
            $this->status = 'active';
            $this->compliance_status = 'compliant';
            return;
        }

        $now = Carbon::now()->startOfDay();
        $expiryDate = Carbon::parse($this->expiry_date)->startOfDay();
        $warningDate = $expiryDate->copy()->subDays(30); // 30 days warning

        if ($expiryDate->isPast()) {
            $this->status = 'expired';
            $this->compliance_status = 'expired';
        } elseif ($now->gte($warningDate)) {
            $this->status = 'expiring_soon';
            $this->compliance_status = 'expiring_soon';
        } else {
            $this->status = 'active';
            $this->compliance_status = 'compliant';
        }
    }

    /**
     * Check if this certificate is current (not expired)
     */
    public function isCurrent(): bool
    {
        return in_array($this->status, ['active', 'expiring_soon']);
    }

    /**
     * Check if this certificate is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Check if this certificate is expiring soon
     */
    public function isExpiringSoon(): bool
    {
        return $this->status === 'expiring_soon';
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get human readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'active' => 'Active',
            'expiring_soon' => 'Expiring Soon',
            'expired' => 'Expired',
            default => 'Unknown'
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'completed' => 'blue',
            'active' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get all certificate files with URLs
     */
    public function getCertificateFilesWithUrlsAttribute(): array
    {
        if (!$this->certificate_files) {
            return [];
        }

        return collect($this->certificate_files)->map(function ($file) {
            $file['url'] = route('employee-certificates.file.download', [
                'certificate' => $this->id,
                'file' => $file['stored_name']
            ]);
            return $file;
        })->toArray();
    }

    /**
     * Add a file to this certificate
     */
    public function addFile(array $fileData): void
    {
        $files = $this->certificate_files ?? [];
        $files[] = $fileData;
        $this->certificate_files = $files;
        $this->save();
    }

    /**
     * Remove a file from this certificate
     */
    public function removeFile(string $fileName): bool
    {
        $files = $this->certificate_files ?? [];
        $filteredFiles = collect($files)->reject(function ($file) use ($fileName) {
            return $file['stored_name'] === $fileName;
        })->values()->toArray();

        $this->certificate_files = $filteredFiles;
        $this->save();

        return true;
    }

    /**
     * Get related certificates (same employee, same type)
     */
    public function getRelatedCertificatesAttribute()
    {
        return self::where('employee_id', $this->employee_id)
                   ->where('certificate_type_id', $this->certificate_type_id)
                   ->where('id', '!=', $this->id)
                   ->orderBy('issue_date', 'desc')
                   ->get();
    }

    /**
     * Check if this is the most recent certificate for this employee and type
     */
    public function isMostRecent(): bool
    {
        $mostRecent = self::where('employee_id', $this->employee_id)
                         ->where('certificate_type_id', $this->certificate_type_id)
                         ->orderBy('issue_date', 'desc')
                         ->first();

        return $mostRecent && $mostRecent->id === $this->id;
    }

    /**
     * Generate QR code for certificate verification
     */
    public function generateVerificationCode(): string
    {
        return strtoupper(substr(md5($this->certificate_number . $this->employee_id . $this->issue_date), 0, 8));
    }
}
