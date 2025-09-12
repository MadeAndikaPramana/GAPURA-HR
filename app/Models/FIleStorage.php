<?php
// app/Models/FileStorage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FileStorage extends Model
{
    use HasFactory;

    protected $table = 'file_storage';

    protected $fillable = [
        'employee_id',
        'certificate_type_id',
        'version_number',
        'issue_date',
        'expiry_date',
        'drive_file_id',
        'drive_folder_id',
        'drive_web_view_link',
        'drive_download_link',
        'original_filename',
        'stored_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'file_hash',
        'uploaded_at',
        'uploaded_by',
        'status',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'uploaded_at' => 'datetime',
        'metadata' => 'array',
        'file_size' => 'integer',
        'version_number' => 'integer'
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Employee that owns this certificate file
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Certificate type for this file
     */
    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    /**
     * User who uploaded this file
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ===== SCOPES =====

    /**
     * Get only successfully stored files
     */
    public function scopeStored($query)
    {
        return $query->where('status', 'stored');
    }

    /**
     * Get files for specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Get files for specific certificate type
     */
    public function scopeForCertificateType($query, $certificateTypeId)
    {
        return $query->where('certificate_type_id', $certificateTypeId);
    }

    /**
     * Get current/latest version for each employee-certificate combination
     */
    public function scopeLatestVersions($query)
    {
        return $query->whereIn('id', function($subquery) {
            $subquery->selectRaw('MAX(id)')
                ->from('file_storage')
                ->where('status', 'stored')
                ->groupBy(['employee_id', 'certificate_type_id']);
        });
    }

    /**
     * Get files expiring within specified days
     */
    public function scopeExpiringWithin($query, $days = 30)
    {
        $futureDate = Carbon::now()->addDays($days);
        return $query->whereBetween('expiry_date', [Carbon::now(), $futureDate]);
    }

    /**
     * Get expired files
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::now());
    }

    /**
     * Get files that are currently valid
     */
    public function scopeValid($query)
    {
        $now = Carbon::now();
        return $query->where('issue_date', '<=', $now)
                    ->where('expiry_date', '>=', $now);
    }

    // ===== ACCESSORS & MUTATORS =====

    /**
     * Get human readable file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get certificate validity status
     */
    public function getValidityStatusAttribute(): string
    {
        $now = Carbon::now();

        if ($this->expiry_date < $now) {
            return 'expired';
        }

        if ($this->issue_date > $now) {
            return 'future';
        }

        // Check if expiring soon (30 days)
        if ($this->expiry_date <= $now->copy()->addDays(30)) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if this is the latest version
     */
    public function getIsLatestVersionAttribute(): bool
    {
        $latestVersion = static::where('employee_id', $this->employee_id)
            ->where('certificate_type_id', $this->certificate_type_id)
            ->where('status', 'stored')
            ->max('version_number');

        return $this->version_number === $latestVersion;
    }

    /**
     * Get Google Drive shareable link
     */
    public function getDriveShareLinkAttribute(): ?string
    {
        if (!$this->drive_file_id) {
            return null;
        }

        return "https://drive.google.com/file/d/{$this->drive_file_id}/view";
    }

    // ===== HELPER METHODS =====

    /**
     * Get next version number for this employee-certificate combination
     */
    public static function getNextVersionNumber($employeeId, $certificateTypeId): int
    {
        $maxVersion = static::where('employee_id', $employeeId)
            ->where('certificate_type_id', $certificateTypeId)
            ->max('version_number');

        return ($maxVersion ?? 0) + 1;
    }

    /**
     * Generate storage path for Google Drive
     */
    public static function generateStoragePath($certificateType, $employee, $version): string
    {
        // Format: /Certificates/{cert_type}/{employee_id}/{version_filename}
        $certTypeSlug = str_replace(' ', '-', strtolower($certificateType->name));
        $employeeFolder = "employee-{$employee->id}";

        return "Certificates/{$certTypeSlug}/{$employeeFolder}";
    }

    /**
     * Generate stored filename with version
     */
    public static function generateStoredFilename($originalFilename, $issueDate, $expiryDate, $version): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $issueDateStr = Carbon::parse($issueDate)->format('Y-m-d');
        $expiryDateStr = Carbon::parse($expiryDate)->format('Y-m-d');

        return "v{$version}_{$issueDateStr}_{$expiryDateStr}.{$extension}";
    }

    /**
     * Mark file as successfully stored
     */
    public function markAsStored($driveData): void
    {
        $this->update([
            'status' => 'stored',
            'drive_file_id' => $driveData['id'] ?? null,
            'drive_web_view_link' => $driveData['webViewLink'] ?? null,
            'drive_download_link' => $driveData['webContentLink'] ?? null,
            'uploaded_at' => now()
        ]);
    }

    /**
     * Mark file upload as failed
     */
    public function markAsFailed(string $error = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($error) {
            $metadata['error'] = $error;
            $metadata['failed_at'] = now()->toISOString();
        }

        $this->update([
            'status' => 'failed',
            'metadata' => $metadata
        ]);
    }

    // ===== QUERY HELPERS =====

    /**
     * Get recurrent certificate history for an employee
     */
    public static function getCertificateHistory($employeeId, $certificateTypeId)
    {
        return static::where('employee_id', $employeeId)
            ->where('certificate_type_id', $certificateTypeId)
            ->where('status', 'stored')
            ->orderBy('version_number', 'desc')
            ->get();
    }

    /**
     * Get certificate statistics for an employee
     */
    public static function getEmployeeStats($employeeId): array
    {
        $files = static::where('employee_id', $employeeId)
            ->where('status', 'stored')
            ->get();

        return [
            'total_certificates' => $files->count(),
            'valid_certificates' => $files->where('validity_status', 'valid')->count(),
            'expiring_soon' => $files->where('validity_status', 'expiring_soon')->count(),
            'expired_certificates' => $files->where('validity_status', 'expired')->count(),
            'certificate_types' => $files->unique('certificate_type_id')->count(),
            'total_file_size' => $files->sum('file_size')
        ];
    }

    /**
     * Get certificate statistics for a certificate type
     */
    public static function getCertificateTypeStats($certificateTypeId): array
    {
        $files = static::where('certificate_type_id', $certificateTypeId)
            ->where('status', 'stored')
            ->get();

        $latestFiles = $files->groupBy('employee_id')
            ->map(fn($group) => $group->sortByDesc('version_number')->first());

        return [
            'total_files' => $files->count(),
            'unique_employees' => $latestFiles->count(),
            'valid_certificates' => $latestFiles->where('validity_status', 'valid')->count(),
            'expiring_soon' => $latestFiles->where('validity_status', 'expiring_soon')->count(),
            'expired_certificates' => $latestFiles->where('validity_status', 'expired')->count(),
            'total_versions' => $files->sum('version_number')
        ];
    }
}
