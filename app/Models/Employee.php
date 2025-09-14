<?php
// app/Models/Employee.php - Complete Container System Model

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Services\EmployeeContainerService;

class Employee extends Model
{
    protected $fillable = [
        'employee_id', 'name', 'email', 'phone',
        'department_id', 'position', 'hire_date', 'status',
        'background_check_date', 'background_check_status',
        'background_check_notes', 'background_check_files',
        'notes', 'profile_photo_path',
        'container_created_at', 'container_status', 'container_file_count', 
        'container_last_updated'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'background_check_files' => 'array',
        'container_created_at' => 'datetime',
        'container_last_updated' => 'datetime'
    ];

    /**
     * Boot the model to automatically create containers
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($employee) {
            // Automatically create container when employee is created
            try {
                $containerService = app(EmployeeContainerService::class);
                $containerService->initializeContainer($employee);
            } catch (\Exception $e) {
                \Log::error("Failed to auto-create container for employee {$employee->employee_id}: " . $e->getMessage());
            }
        });
    }

    // ===== RELATIONSHIPS =====

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->orderBy('issue_date', 'desc');
    }

    // ===== CONTAINER METHODS =====

    /**
     * Get complete employee container data
     */
    public function getContainerData()
    {
        return [
            'profile' => $this->getProfileData(),
            'background_check' => $this->getBackgroundCheckData(),
            'certificates' => $this->getCertificatesByType(),
            'statistics' => $this->getCertificateStatistics()
        ];
    }

    /**
     * Get profile information for container
     */
    public function getProfileData()
    {
        return [
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'position' => $this->position,
            'department' => $this->department?->name,
            'hire_date' => $this->hire_date?->format('d M Y'),
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status
        ];
    }

    /**
     * Get background check data with files
     */
    public function getBackgroundCheckData()
    {
        return [
            'status' => $this->background_check_status,
            'date' => $this->background_check_date?->format('d M Y'),
            'notes' => $this->background_check_notes,
            'files' => $this->background_check_files ?? [],
            'files_count' => count($this->background_check_files ?? []),
            'has_files' => !empty($this->background_check_files),
            'status_label' => $this->getBackgroundCheckStatusLabel()
        ];
    }

    /**
     * Get certificates organized by type
     */
    public function getCertificatesByType()
    {
        $certificates = $this->employeeCertificates()->with('certificateType')->get();

        return $certificates->groupBy(function($certificate) {
            return $certificate->certificateType->name;
        })->map(function($group) {
            return $group->sortByDesc('issue_date')->values();
        });
    }

    /**
     * Get certificate statistics for container overview
     */
    public function getCertificateStatistics()
    {
        $certificates = $this->employeeCertificates;

        $stats = [
            'total' => $certificates->count(),
            'active' => $certificates->where('status', 'active')->count(),
            'expired' => $certificates->where('status', 'expired')->count(),
            'expiring_soon' => $certificates->where('status', 'expiring_soon')->count(),
            'pending' => $certificates->where('status', 'pending')->count(),
        ];

        // Calculate compliance rate
        $activeAndCompleted = $stats['active'] + $certificates->where('status', 'completed')->count();
        $stats['compliance_rate'] = $stats['total'] > 0
            ? round(($activeAndCompleted / $stats['total']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get human-readable background check status
     */
    public function getBackgroundCheckStatusLabel()
    {
        $labels = [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'cleared' => 'Cleared',
            'pending_review' => 'Pending Review',
            'requires_follow_up' => 'Requires Follow-up',
            'expired' => 'Expired',
            'rejected' => 'Rejected'
        ];

        return $labels[$this->background_check_status] ?? 'Unknown';
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithCertificates($query)
    {
        return $query->has('employeeCertificates');
    }

    public function scopeWithoutCertificates($query)
    {
        return $query->doesntHave('employeeCertificates');
    }

    public function scopeWithBackgroundCheck($query)
    {
        return $query->whereNotNull('background_check_date');
    }

    public function scopeWithoutBackgroundCheck($query)
    {
        return $query->whereNull('background_check_date');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('employee_id', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // ===== CONTAINER UTILITIES =====

    /**
     * Check if employee has any active certificates
     */
    public function hasActiveCertificates()
    {
        return $this->employeeCertificates()->where('status', 'active')->exists();
    }

    /**
     * Check if employee has any expired certificates
     */
    public function hasExpiredCertificates()
    {
        return $this->employeeCertificates()->where('status', 'expired')->exists();
    }

    /**
     * Check if employee has certificates expiring soon
     */
    public function hasExpiringSoonCertificates()
    {
        return $this->employeeCertificates()->where('status', 'expiring_soon')->exists();
    }

    /**
     * Check if employee has background check cleared
     */
    public function hasBackgroundCheckCleared()
    {
        return $this->background_check_status === 'cleared';
    }

    /**
     * Get container folder path for file storage
     */
    public function getContainerFolderPath()
    {
        return "employees/{$this->employee_id}";
    }

    /**
     * Get container display name for UI
     */
    public function getContainerDisplayName()
    {
        return "{$this->name} (NIP: {$this->employee_id})";
    }

    /**
     * Get container status for grid display
     */
    public function getContainerStatus()
    {
        if ($this->hasExpiredCertificates()) {
            return [
                'icon' => '🔴',
                'label' => 'Has Expired',
                'color' => 'red',
                'priority' => 1
            ];
        }

        if ($this->hasExpiringSoonCertificates()) {
            return [
                'icon' => '🟡',
                'label' => 'Expiring Soon',
                'color' => 'yellow',
                'priority' => 2
            ];
        }

        if ($this->hasActiveCertificates()) {
            return [
                'icon' => '🟢',
                'label' => 'Active',
                'color' => 'green',
                'priority' => 3
            ];
        }

        return [
            'icon' => '⚪',
            'label' => 'No Certificates',
            'color' => 'gray',
            'priority' => 4
        ];
    }

    /**
     * Get background check status for grid display
     */
    public function getBackgroundCheckStatus()
    {
        switch ($this->background_check_status) {
            case 'cleared':
                return [
                    'icon' => '🟢',
                    'label' => 'BG Check ✓',
                    'color' => 'green'
                ];
            case 'in_progress':
                return [
                    'icon' => '🟡',
                    'label' => 'BG Pending',
                    'color' => 'yellow'
                ];
            case 'expired':
            case 'rejected':
                return [
                    'icon' => '🔴',
                    'label' => 'BG Issue',
                    'color' => 'red'
                ];
            default:
                return [
                    'icon' => '🔴',
                    'label' => 'No BG Check',
                    'color' => 'red'
                ];
        }
    }

public function certificateFiles(): HasMany
{
return $this->hasMany(FileStorage::class)->where('status', 'stored');
}

/**
 * Latest version of each certificate type for this employee
 */
public function latestCertificateFiles(): HasMany
{
    return $this->hasMany(FileStorage::class)
        ->where('status', 'stored')
        ->whereIn('id', function($query) {
            $query->selectRaw('MAX(id)')
                ->from('file_storage')
                ->where('status', 'stored')
                ->where('employee_id', $this->id)
                ->groupBy(['employee_id', 'certificate_type_id']);
        });
}

/**
 * Valid (not expired) certificate files
 */
public function validCertificateFiles(): HasMany
{
    return $this->certificateFiles()
        ->where('expiry_date', '>=', now())
        ->where('issue_date', '<=', now());
}

/**
 * Expired certificate files
 */
public function expiredCertificateFiles(): HasMany
{
    return $this->certificateFiles()
        ->where('expiry_date', '<', now());
}

/**
 * Files expiring soon (within 30 days)
 */
public function expiringSoonCertificateFiles(): HasMany
{
    return $this->certificateFiles()
        ->whereBetween('expiry_date', [now(), now()->addDays(30)]);
}

/**
 * Get certificate files for specific certificate type
 */
public function getCertificateFilesForType($certificateTypeId)
{
    return $this->certificateFiles()
        ->where('certificate_type_id', $certificateTypeId)
        ->orderBy('version_number', 'desc')
        ->get();
}

/**
 * Get latest certificate file for specific type
 */
public function getLatestCertificateForType($certificateTypeId)
{
    return $this->certificateFiles()
        ->where('certificate_type_id', $certificateTypeId)
        ->orderBy('version_number', 'desc')
        ->first();
}

/**
 * Check if employee has valid certificate for specific type
 */
public function hasValidCertificateForType($certificateTypeId): bool
{
    return $this->validCertificateFiles()
        ->where('certificate_type_id', $certificateTypeId)
        ->exists();
}

/**
 * Get certificate statistics for this employee
 */
public function getCertificateStats(): array
{
    $files = $this->latestCertificateFiles;

    return [
        'total_certificates' => $files->count(),
        'valid_certificates' => $files->where('validity_status', 'valid')->count(),
        'expiring_soon' => $files->where('validity_status', 'expiring_soon')->count(),
        'expired_certificates' => $files->where('validity_status', 'expired')->count(),
        'certificate_types' => $files->pluck('certificate_type_id')->unique()->count(),
        'total_file_size' => $files->sum('file_size')
    ];
}

/**
 * Get compliance status for this employee
 */
public function getComplianceStatus(): array
{
    $latestFiles = $this->latestCertificateFiles;
    $mandatoryTypes = CertificateType::where('is_mandatory', true)->pluck('id');

    $compliance = [];
    foreach ($mandatoryTypes as $typeId) {
        $file = $latestFiles->where('certificate_type_id', $typeId)->first();
        $compliance[$typeId] = [
            'required' => true,
            'has_certificate' => $file !== null,
            'is_valid' => $file ? $file->validity_status === 'valid' : false,
            'status' => $file ? $file->validity_status : 'missing',
            'expiry_date' => $file ? $file->expiry_date : null
        ];
    }

    return $compliance;
}

// Add to existing scopes

/**
 * Scope to get employees with valid certificates for specific type
 */
public function scopeWithValidCertificateForType($query, $certificateTypeId)
{
    return $query->whereHas('validCertificateFiles', function($q) use ($certificateTypeId) {
        $q->where('certificate_type_id', $certificateTypeId);
    });
}

/**
 * Scope to get employees missing mandatory certificates
 */
public function scopeMissingMandatoryCertificates($query)
{
    $mandatoryTypes = CertificateType::where('is_mandatory', true)->pluck('id');

    return $query->whereDoesntHave('validCertificateFiles', function($q) use ($mandatoryTypes) {
        $q->whereIn('certificate_type_id', $mandatoryTypes);
    });
}

/**
 * Scope to get employees with expiring certificates
 */
public function scopeWithExpiringCertificates($query, $days = 30)
{
    return $query->whereHas('expiringSoonCertificateFiles');
}

// ===== CONTAINER HEALTH CHECK METHODS =====

/**
 * Get container health status
 */
public function getContainerHealth(): array
{
    $health = [
        'status' => 'healthy',
        'score' => 100,
        'issues' => [],
        'warnings' => [],
        'last_checked' => now()->toISOString()
    ];

    try {
        // Check if container exists
        $containerPath = $this->getContainerFolderPath();
        if (!Storage::disk('private')->exists($containerPath)) {
            $health['status'] = 'critical';
            $health['score'] = 0;
            $health['issues'][] = 'Container directory missing';
            return $health;
        }

        // Check metadata file
        $metadataPath = "{$containerPath}/container_metadata.json";
        if (!Storage::disk('private')->exists($metadataPath)) {
            $health['issues'][] = 'Container metadata missing';
            $health['score'] -= 20;
        } else {
            $metadata = json_decode(Storage::disk('private')->get($metadataPath), true);
            
            // Check metadata integrity
            if (!isset($metadata['employee_id']) || $metadata['employee_id'] !== $this->employee_id) {
                $health['issues'][] = 'Employee ID mismatch in metadata';
                $health['score'] -= 15;
            }
            
            // Check last update
            if (isset($metadata['last_updated'])) {
                $lastUpdate = Carbon::parse($metadata['last_updated']);
                if ($lastUpdate->diffInDays(now()) > 30) {
                    $health['warnings'][] = 'Container not updated in 30+ days';
                    $health['score'] -= 5;
                }
            }
        }

        // Check directory structure
        $requiredDirs = ['certificates', 'background_checks', 'documents', 'photos'];
        foreach ($requiredDirs as $dir) {
            if (!Storage::disk('private')->exists("{$containerPath}/{$dir}")) {
                $health['issues'][] = "Missing {$dir} directory";
                $health['score'] -= 10;
            }
        }

        // Check file count consistency
        if ($this->container_file_count !== null) {
            $actualFileCount = count(Storage::disk('private')->allFiles($containerPath));
            if (abs($actualFileCount - $this->container_file_count) > 0) {
                $health['warnings'][] = 'File count mismatch between database and storage';
                $health['score'] -= 5;
            }
        }

        // Check background check files
        if (!empty($this->background_check_files)) {
            foreach ($this->background_check_files as $file) {
                if (!Storage::disk('private')->exists("{$containerPath}/background_checks/{$file['filename']}")) {
                    $health['issues'][] = "Missing background check file: {$file['filename']}";
                    $health['score'] -= 10;
                }
            }
        }

        // Determine overall status
        if ($health['score'] < 50) {
            $health['status'] = 'critical';
        } elseif ($health['score'] < 80) {
            $health['status'] = 'warning';
        } elseif (!empty($health['warnings'])) {
            $health['status'] = 'warning';
        }

    } catch (\Exception $e) {
        $health['status'] = 'error';
        $health['score'] = 0;
        $health['issues'][] = 'Health check failed: ' . $e->getMessage();
    }

    return $health;
}

/**
 * Repair container issues automatically
 */
public function repairContainer(): array
{
    $results = [
        'success' => true,
        'repairs_made' => [],
        'errors' => []
    ];

    try {
        $containerPath = $this->getContainerFolderPath();
        
        // Create missing directories
        $requiredDirs = ['certificates', 'background_checks', 'documents', 'photos'];
        foreach ($requiredDirs as $dir) {
            $dirPath = "{$containerPath}/{$dir}";
            if (!Storage::disk('private')->exists($dirPath)) {
                Storage::disk('private')->makeDirectory($dirPath);
                $results['repairs_made'][] = "Created missing {$dir} directory";
            }
        }

        // Create or repair metadata
        $metadataPath = "{$containerPath}/container_metadata.json";
        if (!Storage::disk('private')->exists($metadataPath)) {
            $this->createContainerMetadata();
            $results['repairs_made'][] = 'Created missing metadata file';
        } else {
            // Update metadata with current info
            $metadata = json_decode(Storage::disk('private')->get($metadataPath), true);
            $metadata['employee_id'] = $this->employee_id;
            $metadata['employee_name'] = $this->name;
            $metadata['last_updated'] = now()->toISOString();
            
            Storage::disk('private')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            $results['repairs_made'][] = 'Updated metadata file';
        }

        // Update file count
        $actualFileCount = count(Storage::disk('private')->allFiles($containerPath));
        if ($this->container_file_count !== $actualFileCount) {
            $this->updateQuietly(['container_file_count' => $actualFileCount]);
            $results['repairs_made'][] = 'Updated file count in database';
        }

    } catch (\Exception $e) {
        $results['success'] = false;
        $results['errors'][] = $e->getMessage();
    }

    return $results;
}

/**
 * Create container metadata file
 */
private function createContainerMetadata(): void
{
    $containerPath = $this->getContainerFolderPath();
    
    $metadata = [
        'employee_id' => $this->employee_id,
        'employee_name' => $this->name,
        'container_created' => $this->container_created_at ? $this->container_created_at->toISOString() : now()->toISOString(),
        'container_version' => '1.0',
        'total_files' => count(Storage::disk('private')->allFiles($containerPath)),
        'total_size' => 0,
        'last_updated' => now()->toISOString(),
        'directories' => [
            'certificates' => ['created' => now()->toISOString(), 'file_count' => 0],
            'background_checks' => ['created' => now()->toISOString(), 'file_count' => 0],
            'documents' => ['created' => now()->toISOString(), 'file_count' => 0],
            'photos' => ['created' => now()->toISOString(), 'file_count' => 0]
        ]
    ];

    Storage::disk('private')->put(
        "{$containerPath}/container_metadata.json",
        json_encode($metadata, JSON_PRETTY_PRINT)
    );
}

/**
 * Scope to get employees with container issues
 */
public function scopeWithContainerIssues($query)
{
    return $query->where(function($q) {
        $q->whereNull('container_created_at')
          ->orWhere('container_status', '!=', 'active')
          ->orWhereRaw('container_last_updated < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    });
}

/**
 * Check if container exists and is healthy
 */
public function hasHealthyContainer(): bool
{
    $health = $this->getContainerHealth();
    return $health['status'] === 'healthy';
}
}
