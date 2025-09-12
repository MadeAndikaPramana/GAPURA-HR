<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class MPGATrainingImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable;

    private array $importResults = [
        'total_rows' => 0,
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'employee_matches' => 0,
        'certificate_types_created' => 0,
        'details' => [
            'success' => [],
            'errors' => [],
            'skipped' => [],
            'new_certificate_types' => []
        ]
    ];

    private bool $createCertificateTypes;
    private bool $updateExisting;
    
    // MPGA Training Excel Column Mappings
    // Based on typical MPGA training record structure
    private array $columnMapping = [
        'no' => 'no',
        'nip' => 'nip',
        'employee_id' => 'employee_id', 
        'nama' => 'name',
        'name' => 'name',
        'department' => 'department',
        'departemen' => 'department',
        'bagian' => 'department',
        'position' => 'position',
        'jabatan' => 'position',
        'posisi' => 'position',
        'training_code' => 'training_code',
        'kode_training' => 'training_code',
        'training_name' => 'training_name',
        'nama_training' => 'training_name',
        'training_type' => 'training_type',
        'jenis_training' => 'training_type',
        'training_date' => 'training_date',
        'tanggal_training' => 'training_date',
        'completion_date' => 'completion_date',
        'tanggal_selesai' => 'completion_date',
        'issue_date' => 'issue_date',
        'tanggal_terbit' => 'issue_date',
        'expiry_date' => 'expiry_date',
        'tanggal_expired' => 'expiry_date',
        'validity_months' => 'validity_months',
        'masa_berlaku' => 'validity_months',
        'certificate_number' => 'certificate_number',
        'nomor_sertifikat' => 'certificate_number',
        'issuer' => 'issuer',
        'penerbit' => 'issuer',
        'training_provider' => 'training_provider',
        'penyelenggara' => 'training_provider',
        'instructor' => 'instructor',
        'instruktur' => 'instructor',
        'location' => 'location',
        'lokasi' => 'location',
        'training_hours' => 'training_hours',
        'jam_training' => 'training_hours',
        'score' => 'score',
        'nilai' => 'score',
        'grade' => 'grade',
        'status' => 'status',
        'cost' => 'cost',
        'biaya' => 'cost',
        'notes' => 'notes',
        'catatan' => 'notes',
        'keterangan' => 'notes'
    ];

    public function __construct(bool $updateExisting = false, bool $createCertificateTypes = true)
    {
        $this->updateExisting = $updateExisting;
        $this->createCertificateTypes = $createCertificateTypes;
    }

    /**
     * Process the Excel collection
     */
    public function collection(Collection $collection): void
    {
        $this->importResults['total_rows'] = $collection->count();
        
        Log::info("Starting MPGA training import", [
            'total_rows' => $this->importResults['total_rows'],
            'update_existing' => $this->updateExisting,
            'create_certificate_types' => $this->createCertificateTypes
        ]);

        DB::beginTransaction();
        
        try {
            foreach ($collection as $rowIndex => $row) {
                $this->processRow($row->toArray(), $rowIndex + 2); // +2 for header and 1-based indexing
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("MPGA import failed", ['error' => $e->getMessage()]);
            throw $e;
        }

        Log::info("MPGA training import completed", $this->importResults);
    }

    /**
     * Process individual row
     */
    private function processRow(array $rowData, int $rowNumber): void
    {
        try {
            // Normalize column names and extract data
            $normalizedData = $this->normalizeRowData($rowData);
            
            // Skip empty rows
            if (empty($normalizedData['nip']) && empty($normalizedData['employee_id']) && empty($normalizedData['name'])) {
                $this->importResults['skipped']++;
                return;
            }

            // Find or create employee
            $employee = $this->findOrCreateEmployee($normalizedData, $rowNumber);
            
            if (!$employee) {
                $this->importResults['errors']++;
                $this->importResults['details']['errors'][] = [
                    'row' => $rowNumber,
                    'error' => 'Employee not found or created',
                    'data' => $normalizedData
                ];
                return;
            }

            // Find or create certificate type
            $certificateType = $this->findOrCreateCertificateType($normalizedData, $rowNumber);
            
            if (!$certificateType) {
                $this->importResults['errors']++;
                $this->importResults['details']['errors'][] = [
                    'row' => $rowNumber,
                    'error' => 'Certificate type not found or created',
                    'data' => $normalizedData
                ];
                return;
            }

            // Create or update certificate
            $certificate = $this->createOrUpdateCertificate($employee, $certificateType, $normalizedData, $rowNumber);
            
            if ($certificate) {
                $this->importResults['processed']++;
                if ($certificate->wasRecentlyCreated) {
                    $this->importResults['created']++;
                } else {
                    $this->importResults['updated']++;
                }
                
                $this->importResults['details']['success'][] = [
                    'row' => $rowNumber,
                    'employee' => $employee->name,
                    'certificate_type' => $certificateType->name,
                    'action' => $certificate->wasRecentlyCreated ? 'created' : 'updated'
                ];
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->importResults['details']['errors'][] = [
                'row' => $rowNumber,
                'error' => $e->getMessage(),
                'data' => $rowData
            ];
            
            Log::warning("Error processing MPGA row {$rowNumber}", [
                'error' => $e->getMessage(),
                'data' => $rowData
            ]);
        }
    }

    /**
     * Normalize row data using column mapping
     */
    private function normalizeRowData(array $rowData): array
    {
        $normalized = [];
        
        foreach ($rowData as $key => $value) {
            $normalizedKey = strtolower(trim($key));
            $normalizedKey = str_replace([' ', '_', '-'], '_', $normalizedKey);
            
            if (isset($this->columnMapping[$normalizedKey])) {
                $mappedKey = $this->columnMapping[$normalizedKey];
                $normalized[$mappedKey] = is_string($value) ? trim($value) : $value;
            }
        }

        return $normalized;
    }

    /**
     * Find or create employee based on NIP/ID
     */
    private function findOrCreateEmployee(array $data, int $rowNumber): ?Employee
    {
        // Try to find employee by NIP or employee_id
        $employee = null;
        
        if (!empty($data['nip'])) {
            $employee = Employee::where('employee_id', $data['nip'])
                              ->orWhere('nip', $data['nip'])
                              ->first();
        }
        
        if (!$employee && !empty($data['employee_id'])) {
            $employee = Employee::where('employee_id', $data['employee_id'])
                              ->orWhere('nip', $data['employee_id'])
                              ->first();
        }

        if ($employee) {
            $this->importResults['employee_matches']++;
            
            // Update employee data if needed
            $updated = false;
            if (!empty($data['name']) && $employee->name !== $data['name']) {
                $employee->name = $data['name'];
                $updated = true;
            }
            
            if (!empty($data['position']) && $employee->position !== $data['position']) {
                $employee->position = $data['position'];
                $updated = true;
            }

            // Handle department
            if (!empty($data['department'])) {
                $department = $this->findOrCreateDepartment($data['department']);
                if ($department && $employee->department_id !== $department->id) {
                    $employee->department_id = $department->id;
                    $updated = true;
                }
            }

            if ($updated) {
                $employee->save();
            }
            
            return $employee;
        }

        // If employee not found, log warning but don't create
        $this->importResults['details']['errors'][] = [
            'row' => $rowNumber,
            'error' => 'Employee not found',
            'nip' => $data['nip'] ?? $data['employee_id'] ?? 'unknown',
            'name' => $data['name'] ?? 'unknown'
        ];

        return null;
    }

    /**
     * Find or create certificate type based on training code/name
     */
    private function findOrCreateCertificateType(array $data, int $rowNumber): ?CertificateType
    {
        $certificateType = null;
        
        // Try to find by training code first
        if (!empty($data['training_code'])) {
            $certificateType = CertificateType::where('code', $data['training_code'])->first();
        }
        
        // Try to find by training name if code not found
        if (!$certificateType && !empty($data['training_name'])) {
            $certificateType = CertificateType::where('name', 'like', '%' . $data['training_name'] . '%')->first();
        }

        // Create new certificate type if not found and creation is enabled
        if (!$certificateType && $this->createCertificateTypes) {
            $trainingName = $data['training_name'] ?? 'Unknown Training';
            $trainingCode = $data['training_code'] ?? 'MPGA-' . substr(md5($trainingName), 0, 8);
            
            $certificateType = CertificateType::create([
                'name' => $trainingName,
                'code' => $trainingCode,
                'category' => $data['training_type'] ?? 'MPGA Training',
                'validity_months' => $this->parseValidityMonths($data),
                'warning_days' => 90, // Default warning days
                'is_mandatory' => false,
                'is_recurrent' => true, // MPGA trainings are typically recurrent
                'is_active' => true,
                'description' => 'Auto-created from MPGA training import',
                'estimated_duration_hours' => $this->parseTrainingHours($data)
            ]);

            $this->importResults['certificate_types_created']++;
            $this->importResults['details']['new_certificate_types'][] = [
                'name' => $trainingName,
                'code' => $trainingCode,
                'row' => $rowNumber
            ];
        }

        return $certificateType;
    }

    /**
     * Create or update employee certificate
     */
    private function createOrUpdateCertificate(Employee $employee, CertificateType $certificateType, array $data, int $rowNumber): ?EmployeeCertificate
    {
        // Check if certificate already exists for this employee and type
        $existingCertificate = EmployeeCertificate::where('employee_id', $employee->id)
                                                ->where('certificate_type_id', $certificateType->id)
                                                ->orderBy('issue_date', 'desc')
                                                ->first();

        // Parse dates
        $issueDate = $this->parseDate($data['issue_date'] ?? $data['completion_date'] ?? $data['training_date']);
        $expiryDate = $this->parseDate($data['expiry_date']);
        $completionDate = $this->parseDate($data['completion_date']);
        $trainingDate = $this->parseDate($data['training_date']);

        // Calculate expiry date if not provided
        if (!$expiryDate && $issueDate && $certificateType->validity_months) {
            $expiryDate = Carbon::parse($issueDate)->addMonths($certificateType->validity_months);
        }

        $certificateData = [
            'employee_id' => $employee->id,
            'certificate_type_id' => $certificateType->id,
            'certificate_number' => $data['certificate_number'] ?? null,
            'issuer' => $data['issuer'] ?? 'MPGA',
            'training_provider' => $data['training_provider'] ?? $data['issuer'] ?? 'MPGA',
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'completion_date' => $completionDate,
            'training_date' => $trainingDate,
            'training_hours' => $this->parseNumeric($data['training_hours']),
            'cost' => $this->parseNumeric($data['cost']),
            'score' => $this->parseNumeric($data['score']),
            'location' => $data['location'] ?? null,
            'instructor_name' => $data['instructor'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending' // Will be updated by status calculation
        ];

        $certificate = null;

        if ($existingCertificate && $this->updateExisting) {
            // Update existing certificate if newer or force update
            if (!$issueDate || !$existingCertificate->issue_date || $issueDate->greaterThan($existingCertificate->issue_date) || $this->updateExisting) {
                $existingCertificate->update($certificateData);
                $certificate = $existingCertificate;
            }
        } else if (!$existingCertificate || $this->isNewerCertificate($existingCertificate, $certificateData)) {
            // Create new certificate
            $certificate = EmployeeCertificate::create($certificateData);
        }

        if ($certificate) {
            // Update status based on dates
            $certificate->updateStatusBasedOnDates();
        }

        return $certificate;
    }

    /**
     * Check if this is a newer certificate version
     */
    private function isNewerCertificate(EmployeeCertificate $existing, array $newData): bool
    {
        $existingIssueDate = $existing->issue_date;
        $newIssueDate = $newData['issue_date'];
        
        if (!$existingIssueDate || !$newIssueDate) {
            return true; // Allow if we can't compare dates
        }

        return Carbon::parse($newIssueDate)->greaterThan($existingIssueDate);
    }

    /**
     * Find or create department
     */
    private function findOrCreateDepartment(string $departmentName): ?Department
    {
        return Department::firstOrCreate(
            ['name' => $departmentName],
            [
                'code' => strtoupper(substr($departmentName, 0, 3)),
                'is_active' => true
            ]
        );
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // Handle Excel serial dates
            if (is_numeric($dateValue)) {
                return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)->format('Y-m-d'));
            }

            // Handle string dates
            if (is_string($dateValue)) {
                return Carbon::parse($dateValue);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse numeric values
     */
    private function parseNumeric($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove non-numeric characters except decimal point
        $cleaned = preg_replace('/[^\d.]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Parse validity months from data
     */
    private function parseValidityMonths(array $data): ?int
    {
        if (!empty($data['validity_months'])) {
            $months = $this->parseNumeric($data['validity_months']);
            return $months ? (int) $months : null;
        }

        // Try to calculate from issue and expiry dates
        if (!empty($data['issue_date']) && !empty($data['expiry_date'])) {
            $issueDate = $this->parseDate($data['issue_date']);
            $expiryDate = $this->parseDate($data['expiry_date']);
            
            if ($issueDate && $expiryDate) {
                return $issueDate->diffInMonths($expiryDate);
            }
        }

        // Default for MPGA trainings
        return 36; // 3 years
    }

    /**
     * Parse training hours
     */
    private function parseTrainingHours(array $data): ?float
    {
        return $this->parseNumeric($data['training_hours'] ?? null);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            // Basic validation - we handle most validation in the processing logic
            '*.nip' => 'sometimes|string',
            '*.nama' => 'sometimes|string',
            '*.training_code' => 'sometimes|string',
            '*.training_name' => 'sometimes|string',
        ];
    }

    /**
     * Handle validation errors
     */
    public function onError(Throwable $error): void
    {
        $this->importResults['errors']++;
        Log::error("MPGA import validation error", ['error' => $error->getMessage()]);
    }

    /**
     * Handle validation failures
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->importResults['errors']++;
            $this->importResults['details']['errors'][] = [
                'row' => $failure->row(),
                'error' => implode(', ', $failure->errors()),
                'values' => $failure->values()
            ];
        }
    }

    /**
     * Get import results
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Generate detailed import report
     */
    public function generateReport(): string
    {
        $results = $this->importResults;
        
        $report = "MPGA Training Import Report\n";
        $report .= "=" . str_repeat("=", 30) . "\n\n";
        $report .= "Summary:\n";
        $report .= "- Total rows processed: {$results['total_rows']}\n";
        $report .= "- Successfully processed: {$results['processed']}\n";
        $report .= "- Certificates created: {$results['created']}\n";
        $report .= "- Certificates updated: {$results['updated']}\n";
        $report .= "- Rows skipped: {$results['skipped']}\n";
        $report .= "- Errors: {$results['errors']}\n";
        $report .= "- Employee matches: {$results['employee_matches']}\n";
        $report .= "- New certificate types created: {$results['certificate_types_created']}\n\n";

        if (!empty($results['details']['new_certificate_types'])) {
            $report .= "New Certificate Types Created:\n";
            foreach ($results['details']['new_certificate_types'] as $type) {
                $report .= "- {$type['name']} ({$type['code']}) - Row {$type['row']}\n";
            }
            $report .= "\n";
        }

        if (!empty($results['details']['errors'])) {
            $report .= "Errors:\n";
            foreach (array_slice($results['details']['errors'], 0, 10) as $error) {
                $report .= "- Row {$error['row']}: {$error['error']}\n";
            }
            if (count($results['details']['errors']) > 10) {
                $report .= "... and " . (count($results['details']['errors']) - 10) . " more errors\n";
            }
        }

        return $report;
    }
}