<?php

// ===== app/Models/Employee.php (Updated) =====
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'nip',
        'name',
        'department_id',
        'email',
        'phone',
        'position',
        'position_level',
        'employment_type',
        'hire_date',
        'supervisor_id',
        'status',
        'background_check_date',
        'background_check_status',
        'background_check_notes',
        'background_check_files',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'profile_photo_path',
        'notes',
        'container_created_at',
        'total_files_count',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'background_check_files' => 'array',
        'container_created_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function employeeCertificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    public function activeCertificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'active');
    }

    // ===== DIGITAL CONTAINER METHODS =====

    /**
     * Create employee container when employee is created
     */
    protected static function booted()
    {
        static::created(function ($employee) {
            $employee->createDigitalContainer();
        });
    }

    /**
     * Create digital container for employee
     */
    public function createDigitalContainer()
    {
        if (!$this->container_created_at) {
            $this->update([
                'container_created_at' => now(),
                'total_files_count' => 0
            ]);
        }
    }

    /**
     * Get complete container data for employee
     */
    public function getContainerData()
    {
        // Background check data
        $backgroundCheckData = [
            'files' => $this->background_check_files ?? [],
            'status' => $this->background_check_status,
            'date' => $this->background_check_date,
            'notes' => $this->background_check_notes,
            'files_count' => count($this->background_check_files ?? [])
        ];

        // Certificates grouped by type with recurrent support
        $certificatesByType = $this->employeeCertificates()
            ->with('certificateType')
            ->orderBy('certificate_type_id')
            ->orderBy('issue_date', 'desc')
            ->get()
            ->groupBy('certificate_type_id');

        $organizedCertificates = [];
        foreach ($certificatesByType as $typeId => $certificates) {
            $type = $certificates->first()->certificateType;
            $current = $certificates->where('status', 'active')->first();
            $history = $certificates->where('status', '!=', 'active');

            $organizedCertificates[] = [
                'type' => $type,
                'current_certificate' => $current,
                'history_certificates' => $history,
                'total_count' => count($certificates),
                'status_summary' => [
                    'active' => $certificates->where('status', 'active')->count(),
                    'expired' => $certificates->where('status', 'expired')->count(),
                    'expiring_soon' => $certificates->where('status', 'expiring_soon')->count(),
                ]
            ];
        }

        // Container statistics
        $containerStats = [
            'total_certificates' => $this->employeeCertificates->count(),
            'active_certificates' => $this->employeeCertificates->where('status', 'active')->count(),
            'background_check_status' => $this->background_check_status,
            'has_background_check' => !empty($this->background_check_files),
            'total_files' => $this->calculateTotalFiles(),
            'container_age_days' => $this->container_created_at ?
                $this->container_created_at->diffInDays(now()) : 0
        ];

        return [
            'employee' => $this,
            'background_check' => $backgroundCheckData,
            'certificates_by_type' => $organizedCertificates,
            'container_stats' => $containerStats
        ];
    }

    /**
     * Calculate total files in container
     */
    public function calculateTotalFiles()
    {
        $bgFiles = count($this->background_check_files ?? []);
        $certFiles = $this->employeeCertificates->sum(function($cert) {
            return count($cert->certificate_files ?? []);
        });

        $total = $bgFiles + $certFiles;

        // Update cached count
        if ($this->total_files_count !== $total) {
            $this->update(['total_files_count' => $total]);
        }

        return $total;
    }

    /**
     * Add background check file to container
     */
    public function addBackgroundCheckFile($filePath, $originalName, $fileSize, $mimeType)
    {
        $files = $this->background_check_files ?? [];

        $files[] = [
            'path' => $filePath,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => auth()->user()->name ?? 'System'
        ];

        $this->update(['background_check_files' => $files]);
        $this->calculateTotalFiles(); // Update file count
    }

    /**
     * Remove background check file from container
     */
    public function removeBackgroundCheckFile($fileIndex)
    {
        $files = $this->background_check_files ?? [];

        if (isset($files[$fileIndex])) {
            unset($files[$fileIndex]);
            $files = array_values($files); // Reindex array

            $this->update(['background_check_files' => $files]);
            $this->calculateTotalFiles(); // Update file count
        }
    }

    // ===== EXCEL SYNC METHODS =====

    /**
     * Find employee by NIP (for Excel sync)
     */
    public static function findByNip($nip)
    {
        return static::where('nip', $nip)->first();
    }

    /**
     * Create or update employee from Excel data
     */
    public static function createOrUpdateFromExcel($nipData, $namaData, $departemenData)
    {
        // Find department
        $department = Department::where('name', $departemenData)
            ->orWhere('code', $departemenData)
            ->first();

        if (!$department) {
            $department = Department::create([
                'name' => $departemenData,
                'code' => strtoupper(substr($departemenData, 0, 5)),
                'description' => "Auto-created from Excel sync"
            ]);
        }

        // Find existing employee
        $employee = static::findByNip($nipData);

        if ($employee) {
            // Update existing
            $employee->update([
                'name' => $namaData,
                'department_id' => $department->id
            ]);
            return ['employee' => $employee, 'action' => 'updated'];
        } else {
            // Create new
            $employee = static::create([
                'employee_id' => 'EMP' . str_pad(static::max('id') + 1, 4, '0', STR_PAD_LEFT),
                'nip' => $nipData,
                'name' => $namaData,
                'department_id' => $department->id,
                'status' => 'active',
                'background_check_status' => 'not_started'
            ]);
            return ['employee' => $employee, 'action' => 'created'];
        }
    }

    // ===== SEARCH & FILTERS =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeWithContainerData($query)
    {
        return $query->with(['department', 'employeeCertificates.certificateType']);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('nip', 'like', "%{$search}%")
              ->orWhere('employee_id', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%");
        });
    }
}
